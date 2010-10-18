<associations>
<?php
foreach( $associations as $association ) {
	echo "\t<association"
		. (!empty( $association['abbreviation'] )?' abbreviation="'.htmlentities( $association['abbreviation'] ).'"':'')
		. (!empty( $association['link'] )?' link="'.htmlentities( $association['link'] ).'"':'')
		. (!empty( $association['towerCount'] )?' towerCount="'.intval( $association['towerCount'] ).'"':'')
		. '>'
		. htmlentities( $association['name'] )
		. "</association>\n";
}
?>
</associations>
