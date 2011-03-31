<?php
namespace Blueline;
use Pan\View;

View::cache( true );

View::element( 'header', array(
	'title' => htmlspecialchars( \Helpers\Text::toList( array_map( function( $a ){ return $a->name();}, $this->get( 'associations', array() ) ) ) ) . ' | Associations | Blueline',
	'breadcrumb' => array(
		'<a href="/associations">Associations</a>'
	),
	'headerSearch' => array(
		'action' => '/associations/search',
		'placeholder' => 'Search associations'
	)
) );
$i = 0;
foreach( $this->get( 'associations', array() ) as $association ) : ?>
<section class="association" id="association_<?=$i?>" itemscope itemtype="http://data-vocabulary.org/Organization">
	<header>
		<h1 itemprop="name"><?= htmlspecialchars( $association->name() )?></h1>
		<span id="association_<?=$i?>_tabBar"></span>
		<script>
		//<![CDATA[
			require( ['ui/TabBar'], function( TabBar ) {
				window['TabBars'].push( new TabBar( {
					landmark: 'association_<?=$i?>_tabBar',
					tabs: [
						{ title: 'Details', content: 'content_details<?=$i?>' },
<?php if( $association->towerCount() > 0 ) : ?>
						,{ title: 'Towers', content: 'content_towers<?=$i?>' }
<?php endif; ?>
					]
				} ) );
			} );
		//]]>
		</script>
	</header>
	<div class="content">
		<script>
		//<![CDATA[
			require( ['ui/TowerMap'], function( TowerMap ) {
				TowerMap.set( {
					fitBounds: new google.maps.LatLngBounds( <?php $bbox = $association->bbox(); echo "new google.maps.LatLng( {$bbox['lat_min']}, {$bbox['long_min']} ), new google.maps.LatLng( {$bbox['lat_max']}, {$bbox['long_max']} )"; ?> ),
					fusionTableQuery: "SELECT location from 247449 WHERE affiliations contains '<?php echo $association->abbreviation(); ?>'"
				} );
			} );
		//]]>
		</script>
		<section id="content_details<?=$i?>">
			<table class="horizontalDetails">
<?php if( $association->link() ) : ?>
				<tr>
					<th>Link:</th>
					<td><a href="<?= htmlentities( $association->link() )?>" class="external" itemprop="url"><?= htmlentities( $association->link() )?></a></td>
				</tr>
<?php endif; ?>
				<tr>
					<th>Towers:</th>
					<td><?=$association->towerCount()?></td>
				</tr>
			</table>
		</section>
		<section id="content_towers<?=$i?>" class="associationAffiliations">
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
<?php View::element( 'footer' ); ?>
