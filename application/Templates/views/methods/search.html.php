<?php
namespace Blueline;
use Pan\View, Helpers\Text;

View::cache( true );

$methods = $this->get( 'methods', array() );

View::element( 'header', array(
	'title' => 'Search | Methods | Blueline',
	'q' => $this->get( 'q' ),
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
		<li><a href="<?=$method->href()?>"><?=$method->title()?></a></li>
<?php endforeach; ?>
</ol>
<?php View::element( 'paging', array( 'limit' => $this->get( 'limit' ), 'count' => $this->get( 'count' ), 'queryString' => $this->get( 'queryString' ) ) ); ?>
<?php endif; ?>
</section>
<?php View::element( 'footer' ); ?>
