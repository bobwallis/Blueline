<?php
namespace Blueline;
use Pan\View;

View::cache( true );

View::element( 'header', array(
	'title' => 'Towers | Blueline',
	'breadcrumb' => array(
		'<a href="/towers">Towers</a>'
	),
	'headerSearch' => array(
			'action' => '/towers/search',
			'placeholder' => 'Search towers'
	),
	'bigSearch' => false
) );
?>
<section class="search">
	<header>
		<h1>Towers</h1>
	</header>
	<div class="content">
		<ol class="searchResults">
<?php foreach( $this->get( 'towers', array() ) as $tower ) : ?>
			<li><?="<a href=\"{$tower->href()}\">{$tower->place()} <small>({$tower->dedication()})</small></a>"?></li>
<?php endforeach; ?>
		</ol>
	</div>
</section>
<?php View::element( 'footer' ); ?>
