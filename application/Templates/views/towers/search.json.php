<?php
namespace Blueline;
use Pan\View;
use Flourish\fJSON;

View::cache( true );

echo fJSON::encode( array_map( function( $t ) { return $t->toArray(); }, $this->get( 'towers' ) ) );
