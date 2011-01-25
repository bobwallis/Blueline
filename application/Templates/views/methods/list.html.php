<?php
namespace Blueline;

View::element( 'default.header', array(
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
<?php foreach( $methods as $method ) : ?>
			<li><a href="<?php echo $method->href(); ?>"><?php echo $method->title(); ?></a></li>
<?php endforeach; ?>
		</ol>
	</div>
</section>
<?php View::element( 'default.footer' ); ?>
