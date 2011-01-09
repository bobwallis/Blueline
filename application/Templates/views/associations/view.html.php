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
	),
	'scripts' => array(
		'http://maps.google.com/maps/api/js?sensor=false',
		'/scripts/general.js',
		'/scripts/towers.js'
	)
) );
$i = 0;
foreach( $associations as $association ) : ?>
<section class="association" id="association_<?php echo $i; ?>">
	<header>
		<h1><?php echo htmlspecialchars( $association->name() ); ?></h1>
		<span id="association_<?php echo $i; ?>_tabBar"></span>
		<script>
		//<![CDATA[
			window.tabBars.push( new TabBar( {
				landmark: 'association_<?php echo $i; ?>_tabBar',
				tabs: [
					{ id: 'details<?php echo $i; ?>', title: 'Details' },
					{ id: 'map<?php echo $i; ?>', title: 'Map', className: 'normal_hide' }
<?php if( $association->towerCount() > 0 ) : ?>
					,{ id: 'towers<?php echo $i; ?>', title: 'Towers' }
<?php endif; ?>
				]
			} ) );
		//]]>
		</script>
	</header>
	<section id="content_map<?php echo $i; ?>" class="towerMap">
		<noscript><h2>Map</h2></noscript>
		<div id="map<?php echo $i; ?>" class="map"></div>
	</section>
	<script>
	//<![CDATA[
		window.towerMaps.push( new TowerMap( {
			id: <?php echo $i; ?>,
			container: 'map<?php echo $i; ?>',
			scrollwheel: false,
			fitBounds: new google.maps.LatLngBounds( <?php $bbox = $association->bbox(); echo "new google.maps.LatLng( {$bbox['lat_min']}, {$bbox['long_min']} ), new google.maps.LatLng( {$bbox['lat_max']}, {$bbox['long_max']} )"; ?> ),
			fusionTableQuery: "SELECT location from 247449 WHERE affiliations contains '<?php echo $association->abbreviation(); ?>'"
		} ) );
	//]]>
	</script>
	<section id="content_details<?php echo $i; ?>">
		<table class="horizontalDetails">
<?php if( $association->link() ) : ?>
			<tr>
				<th>Link:</th>
				<td><a href="<?php echo htmlentities( $association->link() ); ?>" class="external"><?php echo htmlentities( $association->link() ); ?></a></td>
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
</section>
<?php ++$i; endforeach; ?>
<?php View::element( 'default.footer' ); ?>
