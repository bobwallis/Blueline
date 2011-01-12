<?php
namespace Blueline;
use \PDO;

/**
 * Data access
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class DataAccess {
	
	public static function find( $options = array() ) {
		// Merge the options array with some defaults to stop choking later on
		foreach( array( 'table', 'fields', 'join', 'left_outer_join', 'where', 'having', 'group_by', 'order', 'limit', 'model', 'bind' ) as $key ) {
			if( !array_key_exists( $key, $options ) ) {
				$options[$key] = false;
			}	
		}
		$bindable = $options['bind']?:array();
		
		// Prepare the find conditions
		list( $where, $whereBindable ) = self::conditionsToWhereBindable( $options['where'], 'where' );
		list( $having, $havingBindable ) = self::conditionsToWhereBindable( $options['having'], 'having' );
		$bindable = array_merge( $bindable, $whereBindable, $havingBindable );
		
		// Prepare any joins
		$joinsArray = array();
		$i = 0;
		foreach( $options['join']?:array() as $table => $on ) {
			list( $joinOn, $joinBindable ) = self::conditionsToWhereBindable( $on, 'join'.$i );
			$joinsArray[] = "JOIN {$table} ON {$joinOn}";
			$bindable = array_merge( $bindable, $joinBindable );
			$i++;
		}
		$joins = implode( "\n", $joinsArray );
		$joinsArray = array();
		foreach( $options['left_outer_join']?:array() as $table => $on ) {
			list( $joinOn, $joinBindable ) = self::conditionsToWhereBindable( $on, 'join'.$i );
			$joinsArray[] = "LEFT OUTER JOIN {$table} ON {$joinOn}";
			$bindable = array_merge( $bindable, $joinBindable );
			$i++;
		}
		$outerJoins = implode( "\n", $joinsArray );
		
		$sth = Database::$dbh->prepare( '
			SELECT '.implode( ', ', $options['fields']?:static::$_fields ).'
			FROM '.( $options['table']?:static::$_table ).'
			'.$joins.'
			'.$outerJoins.'
			'.(($where!='1')?'WHERE '.$where:'').'
			'.(($having!='1')?'HAVING '.$having:'').'
			'.( $options['group_by']? 'GROUP BY :groupBy' : '' ).'
			'.( $options['order']? 'ORDER BY '.$options['order'] : '' ).'
			'.( $options['limit']? 'LIMIT '.$options['limit'] : '' ).'
		' );
		$bindable[':groupBy'] = $options['group_by'];
		$sth->execute( array_filter( $bindable ) );
		
		$model = $options['model']?:static::$_model;
		if( is_string( $model ) ) {
			return ( $data = $sth->fetchAll( PDO::FETCH_CLASS, $model ) )? $data : array();
		}
		else {
			$sth->setFetchMode( PDO::FETCH_INTO, $model );
			return $sth->fetch( PDO::FETCH_INTO );
		}
	}
	
	public static function findOne( $options = array() ) {
		$model = array_key_exists( 'model', $options )? $options['model'] :static::$_model;
		return array_pop( static::find( array_merge( $options, array( 'limit' => 1 ) ) ) )? : new $model;
	}
	
	public static function findCount( $options = array() ) {
		$result = array_pop( static::find( array_merge( $options, array( 'fields' => array( 'COUNT(*) as count' ), 'limit' => false ) ) ) );
		return $result->count;
	}
	
	public static function findInto( $model, $options = array() ) {
		return static::find( array_merge( $options, array( 'model' => $model ) ) );
	}
	
	
	private static function conditionsToWhereBindable( $conditions, $varPrefix = '', $join = 'AND', $bindCount = 0 ) {
		if( is_array( $conditions ) && $conditions ) {
			$where = array(); // Implode this to a string later
			$bindable = array();
			foreach( $conditions as $key => $value ) {
				if( $value == null ) {
					$where[] = $key;
				}
				elseif( is_array( $value ) ) {
					list( $where[], $nestedBindable ) = self::conditionsToWhereBindable( $value, $varPrefix, $key, $bindCount );
					$bindable = array_merge( $bindable, $nestedBindable );
					$bindCount += count( $nestedBindable );
				}
				elseif( !empty( $value ) && preg_match( '/^(.*) (REGEXP|LIKE|=)$/', $key, $matches ) ) {
					$where[] = "{$matches[1]} {$matches[2]} :{$varPrefix}var{$bindCount}";
					$bindable[":{$varPrefix}var{$bindCount}"] = $value;
					++$bindCount;
				}
			}
			$where = ( count( $where ) == 0) ? '1' : '('.implode( ' '.$join.' ', $where ).')';
			return array( $where, $bindable );
		}
		else {
			return array( '1', array() );
		}
	}
	
	

	public static $_GETsearchLimit = false;
	public static $_searchLimit = 30;
	public static function GETtoLimit() {
		self::$_GETsearchLimit = self::$_GETsearchLimit?: ( isset( $_GET['count'] )? intval( $_GET['count'] ) : self::$_searchLimit );
		return ( isset( $_GET['from'] )? intval( $_GET['from'] ) : '0' ) . ','.self::$_GETsearchLimit;
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
}
