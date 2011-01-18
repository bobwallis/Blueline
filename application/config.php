<?php
namespace Blueline;

// Obviously set this to false on servers to which the public have access to
// prevent debug messages with passwords in being thrown everywhere.
Config::set( 'development', true );

// These environment-type settings are passed to the View
Config::set( 'site', array( 
	'baseURL' => 'http://blueline.local',
) );
View::set( 'site', Config::get( 'site' ) );

// Google Analytics
//Config::set( 'ga.trackingCode', 'UA-11877145-5' );
Config::set( 'ga.trackingCode', false );

// HTML Tidy
Config::set( 'htmlTidy', false );
/* Not so useful until HTML Tidy supports HTML5
Config::set( 'htmlTidy', (!function_exists('tidy_parse_string'))? false : array(
	'bare' => true,
	'clean' => false,
	'drop-empty-paras' => false,
	'doctype' => '<!DOCTYPE html>',
	'hide-comments' => true,
	'indent' => true,
	'indent-cdata' => true,
	'indent-spaces' => 1,
	'literal-attributes' => true,
	'new-blocklevel-tags' => 'section,header,footer,nav',
	'new-inline-tags' => 'video,audio,canvas,ruby,rt,rp',
	'tab-size' => 1,
	'wrap' => 0
) );
*/

// Caches
Config::set( 'caches', array(
	array(
		'name' => 'static',
		'type' => 'File',
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
		'type' => 'File',
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
