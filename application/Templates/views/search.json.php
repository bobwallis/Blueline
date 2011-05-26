<?php
namespace Blueline;
use Flourish\fJSON;

echo fJSON::encode( array(
	'associations' => array_map( function( $a ) { return $a->toArray(); }, $associations ),
	'methods' => array_map( function( $m ) { return $m->toArray(); }, $methods ),
	'towers' => array_map( function( $t ) { return $t->toArray(); }, $towers )
) );
