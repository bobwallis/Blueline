<?php
namespace Blueline;
use Pan\View;

View::cache( true );

echo json_encode( array_map( function( $t ) { return $t->toArray(); }, $this->get( 'towers' ) ) );
