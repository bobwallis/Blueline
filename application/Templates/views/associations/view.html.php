<?php
namespace Blueline;

$title_for_layout = htmlspecialchars( preg_replace( '/, ([^,]*)$/', ' and $1', implode( array_map( function($a){return $a['name'];}, $associations ), ', ' ), 1 ) ) . ' | Associations | Blueline';
$breadcrumb = array(
	'<a href="/associations">Associations</a>'
);
$headerSearch = array( 
	'action' => '/search',
	'placeholder' => 'Search'
);
$scripts_for_layout = array(
	'/scripts/general.js'
);
$i = 0;
foreach( $associations as $association ) : ?>
<section class="association" id="association_<?php echo $i; ?>">
	<header>
		<h1><?php echo htmlspecialchars( $association['name'] ); ?></h1>
	</header>
	<dl>
<?php if( !empty( $association['link'] ) ) : ?>
		<dt>Link:</dt>
		<dd><a href="<?php echo htmlentities( $association['link'] ); ?>" class="external"><?php echo htmlentities( $association['link'] ); ?></a></dd>
<?php endif; ?>
		<dt>Affiliated Towers:</dt>
		<dd><?php echo $association['towerCount']; ?> <a href="/towers/search?affiliation=<?php echo htmlentities( $association['abbreviation'] ); ?>">See full list &raquo;</a></dd>
	</dl>
</section>
<?php ++$i; endforeach; ?>
