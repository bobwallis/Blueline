<?php
echo json_encode( array_map( function( $t ) { return $t->toArray(); }, $towers ) );
