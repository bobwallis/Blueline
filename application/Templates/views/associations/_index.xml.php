<?php echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n"; ?>
<associations>
<?php
foreach( $associations as $association ) {
	echo "\t<association"
		. ($association->abbreviation()?' abbreviation="'.htmlentities( $association->abbreviation() ).'"':'')
		. ($association->link()?' link="'.htmlentities( $association->link() ).'"':'')
		. ($association->towerCount()?' towerCount="'.$association->towerCount().'"':'')
		. '>'
		. htmlentities( $association->name() )
		. "</association>\n";
}
?>
</associations>
