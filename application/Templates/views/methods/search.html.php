<?php
namespace Blueline;
use \Helpers\Text;

View::element( 'default.header', array(
	'title' => 'Search | Methods | Blueline',
	'q' => $q,
	'breadcrumb' => array(
		'<a href="/methods">Methods</a>'
	),
	'bigSearch' => array(
		'action' => '/methods/search',
		'placeholder' => 'Search methods'
	)
) );
?>
<section class="content search">
<?php if( count( $methods ) == 0 ) : ?>
	<ol class="searchResults">
		<li>No results</li>
	</ol>
<?php else : ?>
	<ol class="searchResults">
<?php foreach( $methods as $method ) : ?>
		<li><a href="<?php echo $method->href(); ?>"><?php echo $method->title(); ?></a></li>
<?php endforeach; ?>
	</ol>
<?php View::element( 'paging', compact( 'limit', 'count' ) ); ?>
<?php endif; ?>
</section>
<?php View::element( 'default.footer' ); ?>
