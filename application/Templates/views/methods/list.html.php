<?php
$title_for_layout = 'Methods | Blueline';
$breadcrumb = array(
	'<a href="/methods">Methods</a>'
);
$headerSearch = array( 
	'action' => '/methods/search',
	'placeholder' => 'Search methods'
);
$scripts_for_layout = array(
	'/scripts/general.js'
);
?>
<section class="search">
	<header>
		<h1>Methods</h1>
	</header>
	<ol class="searchResults">
<?php foreach( $methods as $method ) : ?>
		<li><a href="/methods/view/<?php echo str_replace( ' ', '_', $method['title'] ); ?>"><?php echo $method['title']; ?></a></li>
<?php endforeach; ?>
	</ol>
</section>
