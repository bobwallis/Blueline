<?php
namespace Blueline;
use Pan\View;
use Flourish/fJSON;

View::cache( true );

echo fJSON::encode( array(
	$this->get( 'q' ),
	$this->get( 'suggestions[queries]' ),
	array(),
	$this->get( 'suggestions[URLs]' )
) );
