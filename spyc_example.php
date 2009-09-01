<?php
//include the class file
include_once("settings.class.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>YAML Config Class Example</title>
</head>
<body>
<?php
    if ($_GET["action"]=="show") {
        //initialize the class
        $settings = Settings::singleton();
        //printout all collected settings
        print("<h1>CONFIGURATION SETTINGS FROM [".$settings->config_file."]:</h1><pre>");
        print_r($settings->get);
        print ("</pre>");
        print ("<h2>Dumping strings from [".$settings->localization_file."]:</h2><p>");
        //note that $l is a global variable, declared and made available by the Settings class
        foreach($l as $key=>$value) {
            print ("$key => $value<br>");
        }
        print ("</p>");
    }

?>
<p><strong>Click to view configuration settings and localization strings in: <a href="?action=show&amp;lang=en">English</a> / <a href="?action=show&amp;lang=fr">French</a></strong></p>
</body>

</html> 
