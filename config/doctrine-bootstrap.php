<?php
//  config/doctrine-bootstrap.php

/** @var Composer\Autoload\ClassLoader $loader */
$loader = require (__DIR__. '/../vendor/autoload.php');
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
$params = require ('autoload/doctrine.local.php');
$dbParams = $params['doctrine']['connection']['orm_default']['params']; 
$more = require('autoload/local.php');

$dbParams = array_merge($dbParams,$more['doctrine']['connection']['orm_default']['params']);
$entitiesPath = [
	__DIR__ .'/../module/Application/src/Application/Entity/',
];
$config = Setup::createAnnotationMetadataConfiguration($entitiesPath,true,null, null, false);
return EntityManager::create($dbParams, $config);