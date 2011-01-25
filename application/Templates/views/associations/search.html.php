<?php
namespace Blueline;
use \Helpers\Text;

View::element( 'default.header', array(
	'title' => 'Search | Associations | Blueline',
	'breadcrumb' => array(
		'<a href="/associations">Associations</a>'
	),
	'bigSearch' => array(
		'action' => '/associations/search',
		'placeholder' => 'Search associations'
	)
) );
?>
<section class="content search">
<?php if( count( $associations ) == 0 ) : ?>
	<ol class="searchResults">
		<li>No results</li>
	</ol>
<?php else : ?>
	<ol class="searchResults">
<?php foreach( $associations as $association ) : ?>
		<li><a href="<?php echo $association->href(); ?>"><?php echo $association->name(); ?></a></li>
<?php endforeach; ?>
	</ol>
<?php View::element( 'paging', compact( 'limit', 'count' ) ); ?>
<?php endif; ?>
</section>
<?php View::element( 'default.footer' ); ?>

