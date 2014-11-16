# Magister6.php Documentation

## Navigation ##
- [Features](#features)
- [Basic Use](#basic-use)
- [School Search](#school-search)
- [Set necessary data](#set-necessary-data)
- [Login](#login)
- [Appointments](#appointments)
- [Homework](#homework)
- [Grades](#grades)
- [Credits](#credits)

##Features ##

- School search via Magister 6 api
- Login
- Appointments
- Homework
- Grades (with filter)

##Basic Use ##

```PHP
<?php
require('Magister6.class.php');
$magister = new Magister;
?>
```

## School Search ##
```PHP
$magister->findSchool('part of schoolname');
```

## Set necessary data  ##
```PHP
$magister->setSchool('https://school.magister.net/');

$magister->setCredentials('Username', 'Password');
```

## Login ##
```PHP
$magister->login();
```

## Appointments ##
```PHP
$magister->getAppointments('date from', 'date to');
```
*Dates are in the YYYY-MM-DD format*

## Homework ##
```PHP
$magister->getHomework('date from', 'date to');
```
*This basically gets all the appointments with content*

## Grades ##
```PHP
$magister->getGrades(bool 'filter subject', bool 'active periods', bool 'calculation only', bool 'PTA only');
```
*All parameters are optional, if none are given Magister 6's defaults will be used*

## Credits ##
This library is made by [Thomas Konings a.k.a. tkon99](http://tkon99.me)