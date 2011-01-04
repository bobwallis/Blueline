<?php
namespace Blueline;
use \Helpers\Text;

View::element( 'default.header', array(
	'title' => htmlspecialchars( Text::toList( array_map( function( $t ){ return $t->place().(($t->dedication()!='Unknown')?' ('.$t->dedication().')':''); }, $towers ) ) ) . ' | Towers | Blueline',
	'breadcrumb' => array(
		'<a href="/towers">Towers</a>'
	),
	'headerSearch' => array( 
		'action' => '/towers/search',
		'placeholder' => 'Search towers'
	),
	'scripts' => array(
		'http://maps.google.com/maps/api/js?sensor=false',
		'/scripts/towers.js'
	)
) );
$i = 0;
?>
<?php $i = 0; foreach( $towers as $tower ) : ?>
<section class="tower">
	<header>
		<h1><?php echo $tower->place().(($tower->dedication()!='Unknown')?' <span class="normalweight">('.$tower->dedication().')</span>':''); ?></h1>
		<h2 class="sub"><?php echo Text::toList( array( $tower->county(), $tower->country() ), ', ', ', ' ); ?></h2>
		<ul class="tabBar">
			<li id="tab_details<?php echo $i; ?>" class="active">Details</li>
			<li id="tab_map<?php echo $i; ?>" class="normal_hide">Map</li>
<?php if( count( $tower->firstPeals() ) > 0 ) : ?>
			<li id="tab_peals<?php echo $i; ?>">Methods First Pealed</li>
<?php endif; ?>
		</ul>
	</header>
	<section id="content_map<?php echo $i; ?>" class="towerMap">
		<noscript><h2>Map</h2></noscript>
		<div id="map<?php echo $i; ?>" class="map"><noscript><img width="600px" height="370px" src="http://maps.google.com/maps/api/staticmap?format=png&amp;size=600x370&amp;maptype=roadmap&amp;sensor=false&amp;zoom=14&amp;center=<?php echo "{$tower->latitude()},{$tower->longitude()}&amp;markers=size:small|color:red|{$tower->latitude()},{$tower->longitude()}"; ?>" /></noscript></div>
	</section>
	<script>
	//<![CDATA[
		towerMaps.push( new TowerMap( {
			id: <?php echo $i; ?>,
			container: 'map<?php echo $i; ?>',
			scrollwheel: false,
			zoom: 15,
			center: new google.maps.LatLng( <?php echo "{$tower->latitude()}, {$tower->longitude()}"; ?> )
		} ) );
	//]]>
	</script>
	<section id="content_details<?php echo $i; ?>">
		<noscript><h2>Details</h2></noscript>
		<table class="horizontalDetails">
			<tr class="bigDetails">
				<th>Bells:</th>
				<td><strong><?php echo $tower->bells(); ?></strong></td>
			</tr>
			<tr class="bigDetails">
				<th>Tenor:</th>
				<td><strong><?php echo (($tower->weightApprox())?'~':'').$tower->weightText() . '</strong> in ' . $tower->note( true ) . ($tower->hz()?' ('.$tower->hz().'Hz)':''); ?></td>
			</tr>
<?php if( count( $tower->affiliations() ) > 0 ) : ?>
			<tr>
				<th>Affiliations:</th>
				<td><?php echo Text::toList( array_map( function( $a ) { return "<a href=\"{$a->href()}\">{$a->name()}</a>"; }, $tower->affiliations() ) ); ?></td>
			</tr>
<?php endif; ?>
			<tr>
				<th>Information:</th>
				<td><?php
			$information = array();
			if( $tower->unringable() && stripos( $tower->extraInfo(),'unringable' ) === false ) {
				$information[] = 'Unringable. ';
			}
			if( $tower->extraInfo() ) {
				$extraInfo = preg_replace( array( '/([0-9]+)b/', '/([0-9]+)#/' ), array( '${1}&#x266d;', '${1}&#x266f;' ), $tower->extraInfo() );
				if( isset( $information[0] ) ) {
					$information[0] .= $extraInfo;
				}
				else {
					$information[] = $extraInfo;
				}
			}
			if( $tower->groundFloor() ) {
				$information[] = 'Ground floor.';
			}
			if( $tower->toilet() ) {
				$information[] = 'Toilet available.';
			}
			if( $tower->simulator() ) {
				$information[] = 'Has a simulator.';
			}
			if( $tower->overhaulYear() ) {
				$information[] = 'Overhauled in '.$tower->overhaulYear().($tower->contractor()?' by '.$tower->contractor().'.':'').(($tower->tuned()===$tower->overhaulYear())?' Also tuned in '.$tower->tuned().'.':'');
			}
			if( $tower->tuned() && $tower->overhaulYear() != $tower->tuned() ) {
				$information[] = 'Tuned in '.$tower->tuned().'.';
			}
			if( $tower->practiceNight() || $tower->practiceNotes() ) {
				$days = array( '', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
				$information[] = 'Practice: '.$days[$tower->practiceNight()].($tower->practiceStart()?', '.$tower->practiceStart():'').' '.($tower->practiceNotes()?:'');
			}
			$information[] = ($tower->webPage()?'<a href="http://'.$tower->webPage().'" class="external">Tower Website</a>, ':'') . '<a href="http://dove.cccbr.org.uk/detail.php?DoveID='.str_replace( '_', '&#43;', $tower->doveId()).'&showFrames=true" class="external">Dove Entry</a>';
		
			echo implode( '<br/>', $information );
		?></td>
			</tr>
<?php if( $tower->postcode() ) : ?>
			<tr>
				<th>Postcode:</th>
				<td><?php echo $tower->postcode(); ?></td>
			</tr>
<?php endif; ?>
<?php if( $tower->gridReference() ) : ?>
			<tr>
				<th>Grid Reference:</th>
				<td><?php echo $tower->gridReference(); ?></td>
			</tr>
<?php endif; ?>
		</table>
<?php if( count( $tower->nearbyTowers() ) > 0 ) : ?>
		<h3>Nearby Towers:</h3>
		<ol class="noliststyle">
<?php foreach( $tower->nearbyTowers() as $nTower ) : ?>
			<li><?php echo '<a href="'.$nTower->href().'">' . $nTower->place().' <small>('.$nTower->dedication().')</small></a> <small>'.round( $nTower->distance(), 1 ).' miles</small>'; ?></li>
<?php endforeach; ?>
		</ol>
<?php endif; ?>
	</section>
	<section id="content_peals<?php echo $i; ?>" class="towerFirstPeals">
<?php if( count( $tower->firstPeals() ) > 0 ) : ?>
		<ol class="noliststyle">
<?php foreach( $tower->firstPeals()  as $method ) : ?>
			<li><?php echo "<a href=\"{$method->href()}\">{$method->title()}</a> <small>({$method->firstTowerbellPeal_date()})</small>"; ?></li>
<?php endforeach; ?>
		</ol>
<?php endif; ?>
	</section>
</section>
<?php ++$i; endforeach; ?>
<?php View::element( 'default.footer' ); ?>
