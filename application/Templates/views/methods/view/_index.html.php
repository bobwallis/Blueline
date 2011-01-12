<?php
namespace Blueline;
use Helpers\Text, Helpers\Stages, Helpers\Dates;

View::element( 'default.header', array(
	'title' => htmlspecialchars( \Helpers\Text::toList( array_map( function( $m ){ return $m->title(); }, $methods ) ) ) . ' | Methods | Blueline',
	'breadcrumb' => array(
		'<a href="/methods">Methods</a>'
	),
	'headerSearch' => array( 
		'action' => '/methods/search',
		'placeholder' => 'Search methods'
	),
	'scripts' => array(
		'/scripts/methods.js'
	)
) );
$i = 0;
foreach( $methods as $method ) : ?>
<section class="method" id="method_<?php echo $i; ?>">
	<header>
		<h1><?php echo $method->title(); ?></h1>
		<span id="method_<?php echo $i; ?>_tabBar"></span>
		<script>
		//<![CDATA[
			window.tabBars.push( new TabBar( {
				landmark: 'method_<?php echo $i; ?>_tabBar',
				tabs: [
					{ id: 'details<?php echo $i; ?>', title: 'Details' },
					{ id: 'line<?php echo $i; ?>', title: 'Line' },
					{ id: 'grid<?php echo $i; ?>', title: 'Grid' }
				],
				active: 1
			} ) );
		//]]>
		</script>
	</header>
	<div id="content_details<?php echo $i; ?>" class="methodDetails">
		<noscript><h2>Details</h2></noscript>
		<table class="horizontalDetails">
			<tr>
				<th>Classification:</th>
				<td><?php echo ($method->differential()?'Differential ':'') . ($method->little()?'Little ' :'') . $method->classification() .' '. $method->stageText(); ?></td>
			</tr>
			<tr>
				<th>Place&nbsp;Notation:</th>
				<td><abbr title="<?php echo $method->notationExpanded(); ?>"><?php echo $method->notation(); ?></abbr></td>
			</tr>
			<tr>
				<th>Lead Head:</th>
				<td><?php echo $method->leadHead() . ($method->leadHeadCode()?" <small>(Code: {$method->leadHeadCode()})</small>":''); ?></td>
			</tr>
<?php if( $method->palindromic() || $method->doubleSym() || $method->rotational() ) : ?>
			<tr>
				<th>Symmetry:</th>
				<td><?php echo ucfirst( Text::toList( array_filter( array( ($method->palindromic()?'palindromic':''), ($method->doubleSym()?'double':''), ($method->rotational()?'rotational':'') ) ) ) ); ?></td>
			</tr>
<?php endif; ?>
<?php if( $method->fchGroups() ) : ?>
			<tr>
				<th><abbr title="False Course Head">FCH</abbr> Groups:</th>
				<td><?php echo $method->fchGroups(); ?></td>
			</tr>
<?php endif; ?>
			<tr>
<?php if( $method->numberOfHunts() ) : ?>
			<tr>
				<th>Hunt Bells:</th>
				<td><?php echo ( $method->numberOfHunts() > 0 )? implode( ', ', $method->hunts() ) : 'None'; ?></td>
			</tr>
<?php endif; ?>
<?php if( $method->lengthOfLead() ) : ?>
			<tr>
				<th>Lead Length:</th>
				<td><?php echo $method->lengthOfLead(); ?> rows</td>
			</tr>
<?php endif; ?>
<?php if( $method->firstTowerbellPeal_date() ) : ?>
			<tr>
				<th>First towerbell peal:</th>
				<td><?php echo Dates::convert( $method->firstTowerbellPeal_date() ) . ($method->firstTowerbellPeal_location()? ' at '.($method->firstTowerbellPeal_location_doveId()? '<a href="/towers/view/'.$method->firstTowerbellPeal_location_doveId().'">'.$method->firstTowerbellPeal_location().'</a>' : $method->firstTowerbellPeal_location()) : ''); ?></td>
			</tr>
<?php endif; ?>
<?php if( $method->firstHandbellPeal_date() ) : ?>
			<tr>
				<th>First handbell peal:</th>
				<td><?php echo Dates::convert( $method->firstHandbellPeal_date() ); ?></td>
			</tr>
<?php endif; ?>
		</table>
	</div>
	<div id="content_line<?php echo $i; ?>" class="methodLine"></div>
	<div id="content_grid<?php echo $i; ?>" class="methodGrid"></div>
	<script>
	//<![CDATA[
		window.methods.push( new MethodView( {
			id: <?php echo $i; ?>,
			stage: <?php echo intval( $method->stage() ); ?>,
			notation: <?php echo json_encode( $method->notationExpanded() ); ?>,
			leadHead: <?php echo json_encode( $method->leadHead() ); ?>,
			calls: <?php echo json_encode( $method->calls() ); ?>,
<?php if( $method->ruleOffs() ) : ?>
			ruleOffs: <?php echo json_encode( $method->ruleOffs() ); ?>,
<?php endif; ?>
			options_line: {
				container: 'content_line<?php echo $i; ?>'
			},
			options_grid: {
				container: 'content_grid<?php echo $i; ?>'
			}
		} ) );
	//]]>
	</script>
</section>
<?php ++$i; endforeach; ?>
<?php View::element( 'default.footer' ); ?>
