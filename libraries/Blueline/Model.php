<?php
namespace Blueline;

/**
 * Model
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class Model {

	protected static function GETtoLimit() {
		return isset( $_GET['from'] )? intval( $_GET['from'] ).','.(intval( $_GET['from'] )+30) : 30;
	}
	private static $_where = false;
	protected static function GETtoWhere() {
		if( self::$_where === false ) {
			$whereArray = array();
			foreach( static::GETtoConditions() as $key => $value ) {
				$lefts = explode( ' ', $key );
				switch( count( $lefts ) ) {
					case 1:
						$whereArray[] = str_replace( ':', '', $lefts[0] ).' = '.$lefts[0];
						break;
					case 2:
						$whereArray[] = str_replace( ':', '', $lefts[0] ).' '.$lefts[1].' '.$lefts[0];
						break;
					default;
						break;
				}
			}
			self::$_where = implode( ' AND ', $whereArray );
		}
		return self::$_where;
	}
	private static $_bindable = false;
	protected static function GETtoBindable() {
		if( self::$_bindable === false ) {
			$bindable = array();
			foreach( static::GETtoConditions() as $key => $value ) {
				$lefts = explode( ' ', $key );
				$bindable[$lefts[0]] = $value;
			}
			self::$_bindable = $bindable;
		}
		return self::$_bindable;
	}
}
