<?php
namespace Blueline;
use Pan\View;

View::cache( true );

foreach( $this->get( 'methods', array() ) as $method ) {
	$references = array();
	if( $method->rwRef() ) { $references[] = 'Ringing World '.$method->rwRef(); }
	if( $method->bnRef() ) { $references[] = 'Bell News '.$method->bnRef(); }
	$performances = array();
	if( $method->firstTowerbellPeal_date() ) {
		$performances[] = 'First tower bell peal: '.$method->firstTowerbellPeal_date().($method->firstTowerbellPeal_location()?' at '.$method->firstTowerbellPeal_location():'');
	}
	if( $method->firstHandbellPeal_date() ) {
		$performances[] = 'First hand bell peal:  '.$method->firstHandbellPeal_date().($method->firstHandbellPeal_location()?' at '.$method->firstHandbellPeal_location():'');
	}

	echo $method->title()."\n";

	if( $method->pmmRef() ) {
		echo 'Plain Minor Method #'.$method->pmmRef()."\n";
	}
	if( $method->tdmmRef() ) {
		echo 'Treble-Dodging Minor Method #'.$method->tdmmRef()."\n";
	}

	echo '       Notation: '.$method->notation()."\n";

	if( $method->lengthOfLead() ) {
		echo ' Length of Lead: '.$method->lengthOfLead()."\n";
	}

	if( $method->numberOfHunts() ) {
		echo 'Number of Hunts: '.$method->numberOfHunts()."\n";
	}

	if( $method->palindromic() || $method->doubleSym() || $method->rotational() ) {
		echo '       Symmetry: ' . \Helpers\Text::toList( array_filter( array( ($method->palindromic()?'Palindromic':''), ($method->doubleSym()?'Double':''), ($method->rotational()?'Rotational':'') ) ) )."\n";
	}

	if( $method->leadHead() ) {
		echo '      Lead Head: '.$method->leadHead().($method->leadHeadCode()?' (Code: '.$method->leadHeadCode().')':'')."\n";
	}

	if( $method->fchGroups() ) {
		echo '      Falseness: '.$method->fchGroups()."\n";
	}

	if( count( $references ) > 0 ) {
		echo '     References: '.implode( "\n                 ", $references )."\n";
	}

	if( count( $performances ) > 0 ) {
		echo '   Performances: '.implode( "\n                 ", $performances )."\n";
	}

	echo "\n\n";
}
