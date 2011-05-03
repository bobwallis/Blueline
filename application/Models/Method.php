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
				$this->firstLead = \Helpers\PlaceNotation::apply( $this->notationPermutations(), array_map( array( 'Helpers\PlaceNotation', 'intToBell' ), range( 1, $this->stage() ) ) );
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
		return $this->lengthOfLead? : 0;
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
		return ($this->little === true)? true : false;
	}

	public function differential() {
		return ($this->differential === true)? true : false;
	}

	public function plain() {
		return ($this->plain === true)? true : false;
	}

	public function trebleDodging() {
		return ($this->trebleDodging === true)? true : false;
	}

	public function palindromic() {
		return ($this->palindromic === true)? true : false;
	}

	public function doubleSym() {
		return ($this->doubleSym === true)? true : false;
	}

	public function rotational() {
		return ($this->rotational === true)? true : false;
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
				$stageNotation = \Helpers\PlaceNotation::intToBell( $this->stage() );
				switch( $this->numberOfHunts() ) {
				case 1:
					if( $this->stage() % 2 == 0 ) {
						if( $leadEndChange == '12' ) {
							$this->calls = serialize( array( 'Bob' => '14::', 'Single' => '1234::' ) );
						}
						elseif( $leadEndChange == '1'.$stageNotation ) {
							if( $this->leadHeadCode() == 'm' ) {
								$this->calls = serialize( array( 'Bob' => '14::', 'Single' => '1234::' ) );
							}
							else {
								$this->calls = serialize( array( 'Bob' => '1'.\Helpers\PlaceNotation::intToBell( $this->stage() - 2 ).'::', 'Single' => '1'.\Helpers\PlaceNotation::intToBell( $this->stage() - 2 ).\Helpers\PlaceNotation::intToBell( $this->stage() - 1 ).$stageNotation.'::' ) );
							}
						}
					}
					else {
						if( $leadEndChange == '12'.$stageNotation ) {
							$this->calls = serialize( array( 'Bob' => '14'.$stageNotation.'::', 'Single' => (($this->stage()<6)?'123':'1234'.$stageNotation).'::' ) );
						}
						elseif( $leadEndChange == '1'.\Helpers\PlaceNotation::intToBell( $this->stage() - 1 ).$stageNotation ) {
							if( $this->leadHeadCode() == 'm' ) {
							$this->calls = serialize( array( 'Bob' => '14'.$stageNotation.'::', 'Single' => (($this->stage()<6)?'123':'1234'.$stageNotation).'::' ) );
							}
							else {
								$this->calls = serialize( array( 'Bob' => '1'.\Helpers\PlaceNotation::intToBell( $this->stage() - 3 ).\Helpers\PlaceNotation::intToBell( $this->stage() - 2 ).'::', 'Single' => '1'.\Helpers\PlaceNotation::intToBell( $this->stage() - 3 ).\Helpers\PlaceNotation::intToBell( $this->stage() - 2 ).\Helpers\PlaceNotation::intToBell( $this->stage() - 1 ).$stageNotation.'::' ) );
							}
						}
					}
					break;
				case 2:
					// Bobs and singles for Grandsire-like lead ends
					if( $leadEndChange == '1' && $postLeadEndChange == '3' ) {
						$this->calls = serialize( array( 'Bob' => '3::-1', 'Single' => '3.23::-1' ) );
					}
					// Bobs and singles for Single Court-like lead ends
					if( $leadEndChange == '1' && $postLeadEndChange == $stageNotation ) {
						$this->calls = serialize( array( 'Bob' => '3::-1', 'Single' => '3.23::-1' ) );
					}
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
					if( preg_match( '/^([^:]*):([^:]*):([^:]*)$/', $call, $matches ) && isset( $matches[1], $matches[2], $matches[3] ) ) {
						$call = array( 'notation' => $matches[1], 'every' => intval( $matches[2] ), 'from' => intval( $matches[3] ) );
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

	public function href( $absolute = false ) {
		return ($absolute?Config::get( 'site.baseURL' ):'').'/methods/view/'.urlencode( str_replace( ' ', '_', $this->title() ) );
	}
}
