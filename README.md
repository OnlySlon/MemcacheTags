
# README #

Add (extend) cache expiration functionality by tags to PECL Memcache class.
if you not use 5th argument in MemcacheTags::add  function behaves like a parent.

MemcacheTags::poisontags can poison one or more tags

***NOTES***

$mc = new  MemcacheTags();
$mc->add("alice@user.com", ['password'=> 'secret',     'policy' => ['EditAllow', 'AddAllow']], false, 3000, ['managers', 'dealers']);
$mc->add("bob@user.com",   ['password'=> 'secret_too', 'policy' => ['EditAllow']],             false, 3000, ['dealers', 'smartusers']);


$alice =  $mc->get("alice@user.com");
$bob   =  $mc->get("bob@user.com");
print_r($alice);
print_r($bob);


// Lets expire all records with tag 'dealers'
$mc->poisontags(['dealers']);

$alice =  $mc->get("alice@user.com");
$bob   =  $mc->get("bob@user.com");
// Alice & bob now FALSE

