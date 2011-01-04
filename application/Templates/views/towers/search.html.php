<?php
namespace Blueline;
use \Helpers\Text;

View::element( 'default.header', array(
	'title' => 'Search | Towers | Blueline',
	'q' => $q,
	'breadcrumb' => array(
		'<a href="/towers">Towers</a>'
	),
	'bigSearch' => array(
		'action' => '/towers/search',
		'placeholder' => 'Search towers'
	)
) );
?>
<section class="search">
	<ol class="searchResults">
<?php foreach( $towers as $tower ) : ?>
		<li><?php echo "<a href=\"{$tower->href()}\">{$tower->place()} <small>({$tower->dedication()})</small></a>"; ?></li>
<?php endforeach; ?>
	</ol>
<?php View::element( 'paging', compact( 'limit', 'count' ) ); ?>
</section>
<?php View::element( 'default.footer' ); ?>
