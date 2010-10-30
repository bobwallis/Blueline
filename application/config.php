<?php
namespace Blueline;

// Obviously set this to false on servers to which the public have access to
// prevent debug messages with passwords in being thrown everywhere.
define( 'DEVELOPMENT', true );

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
		'type' => ( apc_cache_info( 'user', false ) !== false )? 'APC' : 'File',
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

Config::set( 'database', array(
	'dsn' => 'mysqll:host=localhost;dbname=blueline',
	'username' => 'blueline',
	'password' => 'password'
) );
