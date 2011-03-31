<?php
namespace Blueline;
use Pan\View;

View::cache( true );

View::element( 'header', array(
	'title' => 'Methods | Blueline',
	'breadcrumb' => array(
		'<a href="/methods">Methods</a>'
	),
	'headerSearch' => array(
		'action' => '/methods/search',
		'placeholder' => 'Search methods'
	),
	'bigSearch' => false
) );
?>
<section class="search">
	<header>
		<h1>Methods</h1>
	</header>
	<div class="content">
		<ol class="searchResults">
<?php foreach( $this->get( 'methods', array() ) as $method ) : ?>
			<li><a href="<?=$method->href()?>"><?=$method->title()?></a></li>
<?php endforeach; ?>
		</ol>
	</div>
</section>
<?php View::element( 'footer' ); ?>
