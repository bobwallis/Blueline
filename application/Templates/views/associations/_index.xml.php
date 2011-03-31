<?php
namespace Blueline;
use Pan\View;

View::cache( true );

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n"; ?>
<associations>
<?php
foreach( $this->get( 'associations', array() ) as $association ) {
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
