<?php
namespace Blueline;
use \PDO;

/**
 * Model
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class Model {
	
	
	/**
	 * Gets details of every entry
	 * @return array
	 */
	public static function fullList() {
		$sth = Database::$dbh->prepare( '
			SELECT '.static::$_searchSelect.'
			FROM '.static::$_table.'
			WHERE 1=1
		' );
		$sth->execute();
		return ( $fullList = $sth->fetchAll( PDO::FETCH_ASSOC ) )? $fullList : array();
	}
	
	/**
	 * Performs a search
	 * @return array
	 */
	public static function search() {	
		$sth = Database::$dbh->prepare( '
			SELECT '.static::$_searchSelect.'
			FROM '.static::$_table.'
			WHERE '.self::GETtoWhere().'
			LIMIT '.self::GETtoLimit().'
		' );
		$sth->execute( self::GETtoBindable() );
		return ( $searchData = $sth->fetchAll( PDO::FETCH_ASSOC ) )? $searchData : array();
	}
	/**
	 * Counts the number of rows returned by a search
	 * @return integer
	 */
	public static function searchCount() {
		$sth = Database::$dbh->prepare( '
			SELECT COUNT(*) as count
			FROM '.static::$_table.'
			WHERE '.self::GETtoWhere().'
		' );
		$sth->execute( self::GETtoBindable() );
		return ( $countData = $sth->fetch( PDO::FETCH_ASSOC ) )? $countData['count'] : 0;
	}
	
	
	/**
	 * Prepares a string for use in a LIKE SQL query by removing punctuation and setting the correct wildcards
	 * @access protected
	 * @param string $string 
	 * @return string
	 */
	protected static function prepareStringForLike( $string ) {
		return str_replace(
			array( '*', '?', ',', '.', ' ', '%%' ),
			array( '%', '_', ' ', ' ', '%', '%' ),
			$string
		);
	}
	
	
	/**
	 * Creates the content of the LIMIT part of an SQL request, either by using $_GET variables, or a default
	 * @access protected
	 * @return string
	 */
	protected static function GETtoLimit() {
		return isset( $_GET['from'] )? intval( $_GET['from'] ).','.(intval( $_GET['from'] )+30) : '30';
	}
	protected static function GETtoWhere() {
		if( static::$_searchWhere === false ) {
			self::GETtoWhereandBindable();
		}
		return static::$_searchWhere;
	}
	protected static function GETtoBindable() {
		if( static::$_searchBindable === false ) {
			self::GETtoWhereAndBindable();
		}
		return static::$_searchBindable;
	}
	
	private static function GETtoWhereAndBindable( $conditions = null, $join = 'AND', $bindCount = 0, $return = false ) {
		// Set up variables to store results
		if( $conditions == null ) { $conditions = static::GETtoConditions(); }
		$where = array();
		$bindable = array();
		
		foreach( $conditions as $key => $value ) {
			if( is_array( $value ) ) {
				$nestedConditions = array( 'where' => '', 'bindable' => array() );
				if( strpos( 'AND', $key ) !== false ) { $nestedConditions = self::GETtoWhereAndBindable( $value, 'AND', $bindCount, true ); }
				elseif( strpos( 'OR', $key ) !== false ) { $nestedConditions = self::GETtoWhereAndBindable( $value, 'OR', $bindCount, true ); }
				$where[] = $nestedConditions['where'];
				$bindable = array_merge( $bindable, $nestedConditions['bindable'] );
				$bindCount += count( $nestedConditions['bindable'] );
			}
			elseif( !empty( $value ) && preg_match( '/^(.*) (REGEXP|LIKE|=)$/', $key, $matches ) ) {
				$where[] = $matches[1].' '.$matches[2].' :var'.$bindCount;
				$bindable[':var'.$bindCount] = $value;
				++$bindCount;
			}
		}
		$where = ( count( $where ) == 0) ? '1=1' : '('.implode( ' '.$join.' ', $where ).')';
		
		if( $return ) {
			return array( 'where' => $where, 'bindable' => $bindable );
		}
		else {
			static::$_searchWhere = $where;
			static::$_searchBindable = $bindable;
		}
	}
}
