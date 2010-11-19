<?php
namespace Models;
use \Blueline\Config;

class Association extends \Blueline\Model {
	public function __toString() {
		return $this->name();
	}
	
	public function abbreviation() {
		return $this->abbreviation?:'';
	}
	
	public function name() {
		return $this->name? : '';
	}
	
	public function link() {
		return $this->link? : '';
	}
	
	public function towerCount() {
		return $this->towerCount? intval( $this->towerCount ) : 0;
	}
	
	public function affiliatedTowers() {
		return $this->affiliatedTowers? : array();
	}
	
	public function bbox() {
		return array( 'lat_max' => $this->lat_max, 'lat_min' => $this->lat_min, 'long_max' => $this->long_max, 'long_min' => $this->long_min );
	}
	
	public function href( $absolute = false ) {
		return ($absolute?Config::get( 'site.baseURL' ):'').'/associations/view/'.urlencode( $this->abbreviation() );
	}
}
