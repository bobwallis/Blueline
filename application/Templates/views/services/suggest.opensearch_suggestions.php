<?php
namespace Blueline;
use Pan\View;

View::cache( true );

echo json_encode( array(
	$this->get( 'q' ),
	$this->get( 'suggestions[queries]' ),
	array(),
	$this->get( 'suggestions[URLs]' )
) );
