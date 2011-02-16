<?php
namespace Blueline;

View::element( 'default.header', array(
	'title' => htmlspecialchars( \Helpers\Text::toList( array_map( function( $a ){ return $a->name();}, $associations ) ) ) . ' | Associations | Blueline',
	'breadcrumb' => array(
		'<a href="/associations">Associations</a>'
	),
	'headerSearch' => array( 
		'action' => '/associations/search',
		'placeholder' => 'Search associations'
	)
) );
$i = 0;
foreach( $associations as $association ) : ?>
<section class="association" id="association_<?php echo $i; ?>" itemscope itemtype="http://data-vocabulary.org/Organization">
	<header>
		<h1 itemprop="name"><?php echo htmlspecialchars( $association->name() ); ?></h1>
		<span id="association_<?php echo $i; ?>_tabBar"></span>
		<script>
		//<![CDATA[
			require( ['ui/TabBar'], function( TabBar ) {
				window['TabBars'].push( new TabBar( {
					landmark: 'association_<?php echo $i; ?>_tabBar',
					tabs: [
						{ title: 'Details', content: 'content_details<?php echo $i; ?>' },
						{ title: 'Map', content: 'content_map<?php echo $i; ?>', className: 'normal_hide' }
<?php if( $association->towerCount() > 0 ) : ?>
						,{ title: 'Towers', content: 'content_towers<?php echo $i; ?>' }
<?php endif; ?>
					]
				} ) );
			} );
		//]]>
		</script>
	</header>
	<div class="content">
		<section id="content_map<?php echo $i; ?>" class="towerMap">
			<noscript><h2>Map</h2></noscript>
			<div id="map<?php echo $i; ?>" class="map"></div>
		</section>
		<script>
		//<![CDATA[
			require( ['ui/TowerMap'], function( TowerMap ) {
				window['towerMaps'].push( new TowerMap( {
					id: <?php echo $i; ?>,
					container: 'map<?php echo $i; ?>',
					scrollwheel: false,
					fitBounds: new google.maps.LatLngBounds( <?php $bbox = $association->bbox(); echo "new google.maps.LatLng( {$bbox['lat_min']}, {$bbox['long_min']} ), new google.maps.LatLng( {$bbox['lat_max']}, {$bbox['long_max']} )"; ?> ),
					fusionTableQuery: "SELECT location from 247449 WHERE affiliations contains '<?php echo $association->abbreviation(); ?>'"
				} ) );
			} );
		//]]>
		</script>
		<section id="content_details<?php echo $i; ?>">
			<table class="horizontalDetails">
<?php if( $association->link() ) : ?>
				<tr>
					<th>Link:</th>
					<td><a href="<?php echo htmlentities( $association->link() ); ?>" class="external" itemprop="url"><?php echo htmlentities( $association->link() ); ?></a></td>
				</tr>
<?php endif; ?>
				<tr>
					<th>Towers:</th>
					<td><?php echo $association->towerCount(); ?></td>
				</tr>
			</table>
		</section>
		<section id="content_towers<?php echo $i; ?>" class="associationAffiliations">
<?php if( $association->towerCount() > 0 ) : ?>
			<noscript><h2>Affiliated Towers</h2></noscript>
			<ol class="noliststyle">
<?php foreach( $association->affiliatedTowers() as $tower ) : ?>
				<li><?php echo '<a href="'.$tower->href().'">' . $tower->place().' <small>('.$tower->dedication().')</small></a>'; ?></li>
<?php endforeach; ?>
			</ol>
<?php endif; ?>
		</section>
	</div>
</section>
<?php ++$i; endforeach; ?>
<?php View::element( 'default.footer' ); ?>
