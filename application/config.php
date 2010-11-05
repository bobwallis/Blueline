<?php
namespace Blueline;

// Obviously set this to false on servers to which the public have access to
// prevent debug messages with passwords in being thrown everywhere.
Config::set( 'development', true );

Config::set( 'site.baseURL', 'http://blueline.local' );

// Google Analytics
//Config::set( 'ga.trackingCode', 'UA-11877145-5' );
Config::set( 'ga.trackingCode', false );

// Caches
Config::set( 'caches', array(
	array(
		'name' => 'static',
		'type' => 'Fail',
		'options' => array(
			'location' => __DIR__.'/../cache/static',
			'serialize' => false
		)
	),
	array(
		'name' => 'data',
		'type' => ( function_exists( 'apc_cache_info' ) && apc_cache_info( 'user', false ) !== false )? 'APC' : 'File',
		'options' => array(
			'location' => __DIR__.'/../cache/data'
		)
	),
	array(
		'name' => 'dynamic',
		'type' => 'Fail',
		'options' => array(
			'location' => __DIR__.'/../cache/dynamic',
			'serialize' => false
		)
	)
) );

// Database
Config::set( 'database', array(
	'dsn' => 'mysql:host=localhost;dbname=blueline',
	'username' => 'blueline',
	'password' => 'password'
) );
