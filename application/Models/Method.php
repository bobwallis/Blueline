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
		return $this->notationExpanded? : '';
	}
	
	public function leadHeadCode() {
		return $this->leadHeadCode? : '';
	}
	
	public function leadHead() {
		if( $this->leadHead ) {
			return $this->leadHead;
		}
		elseif( $this->leadHeadCode && $this->stage ) {
			return \Helpers\LeadHeadCodes::fromCode( $this->leadHeadCode, $this->stage )? : '';
		}
		else {
			return '';
		}
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
		return $this->lengthOfLead? : 0;
	}
	
	public function numberOfHunts() {
		return $this->numberOfHunts? : 0;
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
