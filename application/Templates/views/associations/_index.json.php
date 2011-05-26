<?php
namespace Blueline;
use Pan\View;
use Flourish\fJSON;

View::cache( true );

echo fJSON::encode( array_map( function( $a ) { return $a->toArray(); }, $this->get( 'associations', array() ) ) );
