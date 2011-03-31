<?php
namespace Blueline;
use Pan\View;

View::cache( true );

echo json_encode( array_map( function( $m ) { return $m->toArray(); }, $this->get( 'methods', array() ) ) );
