# Magister6.php Documentation

## Navigation ##
- [Features](#features)
- [Basic Use](#basic-use)
- [School Search](#school-search)
- [Magister Info](#magister-info)
- [Set necessary data](#set-necessary-data)
- [Login](#login)
- [User Info](#user-info)
- [Picture](#picture)
- [Appointments](#appointments)
- [Homework](#homework)
- [Grades](#grades)
- [Subjects]()
- [Credits](#credits)

##Features ##

- School search via Magister 6 api
- Login
- Appointments
- Homework
- Grades (with filter)

##Basic Use ##

*Use this when you don't know the school's url (or user/password) and want to use functions without being logged in*
```PHP
<?php
require('Magister6.class.php');
$magister = new Magister;
?>
```
*You can also use quick start, by calling the following: (when you know them already)*
```PHP
<?php
require('Magister6.class.php');
$magister = new Magister("school.magister.net", "username", "password", bool autoLogin);
?>
```
*When you use all the parameters you don't need to do ["Set necessary data"](#set-necessary-data) or ["Login"](#login)*

## School Search ##
```PHP
$magister->findSchool('part of schoolname');
```

## Magister Info ##
```PHP
$magister->getMagisterInfo();
```
*Returns current Magister release info (such as versions etc)*

## Set necessary data  ##
```PHP
$magister->setSchool('https://school.magister.net/');

$magister->setCredentials('Username', 'Password');
```
*The URL is partially fixed by the library, it automaticaly adds a slash to the end if not present and https:// to the front if not present. (Although it's recommended to do this yourself)*

## Login ##
```PHP
$magister->login();
```

## User Info ##
```PHP
$magister->getUserInfo();
```
*Returns user's profile*

## Picture ##
```PHP
$magister->getPicture(int 'width', int 'height', bool 'crop');
```
*Returns user's profile picture in Base64 format (see test.php for example)*

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

## Get Subjects ##
```PHP
$magister->getSubjects();
```
*This returns all the courses the student is attending at the moment*

## Credits ##
This library is made by [Thomas Konings a.k.a. tkon99](http://tkon99.me)