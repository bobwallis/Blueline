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
		'/scripts/general.js',
		'/scripts/methods.js'
	)
) );
$i = 0;
foreach( $methods as $method ) : ?>
<section class="method" id="method_<?php echo $i; ?>">
	<header>
		<h1><?php echo $method->title(); ?></h1>
		<ul class="tabBar">
			<li id="tab_details<?php echo $i; ?>">Details</li>
			<li id="tab_line<?php echo $i; ?>" class="active">Line</li>
			<li id="tab_grid<?php echo $i; ?>">Grid</li>
		</ul>
	</header>
	<div id="content_details<?php echo $i; ?>" class="methodDetails">
		<noscript><h2>Details</h2></noscript>
		<table class="horizontalDetails">
			<tr>
				<th>Classification:</th>
				<td><?php echo ($method->differential()?'Differential ':'') . ($method->little()?'Little ' :'') . $method->classification() .' '. Stages::fromInt( $method->stage() ); ?></td>
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
				<td><?php echo $method->numberOfHunts(); ?></td>
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
		if( typeof( methods ) == 'undefined' ) { var methods = new Array(); }
		methods.push( new MethodView( {
			id: <?php echo $i; ?>,
			stage: <?php echo intval( $method->stage() ); ?>,
			notation: <?php echo json_encode( $method->notationExpanded() ); ?>,
			leadHead: <?php echo json_encode( $method->leadHead() ); ?>,
			options_line: {
				container: 'content_line<?php echo $i; ?>'
<?php if( $method->ruleOffs() ) : $ruleOffs = explode( ':', $method->ruleOffs() ); ?>
				,ruleOffs: { every: <?php echo $ruleOffs[0]; ?>, from: <?php echo $ruleOffs[1]; ?>, color: '#999' }
<?php endif; ?>
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
