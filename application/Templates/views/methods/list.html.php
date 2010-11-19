<?php
namespace Blueline;

View::element( 'default.header', array(
	'title' => 'Methods | Blueline',
	'breadcrumb' => array(
		'<a href="/methods">Methods</a>'
	)
	'headerSearch' => array( 
		'action' => '/methods/search',
		'placeholder' => 'Search methods'
	),
) );
?>
<section class="search">
	<header>
		<h1>Methods</h1>
	</header>
	<ol class="searchResults">
<?php foreach( $methods as $method ) : ?>
		<li><a href="<?php echo $method->href(); ?>"><?php echo $method->title(); ?></a></li>
<?php endforeach; ?>
	</ol>
</section>
<?php View::element( 'default.footer' ); ?>
