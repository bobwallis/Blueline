<?php
foreach( $associations as $association ) {
	echo $association['name']
		. (!empty( $association['link'] )?"\n  <".$associations[0]['link'].'>':'') . "\n"
		. '  ' . $association['towerCount'] . ' affiliated towers'
		. "\n\n";
}
