<?php
require('lib/Magister6.class.php');
$magister = new Magister("sgtongerlo.magister.net", "user", "pass");
// $magister->setSchool('https://sgtongerlo.magister.net/');
// $magister->setCredentials("user", "pass");

$magister->login();

echo('<h1>Magister '.$magister->getMagisterInfo()->ProductVersie.'</h1>');

echo('Naam: '.$magister->getUserInfo()->Roepnaam.' '.$magister->getUserInfo()->Achternaam);