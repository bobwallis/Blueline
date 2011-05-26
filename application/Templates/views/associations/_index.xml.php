<?php
namespace Blueline;
use Pan\View;
use Flourish\fXML;

View::cache( true );

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n"; ?>
<associations>
<?php
foreach( $this->get( 'associations', array() ) as $association ) {
	echo "\t<association"
		. ($association->abbreviation()?' abbreviation="'.fXML::encode( $association->abbreviation() ).'"':'')
		. ($association->link()?' link="'.fXML::encode( $association->link() ).'"':'')
		. ($association->towerCount()?' towerCount="'.$association->towerCount().'"':'')
		. '>'
		. fXML::encode( $association->name() )
		. "</association>\n";
}
?>
</associations>
