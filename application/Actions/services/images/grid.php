<?php
namespace Blueline;
use Pan\Exception, Pan\View, Flourish\fRequest, Helpers\PlaceNotation, Models\Method;

// No optional arguments
if( isset( $arguments[0] ) ) {
	throw new Exception( 'Not found', 404 );
}

// Basic properties
if( ! isset( $_GET['notation'], $_GET['stage'] ) ) { throw new Exception( 'Bad arguments', 400 ); }
$method = new Method();
$method->stage = fRequest::get( 'stage', 'integer' );
$method->notation = urldecode( $_GET['notation'] );

// Dimensions
$rowHeight = intval( isset( $_GET['rowHeight'] )? $_GET['rowHeight'] : 15 );
$rowWidth = intval( isset( $_GET['rowWidth'] )? $_GET['rowWidth'] : $method->stage()*( isset( $_GET['bellWidth'] )? $_GET['bellWidth'] : 12 ) );
$bellWidth = $rowWidth/$method->stage();

// Colours
$colours = array();
if( isset( $_GET['colours'] ) ) {
	$colours = array_filter( array_map( function( $c ) {
		return strtoupper( '#'.preg_replace( '/[^0-9a-zA-Z]/', '', trim( $c, ' #' ) ) );
	}, explode( ',', urldecode( $_GET['colours'] ) ) ) );
}
else {
	$colours = array( '#11D','#1D1','#D1D', '#DD1', '#1DD', '#306754', '#AF7817', '#F75D59', '#736AFF' );
}
$colours2 = array();
while( count( $colours2 ) <= $method->stage() ) {
	$colours2 = array_merge( $colours2, $colours );
}
$colours = array_slice( $colours2, 0, $method->stage() );

// Line widths
$widths = array();
if( isset( $_GET['widths'] ) ) {
	$widths = array_filter( array_map( function( $w ) {
		return intval( $w );
	}, explode( ',', urldecode( $_GET['widths'] ) ) ) );
}
else {
	$widths = array( 2 );
}
$widths2 = array();
while( count( $widths2 ) <= $method->stage() ) {
	$widths2 = array_merge( $widths2, $widths );
}
$widths = array_slice( $widths2, 0, $method->stage() );

// Paths
$paths = array();
for( $i = 0; $i < $method->stage(); ++$i ) {
	$paths[$i] = 'M'.(($bellWidth * $i) + ($bellWidth / 2)).','.($rowHeight / 2).'l';
	$position = $i;
	$permutations = $method->notationPermutations();
	for( $j = 0; $j < count( $permutations ); ++$j ) {
		$newPosition = array_search( $position, $permutations[$j] );
		$paths[$i] .= (($newPosition-$position)*$bellWidth).','.$rowHeight.' ';
		$position = $newPosition;
	}
}

View::set( array(
	'dimensions' => array(
		'grid' => array(
			'x' => $rowWidth,
			'y' => $rowHeight*($method->lengthOfLead()+1)
		),
		'row' => array(
			'x' => $rowWidth,
			'y' => $rowHeight
		),
		'bell' => array(
			'x' => $bellWidth,
			'y' => $rowHeight
		)
	),
	'colours' => $colours,
	'widths' => $widths,
	'paths' => $paths
) );
