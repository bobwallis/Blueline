<?php
foreach( $methods as $m ) {
	$references = array();
	foreach( array( 'rwRef' => 'Ringing World', 'bnRef' => 'Bell News' ) as $key=>$journal ) {
		if( !empty( $m[$key] ) ) { $references[] = $journal.' '.$m[$key]; }
	}
	$performances = array();
	if( !empty( $m['firstTowerbellPeal_date'] ) ) {
		$performances[] = 'First tower bell peal: '.$m['firstTowerbellPeal_date'].((!empty($m['firstTowerbellPeal_location']))?' at '.$m['firstTowerbellPeal_location']:'');
	}
	if( !empty( $m['firstHandbellPeal_date'] ) ) {
		$performances[] = 'First hand bell peal:  '.$m['firstHandbellPeal_date'].((!empty($m['firstHandbellPeal_location']))?' at '.$m['firstHandbellPeal_location']:'');
	}

	echo $m['title']."\n";

	if( !empty( $m['pmmRef'] ) ) {
		echo 'Plain Minor Method #'.$m['pmmRef']."\n";
	}
	if( !empty( $m['tdmmRef'] ) ) {
		echo 'Treble-Dodging Minor Method #'.$m['tdmmRef']."\n";
	}

	echo '       Notation: '.$m['notation']."\n";

	if( !empty( $m['lengthOfLead'] ) ) {
		echo ' Length of Lead: '.$m['lengthOfLead']."\n";
	}

	if( !empty( $m['numberOfHunts'] ) ) {
		echo 'Number of Hunts: '.$m['numberOfHunts']."\n";
	}

	if( !empty( $m['palindromic'] ) || !empty( $m['doubleSym'] ) || !empty( $m['rotational'] ) ) {
		echo '       Symmetry: ' . \Helpers\Text::toList( array_filter( array( (($m['palindromic']==1)?'Palindromic':''), (($m['doubleSym']==1)?'Double':''), (($m['rotational']==1)?'Rotational':'') ) ) )."\n";
	}

	if( !empty( $m['leadHead'] ) ) {
		echo '      Lead Head: '.$m['leadHead'].((!empty( $m['leadHeadCode'] ))?' (Code: '.$m['leadHeadCode'].')':'')."\n";
	}

	if( !empty( $m['fchGroups'] ) ) {
		echo '      Falseness: '.$m['fchGroups']."\n";
	}

	if( count( $references ) > 0 ) {
		echo '     References: '.implode( "\n                 ", $references )."\n";
	}

	if( count( $performances ) > 0 ) {
		echo '   Performances: '.implode( "\n                 ", $performances )."\n";
	}

	echo "\n\n";
}
