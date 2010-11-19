<?php
foreach( $associations as $association ) {
	echo $association->name()
		. ($association->link()?"\n  <".$association->link().'>':'') . "\n"
		. '  ' . $association->towerCount() . ' affiliated towers'
		. "\n\n";
}
