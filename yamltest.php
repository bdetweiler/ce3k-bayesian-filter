<?

require_once('spyc.php');

$spyc = new Spyc();

$config = $spyc->YAMLLoad('localhost.yaml');

print_r($config);

?>
