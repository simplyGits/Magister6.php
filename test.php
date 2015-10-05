<?php
require('lib/Magister6.class.php');
$magister = new Magister("sgtongerlo.magister.net", "user", "pass", true);
// $magister->setSchool('https://sgtongerlo.magister.net/');
// $magister->setCredentials("user", "pass");

//$magister->login();

echo('<h1>Magister '.$magister->getMagisterInfo()->ProductVersie.'</h1>');

echo('Naam: '.$magister->getUserInfo()->Roepnaam.' '.$magister->getUserInfo()->Achternaam.'<hr>');

echo('<img src="data:image/jpeg;base64,'.$magister->getPicture(200,200,true).'">');