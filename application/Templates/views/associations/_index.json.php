<?php
namespace Blueline;
use Pan\View;

View::cache( true );

echo json_encode( array_map( function( $a ) { return $a->toArray(); }, $this->get( 'associations', array() ) ) );
