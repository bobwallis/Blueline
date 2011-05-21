<?php
namespace Models;
use Pan\Config, Pan\Model;

class Method extends Model {
	public function __toString() {
		return $this->title();
	}

	public function title() {
		return $this->title? : '';
	}

	public function stage() {
		return $this->stage? : 0;
	}

	public function stageText() {
		if( !$this->stageText ) {
			if( $this->stage() ) {
				$this->stageText = \Helpers\Stages::toString( $this->stage() );
			}
		}
		return $this->stageText? : '';
	}

	public function classification() {
		return $this->classification? : '';
	}

	public function notation() {
		return $this->notation? : '';
	}

	public function notationExpanded() {
		if( !$this->notationExpanded ) {
			if( $this->stage() && $this->notation() ) {
				$this->notationExpanded = \Helpers\PlaceNotation::expand( $this->stage, $this->notation );
			}
		}
		return $this->notationExpanded? : '';
	}

	public function notationExploded() {
		if( !$this->notationExploded ) {
			if( $this->notationExpanded() ) {
				$this->notationExploded = \Helpers\PlaceNotation::explode( $this->notationExpanded() );
			}
		}
		return $this->notationExploded? : array();
	}

	public function notationPermutations() {
		if( !$this->notationPermutations ) {
			if( $this->stage() && $this->notationExploded() ) {
				$this->notationPermutations = \Helpers\PlaceNotation::explodedToPermutations( $this->stage(), $this->notationExploded() );
			}
		}
		return $this->notationPermutations? : array();
	}

	public function firstLead() {
		if( !$this->firstLead ) {
			if( $this->notation() && $this->stage() ) {
				$this->firstLead = \Helpers\PlaceNotation::apply( $this->notationPermutations(), \Helpers\PlaceNotation::rounds( $this->stage() ) );
			}
		}
		return $this->firstLead? : array();
	}

	public function leadHeadCode() {
		if( !$this->leadHeadCode ) {
			$placeNotation = $this->notationExploded();
			if( $this->leadHead() && $this->stage() && $placeNotation ) {
				$this->leadHeadCode = \Helpers\LeadHeadCodes::toCode( $this->leadHead(), $this->stage(), $this->numberOfHunts(), array_pop( $placeNotation ), array_shift( $placeNotation ) );
			}
		}
		return $this->leadHeadCode? : '';
	}

	public function leadHead() {
		if( !$this->leadHead ) {
			if( $this->leadHeadCode && $this->stage() ) { // Using leadHeadCode() could result in infinte recursion
				$this->leadHead = \Helpers\LeadHeadCodes::fromCode( $this->leadHeadCode, $this->stage() )? : '';
			}
			else {
				$firstLead = $this->firstLead();
				if( $firstLead ) {
					$this->leadHead = implode( array_pop( $firstLead ) );
				}
			}
		}
		return $this->leadHead? : '';
	}
	
	public function leadHeads() {
		if( !$this->leadHeads ) {
			$rounds = \Helpers\PlaceNotation::rounds( $this->stage() );
			$tmp = str_split( $this->leadHead() );
			$leadHeadPermutation = array_map( function( $b ) { return \Helpers\PlaceNotation::bellToInt( $b ) - 1; }, $tmp );
			$leadHeads = array( $tmp );
			while( !\Helpers\PlaceNotation::rowsEqual( $rounds, $tmp ) ) {
				$tmp = \Helpers\PlaceNotation::permute( $tmp, $leadHeadPermutation );
				array_push( $leadHeads, $tmp );
			}
			$this->leadHeads = $leadHeads;
		}
		return $this->leadHeads? : array();
	}

	public function fchGroups() {
		return $this->fchGroups? : '';
	}

	public function rwRef() {
		return $this->rwRef? : '';
	}

	public function bnRef() {
		return $this->bnRef? : '';
	}

	public function tdmmRef() {
		return $this->tdmmRef? : 0;
	}

	public function pmmRef() {
		return $this->pmmRef? : 0;
	}

	public function lengthOfLead() {
		if( !$this->lengthOfLead ) {
			if( $this->notationExploded() ) {
				$this->lengthOfLead = count( $this->notationExploded() );
			}
		}
		return intval( $this->lengthOfLead )? : 0;
	}

	public function numberOfHunts() {
		if( !$this->numberOfHunts ) {
			if( $this->hunts() ) {
				$this->numberOfHunts = count( $this->hunts() );
			}
		}
		return $this->numberOfHunts? : 0;
	}

	public function hunts() {
		if( !$this->hunts ) {
			if( $this->leadHead() ) {
				$hunts = array();
				$leadHead = array_map( function( $n ) { return \Helpers\PlaceNotation::bellToInt( $n ); }, str_split( $this->leadHead() ) );
				for( $i = 0, $iLim = count( $leadHead ); $i < $iLim; ++$i ) {
					if( ($i+1) == $leadHead[$i] ) { array_push( $hunts, $leadHead[$i] ); }
				}
				$this->hunts = $hunts;
			}
		}
		return $this->hunts? : array();
	}

	// This is wrong (to implement)
	public function principalHunts() {
		if( $this->numberOfHunts() == 1 ) {
			return $this->hunts();
		}
	}

	public function little() {
		if( is_null( $this->little ) ) {
			$hunt = array_pop( $this->principalHunts() );
			$positions = $this->firstLead();
			foreach( $positions as &$pos ) {
				$pos = intval( array_search( $hunt, $pos ) );
			}
			$this->little = ( max( $positions ) - min( $positions ) ) < ( $this->stage() - 1 );
		}
		return (boolean) $this->little;
	}

	public function differential() {
		return (boolean) $this->differential;
	}

	public function plain() {
		return (boolean) $this->plain;
	}

	public function trebleDodging() {
		return (boolean) $this->trebleDodging;
	}

	public function palindromic() {
		return (boolean) $this->palindromic;
	}

	public function doubleSym() {
		return (boolean) $this->doubleSym;
	}

	public function rotational() {
		return (boolean) $this->rotational;
	}

	public function firstTowerbellPeal_date() {
		return $this->firstTowerbellPeal_date? : '';
	}

	public function firstTowerbellPeal_location() {
		return $this->firstTowerbellPeal_location? : '';
	}

	public function firstTowerbellPeal_location_doveId() {
		return $this->firstTowerbellPeal_location_doveId? : '';
	}

	public function firstHandbellPeal_date() {
		return $this->firstHandbellPeal_date? : '';
	}

	public function firstHandbellPeal_location() {
		return $this->firstHandbellPeal_location? : '';
	}

	public function ruleOffs() {
		if( is_string( $this->ruleOffs ) ) {
			if( preg_match( '/^([^:]*):([^:]*)$/', $this->ruleOffs, $matches ) && isset( $matches[1], $matches[2] ) ) {
				$this->ruleOffs = array( 'every' => intval( $matches[1] ), 'from' => intval( $matches[2] ) );
			}
		}
		return $this->ruleOffs? : array( 'from' => 0, 'every' => $this->lengthOfLead() );
	}

	public function calls() {
		// Set default calls
		if( !$this->calls ) {
			if( !$this->differential() && $this->stage() > 4 ) {
				$leadEndChange = array_pop( $this->notationExploded() );
				$postLeadEndChange = array_shift( $this->notationExploded() );
				$n = \Helpers\PlaceNotation::intToBell( $this->stage() );
				$n_1 = \Helpers\PlaceNotation::intToBell( $this->stage() - 1 );
				$n_2 = \Helpers\PlaceNotation::intToBell( $this->stage() - 2 );
				switch( $this->numberOfHunts() ) {
				case 0:
					if( $this->stage() % 2 == 0 ) {
						if( $leadEndChange == '1'.$n ) {
							$this->calls = serialize( array( 'Bob' => '1'.$n_2.'::', 'Single' => '1'.$n_2.$n_1.$n.'::' ) );
						}
					}
					else {
					
					}
					break;
				case 1:
					if( $this->stage() % 2 == 0 ) {
						if( $leadEndChange == '12' ) {
							$this->calls = serialize( array( 'Bob' => '14::', 'Single' => '1234::' ) );
						}
						elseif( $leadEndChange == '1'.$n ) {
							if( $this->leadHeadCode() == 'm' ) {
								$this->calls = serialize( array( 'Bob' => '14::', 'Single' => '1234::' ) );
							}
							else {
								$this->calls = serialize( array( 'Bob' => '1'.$n_2.'::', 'Single' => '1'.$n_2.$n_1.$n.'::' ) );
							}
						}
					}
					else {
						if( $leadEndChange == '12'.$n || $leadEndChange == '1' ) {
							$this->calls = serialize( array( 'Bob' => '14'.$n.'::', 'Single' => (($this->stage()<6)?'123':'1234'.$n).'::' ) );
						}
						elseif( $leadEndChange == '123' ) {
							$this->calls = serialize( array( 'Bob' => '12'.$n.'::' ) );
						}
					}
					break;
				case 2:
					// Bobs and singles for Grandsire and Single Court like lead ends
					if( $leadEndChange == '1' && ( $postLeadEndChange == '3' || $postLeadEndChange == $n ) ) {
						$this->calls = serialize( array( 'Bob' => '3.1::-1', 'Single' => '3.23::-1' ) );
					}
					break;
				default:
					$this->calls = serialize( array() );
				}
			}
		}
		// Unserialize
		if( is_string( $this->calls ) ) {
			$this->calls = unserialize( $this->calls );
		}
		// Parse the format
		$calls = $this->calls;
		if( count( $calls ) > 0 ) {
			foreach( $calls as $title => &$call ) {
				if( is_string( $call ) ) {
					if( preg_match( '/^([^:]*):([^:]*):([^:]*)$/', $call, $matches ) && isset( $matches[1] ) ) {
						$call = array( 'notation' => $matches[1], 'every' => intval( $matches[2]?:$this->lengthOfLead() ), 'from' => intval( $matches[3] )?:0 );
					}
					else {
						unset( $calls[$title] );
					}
				}
			}
			$this->calls = $calls;
		}
		return $this->calls? : array();
	}

	public function callingPositions() {
		if( !$this->callingPositions ) {
			$stage = $this->stage();
			$calls = $this->calls();
			$lengthOfLead = $this->lengthOfLead();
			if( $stage < 6 || empty( $calls ) || !isset( $calls['Bob'] ) ) {
				$this->callingPositions = array();
			}
			else {
				// Calling positions for calls at lead ends (Home, Wrong and so forth)
				$bobNotation = \Helpers\PlaceNotation::explodedToPermutations( $stage, \Helpers\PlaceNotation::explode( $calls['Bob']['notation'] ), $stage );
				if( $calls['Bob']['every'] == $lengthOfLead && $calls['Bob']['from'] == 0 && count( $bobNotation ) == 1 ) {
					$leadHeads = $this->leadHeads();
					// Work out what the lead end of a bobbed lead looks like
					$notation = $this->notationPermutations();
					$notation[$lengthOfLead-1] = $bobNotation[0];
					$bobbedLead = \Helpers\PlaceNotation::apply( $notation, \Helpers\PlaceNotation::rounds( $this->stage() ) );
					$bobbedLeadHeadPermutation = array_map( function( $b ) { return \Helpers\PlaceNotation::bellToInt( $b ) - 1; }, array_pop( $bobbedLead ) );
					// Collect an array of what happens at each lead if a bob is called
					$bobbedLeadHeads = array( \Helpers\PlaceNotation::permute( \Helpers\PlaceNotation::rounds( $stage ), $bobbedLeadHeadPermutation ) );
					for( $i = 1; $i < count( $leadHeads ); $i++ ) {
						array_push( $bobbedLeadHeads, \Helpers\PlaceNotation::permute( $leadHeads[$i-1], $bobbedLeadHeadPermutation ) );
					}
					// Convert the array of lead heads into calling position names
					$this->callingPositions = array( 'from' => 0, 'every' => $lengthOfLead, 'titles' => array_map( function( $leadEnd ) use( $stage ) {
						$position = array_search( \Helpers\PlaceNotation::intToBell( $stage ), $leadEnd );
						switch( $position+1 ) {
							case 2:
								return 'I';
							case 3:
								return 'B';
							case 4:
								return 'F';
							case $stage-2:
								return 'M';
							case $stage-1:
								return 'W';
							case $stage:
								return 'H';
							case 5:
								return 'V';
						}
						return null;
					}, $bobbedLeadHeads ) );
				}
			}
		}
		return $this->callingPositions? : array();
	}

	public function href( $absolute = false ) {
		return ($absolute?Config::get( 'site.baseURL' ):'').'/methods/view/'.urlencode( str_replace( ' ', '_', $this->title() ) );
	}
}
