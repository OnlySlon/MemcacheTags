<?php
/**
 * This add tags and cache expiration by tags support to basic Memcache class
 * git: https://github.com/OnlySlon/MemcacheTags
 *
 * (C) Vadim Melikhow <uprsnab@gmail.com>
 *
 */


class MemcacheTags extends Memcache 
{
    private $cache_timeout_tag = 1000000;    // tag expiration 
    private $tags_key_prefix   = "tags_";

    function __construct($host = '127.0.0.1', $port = 11211)
    {
        parent::connect($host, $port);
    }


    private function cacheTagsInsert($tags)
    {
	while(list($key, $tag) = each($tags))
	{
	    $fetch = parent::get($this->tags_key_prefix.$tag);
	    if (strlen($fetch) == 0)
	    {
		parent::add($this->tags_key_prefix.$tag, 0, false, $this->cache_timeout_tag); // insert new tag
		$tags_ver[$tag] = 0;
	    } else
		$tags_ver[$tag] = (int) $fetch;
	}
	return $tags_ver;
    }


    private function tagsIsValid($tagsver)
    {
	if (count($tagsver) == 0) return true;
	while(list($key, $val) = each($tagsver))
	{
	    $fetch  = $this->get($this->tags_key_prefix.$key); // get tag
	    if ($fetch != $val)
		return false;
	}
	return true;
    }


    /*
	add value with tags
    */
    public function add($key, $value, $flags = false,  $timeout = 0,  $tags = [])
    {
	if (empty($tags))
	    return parent::add($key, $value, $flags, $timeout);
    
	$tags_ver = $this->cacheTagsInsert($tags);
	$to_cache['tagsver']  = $tags_ver;
	$to_cache['data']     = $value;
	if (!$timeout)
	    $to_cache['valid_ts'] = time() + 100500; // look like 27 hours
	else
	    $to_cache['valid_ts'] = time() + $timeout;
	$fetch = parent::get($key);
	if (empty($fetch))
	    parent::add($key, $to_cache, $flags, $timeout);
	else
	    parent::replace($key, $to_cache, $flags, $timeout);
    }


    public function get($key)
    {
	$data = parent::get($key);

	if ($data === FALSE) return FALSE;

	// this is natve call
	if (!isset($data['tagsver']))
	    return $data;

	if (self::tagsIsValid($data['tagsver']))
	{
	    if ($data['valid_ts'] < time())
		return FALSE;
	    return $data['data'];
	} else
	    return FALSE;
    }

    /*
	Poison one or more tag(s)
    */
    public function poisontags($tags = ['default'])
    {
	while(list($key, $tag) = each($tags))
	{
	    $fetch = parent::get($this->tags_key_prefix.$tag);
	    if (strlen($fetch) == 0)
		parent::add($this->tags_key_prefix.$tag, 0, false, $this->cache_timeout_tag); // no this tag. Just reset to zero
	    else
		parent::increment($this->tags_key_prefix.$tag, 1); // this tag exist in cache. just add +1 
	}
    }
}

?>