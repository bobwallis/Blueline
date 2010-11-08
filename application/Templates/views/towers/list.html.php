<?php
namespace Blueline;

View::element( 'html.header', array(
	'title' => 'Towers | Blueline',
	'breadcrumb' => array(
		'<a href="/towers">Towers</a>'
	),
	'scripts' => array(
		'/scripts/general.js'
	)
) );
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
<?php View::element( 'html.footer' ); ?>
