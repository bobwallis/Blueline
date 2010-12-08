<?php
namespace Models;
use \Blueline\Config;

class Tower extends \Blueline\Model {
	public function __toString() {
		return \Helpers\Text::toList( array( "{$this->place()} ({$this->dedication()})", $this->county(), $this->country() ), ', ', ', ' );
	}
	
	public function doveId() {
		return $this->doveId? : '';
	}
	public function gridReference() {
		return $this->gridReference? : '';
	}
	public function latitude() {
		return $this->latitude? : 0;
	}
	public function longitude() {
		return $this->longitude? : 0;
	}
	public function latitudeSatNav() {
		return $this->latitudeSatNav? : 0;
	}
	public function longitudeSatNav() {
		return $this->longitudeSatNav? : 0;
	}
	public function postcode() {
		return $this->postcode? : '';
	}
	public function country() {
		return $this->country? : '';
	}
	public function county() {
		return $this->county? : '';
	}
	public function diocese() {
		return $this->diocese? : '';
	}
	public function place() {
		return $this->place? : '';
	}
	public function altName() {
		return $this->altName? : '';
	}
	public function dedication() {
		return $this->dedication? : '';
	}
	public function bells() {
		return $this->bells? $this->bells : 0;
	}
	public function weight() {
		return $this->weight? : 0;
	}
	public function weightApprox() {
		return $this->weightApprox? true : false;
	}
	public function weightText() {
		return $this->weightText? : '';
	}
	public function note( $html = false ) {
		return $this->note? str_replace( array( '#', 'b' ), array( '&#x266f;', '&#x266d;' ), $this->note ) : ''; 
	}
	public function hz() {
		return $this->hz? : 0;
	}
	public function practiceNight() {
		return $this->practiceNight? : 0;
	}
	public function practiceStart() {
		return $this->practiceStart? : '';
	}
	public function practiceNotes() {
		return $this->practiceNotes? : '';
	}
	public function groundFloor() {
		return $this->groundFloor? true : false;
	}
	public function toilet() {
		return $this->toilet? true : false;
	}
	public function unringable() {
		return $this->unringable? true : false;
	}
	public function simulator() {
		return $this->simulator? true : false;
	}
	public function overhaulYear() {
		return $this->overhaulYear? : 0;
	}
	public function contractor() {
		return $this->contractor? : '';
	}
	public function tuned() {
		return $this->tuned? : 0;
	}
	public function extraInfo() {
		return $this->extraInfo? : '';
	}
	public function webPage() {
		return $this->webPage? : '';
	}
	
	public function distance() {
		return $this->distance? : 0;
	}
	
	public function firstPeals() {
		return $this->firstPeals? : array();
	}
	
	public function affiliations() {
		return $this->affiliations? : array();
	}
	
	public function nearbyTowers() {
		return $this->nearbyTowers? : array();
	}
	
	public function href( $absolute = false ) {
		return ($absolute?Config::get( 'site.baseURL' ):'').'/towers/view/'.urlencode( $this->doveId() );
	}
}
