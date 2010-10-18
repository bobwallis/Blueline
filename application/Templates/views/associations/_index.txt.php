<?php
$abbreviationMax = max( array_map( function( $a ){ return strlen( $a['abbreviation'] ); }, $associations ) ) + 2;
$nameMax = max( array_map( function( $a ){ return strlen( $a['name'] ); }, $associations ) ) + 2;

echo "Associations\n\n";
echo 'Abbr.'.str_repeat( ' ', $abbreviationMax - 5 ).'Name:'.str_repeat( ' ', $nameMax - 5 )."Link:\n";
foreach( $associations as $association ) {
	echo $association['abbreviation']
		. str_repeat( ' ', $abbreviationMax-strlen( $association['abbreviation'] ) ) . $association['name']
		. ( !empty( $association['link'] )? str_repeat( ' ', $nameMax - strlen( $association['name'] ) ).'<'.$association['link'].'>' : '' )."\n";
}
