<?php
namespace Blueline;
use Pan\View;

View::cache( true );

foreach( $this->get( 'associations', array() ) as $association ) {
	echo $association->name()
		. ($association->link()?"\n  <".$association->link().'>':'') . "\n"
		. '  ' . $association->towerCount() . ' affiliated towers'
		. "\n\n";
}
