<?php
namespace Pan;

// These environment-type settings are passed to the View
Config::set( 'site', array(
	'development' => true, // Obviously set this to false on servers to which the public have access to prevent debug messages with passwords in being thrown everywhere.
	'baseURL' => 'http://blueline.local',
	//'ga_trackingCode' => 'UA-11877145-5',
	'ga_trackingCode' => false
) );

// Caches
Config::set( 'caches', array(
	'view' => array(
		'type' => 'directory',
		'data_store' => CACHE_PATH.'/views',
		'ttl_store' => array(
			'type' => 'directory',
			'data_store' => CACHE_PATH.'/views/metadata',
		),
		'serialize' => false
	)
) );

// Database
Config::set( 'database', array(
	'dsn' => 'mysql:host=localhost;dbname=blueline',
	'username' => 'blueline',
	'password' => 'password'
) );
