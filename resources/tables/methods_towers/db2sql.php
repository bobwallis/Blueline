<?php
namespace Utilities;
require( dirname(dirname(dirname(dirname(__FILE__)))).'/vendor/blueline/abbreviations.php' );
use \PDO, \Helpers;

$dsn = 'mysql:host=localhost;dbname=blueline';
$username = 'blueline';
$password = 'password';
?>
-- Method to Tower links
-- Generated on: <?php echo date( 'Y/m/d' ); ?>

-- Set up methods_towers table
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

DROP TABLE IF EXISTS `methods_firsttowerbellpeal_tower`;
CREATE TABLE IF NOT EXISTS `methods_firsttowerbellpeal_tower` (
  `method` varchar(255) NOT NULL COMMENT 'Title of method',
  `firstTowerbellPeal_tower` varchar(10) NOT NULL COMMENT 'Dove ID of the tower where the first tower bell peal was rung',
  PRIMARY KEY (`method`,`firstTowerbellPeal_tower`),
  UNIQUE KEY `method` (`method`),
  KEY `tower_doveId` (`firstTowerbellPeal_tower`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

<?php
$notFound = array();
try {
	$dbh = new PDO( $dsn, $username, $password );
	
	$methodSearch = $dbh->prepare( 'SELECT title, stage, firstTowerbellPeal_location as location from methods WHERE firstTowerbellPeal_location IS NOT NULL' );
	$methodSearch->execute();
	
	foreach( $methodSearch->fetchAll( PDO::FETCH_ASSOC ) as $method ) {
		$location = $method['location'];
		$minBells = $method['stage'];
		$method = $method['title'];
		$doveId = '';
		$location = trim( str_replace( '-', ' ', $location ) );
		
		// Place
		if( strpos( $location, ',' ) === FALSE ) {
			if( $location == 'Barnsley' ) { $doveId = 'BARNSLEY_Y'; }
			elseif( $location == 'Barrow on Humber' ) { $doveId = 'BARROW_UH'; }
			elseif( $location == 'Birstall' ) { $doveId = 'BIRSTALL_Y'; }
			elseif( $location == 'Bishop\'s Tawton' ) { $doveId = 'BISHOPS_TA'; }
			elseif( $location == 'Bishops Cleeve' ) { $doveId = 'BISHOPS_6'; }
			elseif( $location == 'Collingham' ) { $doveId = 'COLLINGHAM'; }
			elseif( $location == 'Farnworth with Kearsley' ) { $doveId = 'FARNWORTHK'; }
			elseif( $location == 'Great Berkhamsted' ) { $doveId = 'BERKHAMSTE'; }
			elseif( $location == 'Lye' ) { $doveId = 'LYE'; }
			elseif( $location == 'Michaelston y Fedw' ) { $doveId = 'MICHAELSTN'; }
			elseif( $location == 'Pulham St Mary Magd.' ) { $doveId = 'PULHAM_MAR'; }
			elseif( $location == 'Pulham St Mary the Virgin' ) { $doveId = 'PULHAM_ST'; }
			elseif( $location == 'Scofton with Osberton' ) { $doveId = 'SCOFTON'; }
			elseif( $location == 'Wath on Dearne' ) { $doveId = 'WATH_UPOND'; }
			
			elseif( ! $doveId = search( array( 'minBells' => $minBells, 'place' => $location ), $dbh ) ) {
				$notFound[] = $location.' : '.$method;
			}
		}
		else {
			$locationE = explode( ', ', $location );
			
			switch( count( $locationE ) ) {
			case 2:
				// Random special case which is like (County), Place
				if( strpos( $locationE[0], '(' ) !== false && preg_match( '/^\((.*)\)$/', $locationE[0], $match ) ) {
					$locationE[0] = $locationE[1];
					$locationE[1] = $match[1];
				}
				// Other special cases encountered when the dedication or place don't quite match between CCCBR method and tower data
				$locationE[0] = str_replace( array(
					'Art Centre',
					'Bellfoundry'
				), array(
					'Arts Centre',
					'Bell Foundry',
				), $locationE[0] );
				if( $locationE[1] == 'Milton Keynes' && $locationE[0] == 'All Saints' ) { $doveId = 'MILTONKY21'; break; }
				if( $locationE[1] == 'Dublin' && $locationE[0] == 'Christ Church Cathedral' ) { $doveId = 'DUBLIN__DC'; break; }
				if( $locationE[1] == 'Ealing' && $locationE[0] == 'Christ Church' ) { $doveId = 'EALING'; break; }
				if( $locationE[0] == 'Christchurch' && $locationE[1] == 'Hants' ) { $doveId = 'CHRISTCH_D'; break; }
				if( $locationE[0] == 'St Michael with St Paul' && $locationE[1] == 'Bath' ) { $doveId = 'BATH___MIC'; break; }
				if( $locationE[0] == 'St Michael Coslany' && $locationE[1] == 'Norwich' ) { $doveId = 'NORWICH_MI'; break; }
				if( $locationE[0] == 'St Mary' && $locationE[1] == 'Spalding' ) { $doveId = 'SPALDING'; break; }
				if( $locationE[0] == 'St Mary' && $locationE[1] == 'Hayes' ) { $doveId = 'HAYES_MRY'; break; }
				if( $locationE[0] == 'St Mary Magdalene' && $locationE[1] == 'Torquay' ) { $doveId = 'TORQUAY_UP'; break; }
				if( $locationE[0] == 'St Mary Magdalene' && $locationE[1] == 'Bridgnorth' ) { $doveId = 'BRIDGNOR_M'; break; }
				if( $locationE[0] == 'St Margaret of Antioch' && $locationE[1] == 'Uxbridge' ) { $doveId = 'UXBRIDGE_M'; break; }
				if( $locationE[0] == 'St Machar\'s Cathedral' && $locationE[1] == 'Aberdeen' ) { $doveId = 'ABERDEEN'; break; }
				if( $locationE[0] == 'St Laurence' && $locationE[1] == 'York' ) { $doveId = 'YORK___SLA'; break; }
				if( $locationE[0] == 'St John in Bedwardine' && $locationE[1] == 'Worcester' ) { $doveId = 'WORCESTERB'; break; }
				if( $locationE[0] == 'St John at Hackney' && $locationE[1] == 'Hackney' ) { $doveId = 'HACKNEY'; break; }
				if( $locationE[0] == 'St John Bapt.' && $locationE[1] == 'Newcastle upon Tyne' ) { $doveId = 'NEWCASUT_J'; break; }
				if( $locationE[0] == 'Newport' && $locationE[1] == 'Gwent' ) { $doveId = 'NEWPORT_NC'; break; }
				if( $locationE[0] == 'Marshfield' && $locationE[1] == 'Mon' ) { $doveId = 'MARSHFLD_G'; break; }
				if( $locationE[0] == 'Llanbadarn Fawr' && $locationE[1] == 'Dyfed' ) { $doveId = 'LLANBADARA'; break; }
				if( $locationE[0] == 'Cape Town' && $locationE[1] == 'Woodstock' ) { $doveId = 'CAPE_TOWNW'; break; }
				
				// Dedication, Place
				if( $doveId = search( array( 'minBells' => $minBells, 'dedication' => $locationE[0], 'place' => $locationE[1] ), $dbh ) ) {
					break;
				}
				
				// Place, County
				if( $doveId = search( array( 'minBells' => $minBells, 'place' => $locationE[0], 'county' => $locationE[1] ), $dbh ) ) {
					break;
				}
			
				$notFound[] = $location.' : '.$method;
				break;
			
			case 3:
			if( $locationE[0] == 'Sullivans Island' ) { $locationE[0] = 'Charleston, Sullivan\'s Island'; }
			
				// Dedication, Place, County
				if( $doveId = search( array( 'minBells' => $minBells, 'dedication' => $locationE[0], 'place' => $locationE[1], 'county' => $locationE[2] ), $dbh ) ) {
					break;
				}
				
				// Place, County, Country
				if( $doveId = search( array( 'minBells' => $minBells, 'place' => $locationE[0], 'county' => $locationE[1], 'country' => $locationE[2] ), $dbh ) ) {
					break;
				}
			
				$notFound[] = $location.' : '.$method;
				break;
			
			case 4:
				// Special cases
				if( $locationE[0] == 'St Peter\'s Cathedral' && $locationE[1] == 'Adelaide' ) { $doveId = 'ADELA___CA'; break; }
				if( $locationE[0] == 'St Mary\'s Cathedral' && $locationE[1] == 'Sydney' ) { $doveId = 'SYDNEY___R'; break; }
				if( $locationE[0] == 'St Andrew\'s Cathedral' && $locationE[1] == 'Sydney' ) { $doveId = 'SYDNEY___A'; break; }
			
				// Dedication, Place, County, Country
				if( $doveId = search( array( 'minBells' => $minBells, 'dedication' => $locationE[0], 'place' => $locationE[1], 'county' => $locationE[2], 'country' => $locationE[3] ), $dbh ) ) {
					break;
				}
			
			default:
				$notFound[] = $location.' : '.$method;
				break;
			}
		}
		
		if( !empty( $doveId ) ) {
			echo 'INSERT INTO methods_firsttowerbellpeal_tower (method,firstTowerbellPeal_tower) VALUES (\''.sqlite_escape_string( $method ).'\', \''.sqlite_escape_string( $doveId ).'\');'."\n";
		}
	}
	$dbh = null;
?>
ALTER TABLE `methods_firsttowerbellpeal_tower`
  ADD CONSTRAINT `methods_firsttowerbellpeal_tower_ibfk_3` FOREIGN KEY (`method`) REFERENCES `methods` (`title`),
  ADD CONSTRAINT `methods_firsttowerbellpeal_tower_ibfk_2` FOREIGN KEY (`firstTowerbellPeal_tower`) REFERENCES `towers` (`doveId`);
<?php
	echo "\n/*\nUnmatched towers:\n";
	sort( $notFound );
	echo implode( "\n", $notFound )."\n*/";
}
catch ( PDOException $e ) {
	echo 'Error: ' . $e->getMessage();
	die();
}

function search( $where, &$dbh ) {
	global $counties, $states, $australianAreas, $canadianStates;

	$queryText = 'SELECT doveId from towers WHERE 1';
	if( isset( $where['minBells'] ) ) {
		$queryText .= ' AND bells >= :minBells';
	}
	if( isset( $where['place'] ) ) { $queryText .=  ' AND (place = :place OR altName = :place)'; }
	if( isset( $where['dedication'] ) ) { $queryText .=  ' AND dedication LIKE CONCAT(\'%\', :dedication, \'%\')'; }
	if( isset( $where['country'] ) ) {
		$queryText .=  ' AND country = :country';
		if( $where['country'] == 'US' ) { $where['country'] = 'USA'; }
		elseif( $where['country'] == 'AU' ) { $where['country'] = 'Australia'; }
		elseif( $where['country'] == 'CA' ) { $where['country'] = 'Canada'; }
	}
	if( isset( $where['county'] ) ) {
		$queryText .=  ' AND county LIKE CONCAT(\'%\', :county, \'%\')';
		if( ! isset( $where['country'] ) ) {
			if( isset( $counties[$where['county']] ) ) { $where['county'] = $counties[$where['county']]; }
			elseif( isset( $states[$where['county']] ) ) { $where['county'] = $states[$where['county']]; }
			elseif( isset( $australianAreas[$where['county']] ) ) { $where['county'] = $australianAreas[$where['county']]; }
			elseif( isset( $canadianStates[$where['county']] ) ) { $where['county'] = $canadianStates[$where['county']]; }
		}
		else {
			if( $where['country'] == 'England' && isset( $counties[$where['county']] ) ) { $where['county'] = $counties[$where['county']]; }
			elseif( $where['country'] == 'USA' && isset( $states[$where['county']] ) ) { $where['county'] = $states[$where['county']]; }
			elseif( $where['country'] == 'Australia' && isset( $australianAreas[$where['county']] ) ) { $where['county'] = $australianAreas[$where['county']]; }
			elseif( $where['country'] == 'Canada' && isset( $canadianStates[$where['county']] ) ) { $where['county'] = $canadianStates[$where['county']]; }
		}
	}
	
	// print_r( array( 'query' => $queryText, 'where' => $where) );
	$query = $dbh->prepare( $queryText );
	$query->execute( $where );
	$search = $query->fetchAll( PDO::FETCH_ASSOC );
	if( count( $search ) == 1 ) {
		return $search[0]['doveId'];
	}
	else {

		// Some county alternatives (thanks Dove!)
		if( isset( $where['county'] ) ) {
			$countyAgain = false;
			if( $where['county'] == 'Middlesex' || $where['county'] == 'Surrey' || $where['county'] == 'Essex' ) {
				$county = 'Greater London';
				$countyAgain = true;
			}
			else if( $where['county'] == 'Warwickshire' ) {
				$county = 'West Midlands';
				$countyAgain = true;
			}
			elseif( $where['county'] == 'Lancashire' ) {
				$county = 'Greater Manchester';
				$countyAgain = true;
			}
			elseif( $where['county'] == 'Staffordshire' ) {
				$county = 'South Yorkshire';
				$countyAgain = true;
			}
			if( $countyAgain ) {
				if( $doveId = search( array_merge( $where, array( 'county' => $county ) ), $dbh ) ) {
					return $doveId;
				}
			}
		}
		
		// Try more general place searching
		$query = $dbh->prepare( str_replace( 'place = :place', 'place LIKE CONCAT(\'% \', :place)', $queryText ) );
		$query->execute( $where );
		$search = $query->fetchAll( PDO::FETCH_ASSOC );
		if( count( $search ) == 1 ) {
			return $search[0]['doveId'];
		}
		
		// Try even more general place searching
		$query = $dbh->prepare( str_replace( 'place = :place', 'place LIKE CONCAT(\'%\', :place, \'%\')', $queryText ) );
		$query->execute( $where );
		$search = $query->fetchAll( PDO::FETCH_ASSOC );
		if( count( $search ) == 1 ) {
			return $search[0]['doveId'];
		}
	}
	return false;
}
