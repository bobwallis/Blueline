<?php
namespace Blueline;

View::element( 'default.header', array(
	'title' => 'Towers | Blueline',
	'breadcrumb' => array(
		'<a href="/towers">Towers</a>'
	)
) );
?>
<section class="search">
	<header>
		<h1>Towers</h1>
	</header>
	<div class="content">
		<ol class="searchResults">
<?php foreach( $towers as $tower ) : ?>
			<li><?php echo "<a href=\"{$tower->href()}\">{$tower->place()} <small>({$tower->dedication()})</small></a>"; ?></li>
<?php endforeach; ?>
		</ol>
	</div>
</section>
<?php View::element( 'default.footer' ); ?>
