<?php
$title_for_layout = 'Towers | Blueline';
$breadcrumb = array(
	'<a href="/towers">Towers</a>'
);
$headerSearch = array( 
	'action' => '/towers/search',
	'placeholder' => 'Search towers'
);
$scripts_for_layout = array(
	'/scripts/general.js'
);
?>
<section class="search">
	<header>
		<h1>Towers</h1>
	</header>
	<ol class="searchResults">
<?php foreach( $towers as $tower ) : ?>
		<li><?php echo '<a href="/towers/view/'.$tower['doveId'].'">' . $tower['place'].' <small>('.$tower['dedication'].')</small></a>'; ?></li>
<?php endforeach; ?>
	</ol>
</section>

