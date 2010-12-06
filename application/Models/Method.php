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
		return $this->stage? \Helpers\Stages::fromInt( $this->stage ) : '';
	}
	
	public function classification() {
		return $this->classification? : '';
	}
	
	public function notation() {
		return $this->notation? : '';
	}
	
	public function notationExpanded() {
		if( !$this->notationExpanded ) {
			$this->notationExpanded = \Helpers\PlaceNotation::expand( $this->stage, $this->notation );
		}
		return $this->notationExpanded? : '';
	}
	
	public function notationExploded() {
		if( !$this->notationExploded ) {
			$this->notationExploded = \Helpers\PlaceNotation::explode( $this->notationExpanded() );
		}
		return $this->notationExploded? : array();
	}
	
	public function notationPermutations() {
		if( !$this->notationPermutations ) {
			$this->notationPermutations = \Helpers\PlaceNotation::explodedToPermutations( $this->stage(), $this->notationExploded() );
		}
		return $this->notationPermutations? : array();
	}
	
	public function firstLead() {
		if( !$this->firstLead ) {
			$this->firstLead = \Helpers\PlaceNotation::apply( $this->notationPermutations(), range( 1, $this->stage() ) );
		}
		return $this->firstLead? : array();
	}
	
	public function leadHeadCode() {
		if( !$this->leadHeadCode ) {
			$placeNotation = $this->notationExploded();
			$this->leadHeadCode = \Helpers\LeadHeadCodes::toCode( $this->leadHead(), $this->stage(), array_pop( $placeNotation ), array_shift( $placeNotation ) );
		}
		return $this->leadHeadCode? : '';
	}
	
	public function leadHead() {
		if( !$this->leadHead ) {
			if( $this->leadHeadCode && $this->stage ) {
				$this->leadHead = \Helpers\LeadHeadCodes::fromCode( $this->leadHeadCode, $this->stage )? : '';
			}
			else {
				$firstLead = $this->firstLead();
				$this->leadHead = implode( array_pop( $firstLead ) );
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
			$this->lengthOfLead = count( $this->notationExploded() );
		}
		return $this->lengthOfLead? : 0;
	}
	
	public function numberOfHunts() {
		if( !$this->numberOfHunts ) {
			$this->numberOfHunts = count( $this->hunts() );
		}
		return $this->numberOfHunts? : 0;
	}
	
	public function hunts() {
		if( !$this->hunts ) {
			$hunts = array();
			$leadHead = array_map( function( $n ) { return \Helpers\PlaceNotation::bellToInt( $n ); }, str_split( $this->leadHead() ) );
			for( $i = 0, $iLim = count( $leadHead ); $i < $iLim; ++$i ) {
				if( ($i+1) == $leadHead[$i] ) { array_push( $hunts, $leadHead[$i] ); }
			}
			$this->hunts = $hunts;
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
		return $this->firstTowerbellPeal_date? : 0;
	}
	
	public function firstTowerbellPeal_location() {
		return $this->firstTowerbellPeal_location? : '';
	}
	
	public function firstTowerbellPeal_location_doveId() {
		return $this->firstTowerbellPeal_location_doveId? : '';
	}
	
	public function firstHandbellPeal_date() {
		return $this->firstHandbellPeal_date? : 0;
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
