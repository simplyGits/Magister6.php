<?php
require('Magister6.class.php');
$magister = new Magister;
$magister->setSchool('https://schoolname.magister.net/');
$magister->setCredentials("username", "password");

$magister->login();

echo('<h1>Magister '.$magister->getMagisterInfo()->ProductVersie.'</h1>');

echo('Naam: '.$magister->getUserInfo()->Roepnaam.' '.$magister->getUserInfo()->Achternaam);