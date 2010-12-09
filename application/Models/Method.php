<?php
namespace Models;
use \Blueline\Config;

class Method extends \Blueline\Model {
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
	
	public function little() {
		return $this->little? true : false;
	}
	
	public function differential() {
		return $this->differential? true : false;
	}
	
	public function plain() {
		return $this->plain? true : false;
	}
	
	public function trebleDodging() {
		return $this->trebleDodging? true : false;
	}
	
	public function palindromic() {
		return $this->palindromic? true : false;
	}
	
	public function doubleSym() {
		return $this->doubleSym? true : false;
	}
	
	public function rotational() {
		return $this->rotational? true : false;
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
		return $this->ruleOffs? : '';
	}
	
	public function href( $absolute = false ) {
		return ($absolute?Config::get( 'site.baseURL' ):'').'/methods/view/'.urlencode( str_replace( ' ', '_', $this->title() ) );
	}
}
