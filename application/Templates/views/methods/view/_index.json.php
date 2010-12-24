<?php
echo json_encode( array_map( function( $m ) { return $m->toArray(); }, $methods ) );
