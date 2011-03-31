<?php
namespace Blueline;
use Pan\View, Helpers\Text;

View::cache( true );

$towers = $this->get( 'towers', array() );

View::element( 'header', array(
	'title' => 'Search | Towers | Blueline',
	'q' => $this->get( 'q' ),
	'breadcrumb' => array(
		'<a href="/towers">Towers</a>'
	),
	'bigSearch' => array(
		'action' => '/towers/search',
		'placeholder' => 'Search towers'
	)
) );
?>
<section class="content search">
<?php if( count( $towers ) == 0 ) : ?>
	<ol class="searchResults">
		<li>No results</li>
	</ol>
<?php else : ?>
	<ol class="searchResults">
<?php foreach( $towers as $tower ) : ?>
		<li><?="<a href=\"{$tower->href()}\">{$tower->place()} <small>({$tower->dedication()})</small></a>"?></li>
<?php endforeach; ?>
</ol>
<?php View::element( 'paging', array( 'limit' => $this->get( 'limit' ), 'count' => $this->get( 'count' ), 'queryString' => $this->get( 'queryString' ) ) ); ?>
<?php endif; ?>
</section>
<?php View::element( 'footer' ); ?>
