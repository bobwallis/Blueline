<?php
namespace Blueline;
use Pan\View;
use Flourish\fJSON;

View::cache( true );

echo fJSON::encode( array_map( function( $m ) { return $m->toArray(); }, $this->get( 'methods', array() ) ) );
