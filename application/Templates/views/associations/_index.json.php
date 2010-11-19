<?php
echo json_encode( array_map( function( $a ) { return $a->toArray(); }, $associations ) );
