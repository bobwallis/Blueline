<?php
namespace Models;
use \Blueline\Config;

class Method extends \Blueline\Model {
	public function __toString() {
		return $this->title();
	}
	
	function title() {
		return $this->title? : '';
	}
	
	function stage() {
		return $this->stage? : 0;
	}
	
	function classification() {
		return $this->classification? : '';
	}
	
	function notation() {
		return $this->notation? : '';
	}
	
	function notationExpanded() {
		return $this->notationExpanded? : '';
	}
	
	function leadHeadCode() {
		return $this->leadHeadCode? : '';
	}
	
	function leadHead() {
		return $this->leadHead? : '';
	}
	
	function fchGroups() {
		return $this->fchGroups? : '';
	}
	
	function rwRef() {
		return $this->rwRef? : '';
	}
	
	function bnRef() {
		return $this->bnRef? : '';
	}
	
	function tdmmRef() {
		return $this->tdmmRef? : 0;
	}
	
	function pmmRef() {
		return $this->pmmRef? : 0;
	}
	
	function lengthOfLead() {
		return $this->lengthOfLead? : 0;
	}
	
	function numberOfHunts() {
		return $this->numberOfHunts? : 0;
	}
	
	function little() {
		return $this->little? true : false;
	}
	
	function differential() {
		return $this->differential? true : false;
	}
	
	function plain() {
		return $this->plain? true : false;
	}
	
	function trebleDodging() {
		return $this->trebleDodging? true : false;
	}
	
	function palindromic() {
		return $this->palindromic? true : false;
	}
	
	function doubleSym() {
		return $this->doubleSym? true : false;
	}
	
	function rotational() {
		return $this->rotational? true : false;
	}
	
	function firstTowerbellPeal_date() {
		return $this->firstTowerbellPeal_date? : 0;
	}
	
	function firstTowerbellPeal_location() {
		return $this->firstTowerbellPeal_location? : '';
	}
	
	function firstTowerbellPeal_location_doveId() {
		return $this->firstTowerbellPeal_location_doveId? : '';
	}
	
	function firstHandbellPeal_date() {
		return $this->firstHandbellPeal_date? : 0;
	}
	
	function firstHandbellPeal_location() {
		return $this->firstHandbellPeal_location? : '';
	}
	
	function ruleOffs() {
		return $this->ruleOffs? : '';
	}
	
	public function href( $absolute = false ) {
		return ($absolute?Config::get( 'site.baseURL' ):'').'/methods/view/'.urlencode( str_replace( ' ', '_', $this->title() ) );
	}
}
