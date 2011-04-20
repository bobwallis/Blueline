<?php
namespace Blueline;
use Pan\View, Helpers\Text, Helpers\Stages, Helpers\Dates;

View::cache( true );

View::element( 'header', array(
	'title' => htmlspecialchars( \Helpers\Text::toList( array_map( function( $m ){ return $m->title(); }, $this->get( 'methods', array() ) ) ) ) . ' | Methods | Blueline',
	'breadcrumb' => array(
		'<a href="/methods">Methods</a>'
	),
	'headerSearch' => array(
		'action' => '/methods/search',
		'placeholder' => 'Search methods'
	)
) );
$i = 0;
foreach( $this->get( 'methods', array() ) as $method ) : ?>
<section class="method" id="method<?=$i?>">
	<header>
		<h1><?=$method->title()?></h1>
		<span id="method<?=$i?>_tabBar"></span>
		<script>
		//<![CDATA[
			require( ['ui/TabBar'], function( TabBar ) {
				window['TabBars'].push( new TabBar( {
					landmark: 'method<?=$i?>_tabBar',
					tabs: [
						{ title: 'Details', content: 'content_details<?=$i?>' },
						{ title: 'Line', content: 'content_line<?=$i?>' },
						{ title: 'Grid', content: 'content_grid<?=$i?>' }
					],
					active: 1
				} ) );
			} );
		//]]>
		</script>
	</header>
	<div class="content">
		<div id="content_details<?=$i?>" class="methodDetails">
			<noscript><h2>Details</h2></noscript>
			<table class="horizontalDetails">
				<tr>
					<th>Classification:</th>
					<td><?=($method->differential()?'Differential ':'') . ($method->little()?'Little ' :'') . $method->classification() .' '. $method->stageText()?></td>
				</tr>
				<tr>
					<th>Place&nbsp;Notation:</th>
					<td><abbr title="<?=$method->notationExpanded()?>"><?=$method->notation()?></abbr></td>
				</tr>
				<tr>
					<th>Lead Head:</th>
					<td><?=$method->leadHead() . ($method->leadHeadCode()?" <small>(Code: {$method->leadHeadCode()})</small>":'')?></td>
				</tr>
<?php if( $method->palindromic() || $method->doubleSym() || $method->rotational() ) : ?>
				<tr>
					<th>Symmetry:</th>
					<td><?= ucfirst( Text::toList( array_filter( array( ($method->palindromic()?'palindromic':''), ($method->doubleSym()?'double':''), ($method->rotational()?'rotational':'') ) ) ) )?></td>
				</tr>
<?php endif; ?>
<?php if( $method->fchGroups() ) : ?>
				<tr>
					<th><abbr title="False Course Head">FCH</abbr> Groups:</th>
					<td><?=$method->fchGroups()?></td>
				</tr>
<?php endif; ?>
<?php if( $method->numberOfHunts() ) : ?>
				<tr>
					<th>Hunt Bells:</th>
					<td><?=( $method->numberOfHunts() > 0 )? implode( ', ', $method->hunts() ) : 'None'?></td>
				</tr>
<?php endif; ?>
<?php if( $method->lengthOfLead() ) : ?>
				<tr>
					<th>Lead Length:</th>
					<td><?=$method->lengthOfLead()?> rows</td>
				</tr>
<?php endif; ?>
<?php if( $method->firstTowerbellPeal_date() ) : ?>
				<tr>
					<th>First towerbell peal:</th>
					<td><?= Dates::convert( $method->firstTowerbellPeal_date() ) . ($method->firstTowerbellPeal_location()? ' at '.($method->firstTowerbellPeal_location_doveId()? '<a href="/towers/view/'.$method->firstTowerbellPeal_location_doveId().'">'.$method->firstTowerbellPeal_location().'</a>' : $method->firstTowerbellPeal_location()) : '')?></td>
				</tr>
<?php endif; ?>
<?php if( $method->firstHandbellPeal_date() ) : ?>
				<tr>
					<th>First handbell peal:</th>
					<td><?= Dates::convert( $method->firstHandbellPeal_date() )?></td>
				</tr>
<?php endif; ?>
			</table>
		</div>
		<div id="content_line<?=$i?>" class="methodLine"></div>
		<div id="content_grid<?=$i?>" class="methodGrid"></div>
		<script>
		//<![CDATA[
			require( ['ui/MethodView'], function( MethodView ) {
				new MethodView( {
					id: <?=$i?>,
					numbersContainer: '#content_line<?=$i?>',
					gridContainer: '#content_grid<?=$i?>',
					stage: <?=$method->stage()?>,
					notation: <?= json_encode( $method->notationExpanded() )?>,
					calls: <?= json_encode( $method->calls() )?>,
<?php if( $method->ruleOffs() ) : ?>
					ruleOffs: <?= json_encode( $method->ruleOffs() )?>
<?php endif; ?>
				} );
			} );
		//]]>
		</script>
	</div>
</section>
<?php ++$i; endforeach; ?>
<?php View::element( 'footer' ); ?>
