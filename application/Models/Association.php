<?php
namespace Models;
use Pan\Config, Pan\Model;

class Association extends Model {
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
		if( !$this->towerCount ) {
			if( $this->affiliatedTowers && ( is_array( $this->affiliatedTowers ) || $this->affiliatedTowers instanceof Countable ) ) {
				$this->towerCount = count( $this->affiliatedTowers );
			}
		}
		return $this->towerCount? : 0;
	}

	public function affiliatedTowers() {
		return $this->affiliatedTowers? : array();
	}

	public function bbox() {
		return array(
			'lat_max' => $this->lat_max?:false,
			'lat_min' => $this->lat_min?:false,
			'long_max' => $this->long_max?:false,
			'long_min' => $this->long_min?:false
		);
	}

	public function href( $absolute = false ) {
		return ($absolute?Config::get( 'site.baseURL' ):'').'/associations/view/'.urlencode( $this->abbreviation() );
	}
}
