<?php
namespace Blueline;

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
	'dsn' => 'mysql:host=localhost;dbname=blueline',
	'username' => 'blueline',
	'password' => 'password'
) );
