<?php
// This is a horrendous mess

require( dirname(__FILE__).'/../../../vendor/blueline/abbreviations.php' );

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

DROP TABLE IF EXISTS `methods_towers`;
CREATE TABLE IF NOT EXISTS `methods_towers` (
  `method_title` varchar(255) NOT NULL COMMENT 'Title of method',
  `tower_doveid` varchar(10) NOT NULL COMMENT 'Dove ID of the tower where the first tower bell peal was rung',
  PRIMARY KEY (`method_title`,`tower_doveid`),
  UNIQUE KEY `method_title` (`method_title`),
  KEY `tower_doveid` (`tower_doveid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

<?php
$notFound = array();
$doesntExistInDove = array(
	'All Saints, Leicester',
	'Bell Meadow Peal, Warnham', // http://www.campaniles.co.uk/MiniRingsFixed.html
	'Beehive Campanile, Swanage',
	'Bishop Ryder, Birmingham', // http://www.warksbells.co.uk/lost.htm
	'Brentford', // http://www.brentforddockresidents.co.uk/stlawrencebells.php
	'Butterywells Campanile, Potterton', 
	'Campanile, Romanby',
	'Cathedral, Melbourne, VIC, AU', // Which one?!
	'Coleridge Campanile, Walsoken', 
	'Denholme Gate', // http://www.westgallerychurches.com/Yorks/West/Denholme/Denholme.html
	'Deritend', // http://www.warksbells.co.uk/lost.htm
	'Die Glockli, Modautal Brandau', 
	'Earlsheaton', // http://www.dewsburyreporter.co.uk/lifestyle/features/ringing_the_changes_1_1349858
	'Eastwell', // http://kent.lovesguide.com/eastwell.htm
	'Hillbrow Campanile, Liss',
	'Horsleydown', // http://en.wikipedia.org/wiki/St_John_Horsleydown
	'Le Petit Beffroi, Souce', // Mini-ring
	'Little Orchard, East Huntspill', // Mini-ring
	'Lower House Farm, Leigh Sinton',
	'Midways Campanile, Stubbington',
	'Millcroft Campanile, Willingham',
	'Mindinho le Tower, Newmarket',
	'Narnia Campanile',
	'Narnia Campanile, Stubbington',
	'The Narnia Campanile, Stubbington',
	'Pig le Tower, Marston Bigot',
	'Senouillac', // Mini-ring
	'St Bride, Fleet Street, London',
	'St Dunstan in the East, London',
	'St Dunstan in the West, London', // http://london.lovesguide.com/dunstan-in-the-west.htm
	'St Francis, Holbeck', // http://www.riponandleedsbells.org.uk/Leeds%20St%20Anne.htm
	'The Rector\'s Ring, Clenchwarton'
);
try {
	$dbh = new PDO( $dsn, $username, $password );
	
	$methodSearch = $dbh->prepare( 'SELECT title, stage, firstTowerbellPeal_location as location from methods WHERE firstTowerbellPeal_location IS NOT NULL' );
	$methodSearch->execute();
	
	foreach( $methodSearch->fetchAll( PDO::FETCH_ASSOC ) as $method ) {
		$location = $method['location'];
		$minBells = ($method['stage'] == 13 || $method['stage'] == 15)? $method['stage']-1 : $method['stage'];
		$method = $method['title'];
		$doveid = '';
		$location = trim( str_replace( '-', ' ', $location ) );
		
		
		if( in_array( $location, $doesntExistInDove ) ) {
			continue;
		}
		
		// Place
		if( strpos( $location, ',' ) === FALSE ) {
			if( $location == 'Barnsley' ) { $doveid = 'BARNSLEY_Y'; }
			elseif( $location == 'Barrow on Humber' ) { $doveid = 'BARROW_UH'; }
			elseif( $location == 'Birstall' ) { $doveid = 'BIRSTALL_Y'; }
			elseif( $location == 'Bishop\'s Tawton' ) { $doveid = 'BISHOPS_TA'; }
			elseif( $location == 'Bishops Cleeve' ) { $doveid = 'BISHOPS_6'; }
			elseif( $location == 'Church Lawford' ) { $doveid = 'CHURCH_LAW'; } // Assume not The Plantagenet Ring
			elseif( $location == 'Collingham' ) { $doveid = 'COLLINGHAM'; }
			elseif( $location == 'Farnworth with Kearsley' ) { $doveid = 'FARNWORTHK'; }
			elseif( $location == 'Great Berkhamsted' ) { $doveid = 'BERKHAMSTE'; }
			elseif( $location == 'Hanbury' && $method == 'Vale of Evesham Surprise Major' ) { $doveid = 'HANBURY_WO'; }
			elseif( $location == 'Lye' ) { $doveid = 'LYE'; }
			elseif( $location == 'Michaelston y Fedw' ) { $doveid = 'MICHAELSTN'; }
			elseif( $location == 'Netherseale' ) { $doveid = 'NETHERSEAL'; }
			elseif( $location == 'Newchurch Kenyon' ) { $doveid = 'CULCHETH'; }
			elseif( $location == 'Overseale' ) { $doveid = 'OVERSEAL'; }
			elseif( $location == 'Pulham St Mary Magd.' ) { $doveid = 'PULHAM_MAR'; }
			elseif( $location == 'Pulham St Mary the Virgin' ) { $doveid = 'PULHAM_ST'; }
			elseif( $location == 'Shaw' && $method == 'Shaw Bob Major' ) { $doveid = 'EAST_CROMP'; }
			elseif( $location == 'St John in the Oaks' ) { $doveid = 'SAI_JOHNJE'; }
			elseif( $location == 'Scofton with Osberton' ) { $doveid = 'SCOFTON'; }
			elseif( $location == 'Sprotborough' ) { $doveid = 'SPROTBROUG'; }
			elseif( $location == 'Sutton on Hull' ) { $doveid = 'KINGSTN6'; }
			elseif( $location == 'Wath on Dearne' ) { $doveid = 'WATH_UPOND'; }
			
			elseif( !$doveid = search( array( 'minBells' => $minBells, 'place' => $location ), $dbh ) ) {
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
				
				// Some problem towers
				if( $locationE[0] == 'Cape Town' && $locationE[1] == 'Woodstock' ) { $doveid = 'CAPE_TOWNW'; break; }
				if( $locationE[0] == 'Christchurch' && $locationE[1] == 'Hants' ) { $doveid = 'CHRISTCH_D'; break; }
				if( $locationE[1] == 'Dublin' && $locationE[0] == 'Christ Church Cathedral' ) { $doveid = 'DUBLIN__DC'; break; }
				if( $locationE[1] == 'Ealing' && $locationE[0] == 'Christ Church' ) { $doveid = 'EALING'; break; }
				if( $locationE[0] == 'Llanbadarn Fawr' && $locationE[1] == 'Dyfed' ) { $doveid = 'LLANBADARA'; break; }
				if( $locationE[0] == 'Marshfield' && $locationE[1] == 'Mon' ) { $doveid = 'MARSHFLD_G'; break; }
				if( $locationE[1] == 'Milton Keynes' && $locationE[0] == 'All Saints' ) { $doveid = 'MILTONKY21'; break; }
				if( $locationE[0] == 'Newport' && $locationE[1] == 'Gwent' ) { $doveid = 'NEWPORT_NC'; break; }
				if( $locationE[0] == 'Norton' && $locationE[1] == 'Yorks' ) { $doveid = 'SHEFFIELDN'; break; }
				if( $locationE[0] == 'Old St Mary' && $locationE[1] == 'Chester' ) { $doveid = 'CHESTR___M'; break; }
				if( $locationE[0] == 'Ryton' && $locationE[1] == 'Durham' ) { $doveid = 'RYTON__TYW'; break; }
				if( $locationE[0] == 'St John' && $locationE[1] == 'Lambeth' ) { $doveid = 'WATERLOOWR'; break; }
				if( $locationE[0] == 'St Michael with St Paul' && $locationE[1] == 'Bath' ) { $doveid = 'BATH___MIC'; break; }
				if( $locationE[0] == 'St Michael Coslany' && $locationE[1] == 'Norwich' ) { $doveid = 'NORWICH_MI'; break; }
				if( $locationE[0] == 'St Mary' && $locationE[1] == 'Spalding' ) { $doveid = 'SPALDING'; break; }
				if( $locationE[0] == 'St Mary' && $locationE[1] == 'Hayes' ) { $doveid = 'HAYES_MRY'; break; }
				if( $locationE[0] == 'St Mary' && $locationE[1] == 'Folkestone' ) { $doveid = 'FOLKESTN'; break; }
				if( $locationE[0] == 'St Mary Magdalene' && $locationE[1] == 'Torquay' ) { $doveid = 'TORQUAY_UP'; break; }
				if( $locationE[0] == 'St Mary Magdalene' && $locationE[1] == 'Bridgnorth' ) { $doveid = 'BRIDGNOR_M'; break; }
				if( $locationE[0] == 'St Margaret of Antioch' && $locationE[1] == 'Uxbridge' ) { $doveid = 'UXBRIDGE_M'; break; }
				if( $locationE[0] == 'St Machar\'s Cathedral' && $locationE[1] == 'Aberdeen' ) { $doveid = 'ABERDEEN'; break; }
				if( $locationE[0] == 'St Laurence' && $locationE[1] == 'York' ) { $doveid = 'YORK___SLA'; break; }
				if( $locationE[0] == 'St John in Bedwardine' && $locationE[1] == 'Worcester' ) { $doveid = 'WORCESTERB'; break; }
				if( $locationE[0] == 'St John at Hackney' && $locationE[1] == 'Hackney' ) { $doveid = 'HACKNEY'; break; }
				if( $locationE[0] == 'St John Bapt.' && $locationE[1] == 'Newcastle upon Tyne' ) { $doveid = 'NEWCASUT_J'; break; }
				if( $locationE[0] == 'Wollaston' && $locationE[1] == 'Worcs' ) { $doveid = 'STOURBRGWO'; break; }
				if( $locationE[0] == 'Woodchurch' && $locationE[1] == 'Cheshire' ) { $doveid = 'WOODCHURME'; break; }
				
				// Dedication, Place
				if( $doveid = search( array( 'minBells' => $minBells, 'dedication' => $locationE[0], 'place' => $locationE[1] ), $dbh ) ) {
					break;
				}
				
				// Place, County
				if( $doveid = search( array( 'minBells' => $minBells, 'place' => $locationE[0], 'county' => $locationE[1] ), $dbh ) ) {
					break;
				}
			
				$notFound[] = $location.' : '.$method;
				break;
			
			case 3:
				if( $locationE[0] == 'Sullivans Island' ) { $doveid = 'CHARLESTNS'; break; }
			
				// Dedication, Place, County
				if( $doveid = search( array( 'minBells' => $minBells, 'dedication' => $locationE[0], 'place' => $locationE[1], 'county' => $locationE[2] ), $dbh ) ) {
					break;
				}
				
				// Place, County, Country
				if( $doveid = search( array( 'minBells' => $minBells, 'place' => $locationE[0], 'county' => $locationE[1], 'country' => $locationE[2] ), $dbh ) ) {
					break;
				}
			
				$notFound[] = $location.' : '.$method;
				break;
			
			case 4:
				// Special cases
				if( $locationE[0] == 'St Peter\'s Cathedral' && $locationE[1] == 'Adelaide' ) { $doveid = 'ADELA___CA'; break; }
				if( $locationE[0] == 'St Mary\'s Cathedral' && $locationE[1] == 'Sydney' ) { $doveid = 'SYDNEY___R'; break; }
				if( $locationE[0] == 'St Andrew\'s Cathedral' && $locationE[1] == 'Sydney' ) { $doveid = 'SYDNEY___A'; break; }
				if( $locationE[0] == 'Swan Campanile' && $locationE[1] == 'Perth' ) { $doveid = 'PERTH_SWAN'; break; }
			
				// Dedication, Place, County, Country
				if( $doveid = search( array( 'minBells' => $minBells, 'dedication' => $locationE[0], 'place' => $locationE[1], 'county' => $locationE[2], 'country' => $locationE[3] ), $dbh ) ) {
					break;
				}
			
			default:
				$notFound[] = $location.' : '.$method;
				break;
			}
		}
		
		if( !empty( $doveid ) ) {
			echo 'INSERT INTO `methods_towers` (`method_title`, `tower_doveid`) VALUES (\''.sqlite_escape_string( $method ).'\', \''.sqlite_escape_string( $doveid ).'\');'."\n";
		}
	}
	$dbh = null;

	echo "\n\n-- Unmatched towers:\n";
	sort( $notFound );
	echo "\n-- ".implode( "\n-- ", $notFound )."\n";

}
catch ( PDOException $e ) {
	echo 'Error: ' . $e->getMessage();
	die();
}

function search( $where, &$dbh ) {
	global $counties, $states, $australianAreas, $canadianStates;

	$queryText = 'SELECT doveid from towers WHERE 1';
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
		if( !isset( $where['country'] ) ) {
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
		return $search[0]['doveid'];
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
				if( $doveid = search( array_merge( $where, array( 'county' => $county ) ), $dbh ) ) {
					return $doveid;
				}
			}
		}
		
		// Try more general place searching
		$query = $dbh->prepare( str_replace( 'place = :place', 'place LIKE CONCAT(\'% \', :place)', $queryText ) );
		$query->execute( $where );
		$search = $query->fetchAll( PDO::FETCH_ASSOC );
		if( count( $search ) == 1 ) {
			return $search[0]['doveid'];
		}
		
		// Try even more general place searching
		$query = $dbh->prepare( str_replace( 'place = :place', 'place LIKE CONCAT(\'%\', :place, \'%\')', $queryText ) );
		$query->execute( $where );
		$search = $query->fetchAll( PDO::FETCH_ASSOC );
		if( count( $search ) == 1 ) {
			return $search[0]['doveid'];
		}
	}
	return false;
}
