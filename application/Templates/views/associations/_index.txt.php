<?php
namespace Blueline;
use Pan\View;

View::cache( true );

$abbreviationMax = max( array_map( function( $a ){ return strlen( $a->abbreviation() ); }, $this->get( 'associations', array() ) ) ) + 2;
$nameMax = max( array_map( function( $a ){ return strlen( $a->name() ); }, $this->get( 'associations', array() ) ) ) + 2;

echo "Associations\n\n";
echo 'Abbr.'.str_repeat( ' ', $abbreviationMax - 5 ).'Name:'.str_repeat( ' ', $nameMax - 5 )."Link:\n";
foreach( $this->get( 'associations', array() ) as $association ) {
	echo $association->abbreviation()
		. str_repeat( ' ', $abbreviationMax-strlen( $association->abbreviation() ) ) . $association->name()
		. ( $association->link()? str_repeat( ' ', $nameMax - strlen( $association->name() ) ).'<'.$association->link().'>' : '' )."\n";
}
