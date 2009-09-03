<?

require_once('spyc.php');

$spyc = new Spyc();

$config = $spyc->YAMLLoad('ce3k.yaml');

print_r($config);

?>
