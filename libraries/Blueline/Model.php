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
			self::GETtoWhereandBindable();
		}
		return self::$_where;
	}
	private static $_bindable = false;
	protected static function GETtoBindable() {
		if( self::$_bindable === false ) {
			self::GETtoWhereandBindable();
		}
		return self::$_bindable;
	}
	
	private static function GETtoWhereandBindable( $conditions = null, $join = 'AND', $bindCount = 0, $return = false ) {
		// Set up variables to store results
		if( $conditions == null ) { $conditions = static::GETtoConditions(); }
		$where = array();
		$bindable = array();
		
		foreach( $conditions as $key => $value ) {
			if( is_array( $value ) ) {
				$nestedConditions = array( 'where' => '', 'bindable' => array() );
				if( strpos( 'AND', $key ) !== false ) { $nestedConditions = self::GETtoWhereandBindable( $value, 'AND', $bindCount, true ); }
				elseif( strpos( 'OR', $key ) !== false ) { $nestedConditions = self::GETtoWhereandBindable( $value, 'OR', $bindCount, true ); }
				$where[] = $nestedConditions['where'];
				$bindable = array_merge( $bindable, $nestedConditions['bindable'] );
				$bindCount += count( $nestedConditions['bindable'] );
			}
			elseif( !empty( $value ) ) {
				$lefts = explode( ' ', $key );
				switch( count( $lefts ) ) {
					case 1:
						$where[] = $lefts[0].' =  :var'.$bindCount;
						break;
					case 2:
						$where[] = $lefts[0].' '.$lefts[1].' :var'.$bindCount;
						break;
					default;
						break;
				}
				$bindable[':var'.$bindCount] = $value;
				++$bindCount;
			}
		}
		
		$where = '('.implode( ' '.$join.' ', $where ).')';
		
		if( $return ) {
			return array( 'where' => $where, 'bindable' => $bindable );
		}
		else {
			self::$_where = $where;
			self::$_bindable = $bindable;
		}
	}
}
