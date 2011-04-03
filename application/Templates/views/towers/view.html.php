<?php
namespace Blueline;
use Pan\View, Helpers\Text;

View::cache( true );

View::element( 'header', array(
	'title' => htmlspecialchars( Text::toList( array_map( function( $t ){ return $t->place().(($t->dedication()!='Unknown')?' ('.$t->dedication().')':''); }, $this->get( 'towers', array() ) ) ) ) . ' | Towers | Blueline',
	'breadcrumb' => array(
		'<a href="/towers">Towers</a>'
	),
	'headerSearch' => array(
		'action' => '/towers/search',
		'placeholder' => 'Search towers'
	)
) );
$i = 0;
?>
<?php $i = 0; foreach( $this->get( 'towers', array() ) as $tower ) : ?>
<section class="tower">
	<header>
		<h1><?=$tower->place().(($tower->dedication()!='Unknown')?' <span class="normalweight">('.$tower->dedication().')</span>':'')?></h1>
		<h2><?= Text::toList( array( $tower->county(), $tower->country() ), ', ', ', ' )?></h2>
		<span id="tower_<?=$i?>_tabBar"></span>
		<script>
		//<![CDATA[
			require( ['ui/TabBar'], function( TabBar ) {
				window['TabBars'].push( new TabBar( {
					landmark: 'tower_<?=$i?>_tabBar',
					tabs: [
						{ title: 'Details', content: 'content_details<?=$i?>' },
						{ title: 'Map', content: 'content_map<?=$i?>', className: 'normal_hide' }
	<?php if( count( $tower->firstPeals() ) > 0 ) : ?>
						,{ title: 'Methods First Pealed', content: 'content_peals<?=$i?>' }
	<?php endif; ?>
					]
				} ) );
			} );
		//]]>
		</script>
	</header>
	<div class="content">
		<section id="content_map<?=$i?>" class="towerStaticMap">
			<noscript><h2>Map</h2></noscript>
			<img width="320px" height="400px" src="http://maps.google.com/maps/api/staticmap?format=png&amp;size=320x400&amp;maptype=roadmap&amp;sensor=false&amp;zoom=14&amp;center=<?php echo "{$tower->latitude()},{$tower->longitude()}&amp;markers=size:small|color:red|{$tower->latitude()},{$tower->longitude()}"; ?>" />
		</section>
		<script>
		//<![CDATA[
			require( ['ui/TowerMap'], function( TowerMap ) {
				TowerMap.set( {
					zoom: 15,
					center: new google.maps.LatLng( <?="{$tower->latitude()}, {$tower->longitude()}"?> )
				} );
			} );
		//]]>
		</script>
		<section id="content_details<?=$i?>">
			<noscript><h2>Details</h2></noscript>
			<table class="horizontalDetails">
				<tr class="bigDetails">
					<th>Bells:</th>
					<td><strong><?=$tower->bells()?></strong></td>
				</tr>
				<tr class="bigDetails">
					<th>Tenor:</th>
					<td><strong><?=(($tower->weightApprox())?'~':'').$tower->weightText() . '</strong> in ' . $tower->note( true ) . ($tower->hz()?' ('.$tower->hz().'Hz)':'')?></td>
				</tr>
<?php if( count( $tower->affiliations() ) > 0 ) : ?>
				<tr>
					<th>Affiliations:</th>
					<td><?= Text::toList( array_map( function( $a ) { return "<a href=\"{$a->href()}\">{$a->name()}</a>"; }, $tower->affiliations() ) )?></td>
				</tr>
<?php endif; ?>
				<tr>
					<th>Diocese:</th>
					<td><?="<a href=\"/towers/search?diocese=".urlencode($tower->diocese())."\">{$tower->diocese()}</a>"?></td>
				</tr>
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
					<td><?=$tower->postcode()?></td>
				</tr>
<?php endif; ?>
<?php if( $tower->gridReference() ) : ?>
				<tr>
					<th>Grid Reference:</th>
					<td><?=$tower->gridReference()?></td>
				</tr>
<?php endif; ?>
			</table>
<?php if( count( $tower->nearbyTowers() ) > 0 ) : ?>
			<h3>Nearby Towers:</h3>
			<ol>
<?php foreach( $tower->nearbyTowers() as $nTower ) : ?>
				<li><?='<a href="'.$nTower->href().'">' . $nTower->place().' <small>('.$nTower->dedication().')</small></a> <small>'.round( $nTower->distance(), 1 ).' miles</small>'?></li>
<?php endforeach; ?>
			</ol>
<?php endif; ?>
		</section>
		<section id="content_peals<?=$i?>" class="towerFirstPeals">
<?php if( count( $tower->firstPeals() ) > 0 ) : ?>
			<noscript><h2>First Peals</h2></noscript>
			<ol>
<?php foreach( $tower->firstPeals()  as $method ) : ?>
				<li><?="<a href=\"{$method->href()}\">{$method->title()}</a> <small>({$method->firstTowerbellPeal_date()})</small>"?></li>
<?php endforeach; ?>
			</ol>
<?php endif; ?>
		</section>
	</div>
</section>
<?php ++$i; endforeach; ?>
<?php View::element( 'footer' ); ?>
