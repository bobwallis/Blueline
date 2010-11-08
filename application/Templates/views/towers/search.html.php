<?php
namespace Blueline;
use \Helpers\Text;

View::element( 'default.header', array(
	'title' => 'Search | Towers | Blueline',
	'breadcrumb' => array(
		'<a href="/towers">Towers</a>'
	)
) );
?>
<section class="search">
	<header>
<?php View::element( 'sectionSearch', array(
	'action' => '/towers/search',
	'q' => $q,
	'placeholder' => 'Search towers',
	'extra' => Text::pluralise( $count, 'tower' )
) ); ?>
	</header>
	<ol class="searchResults">
<?php foreach( $towers as $tower ) : ?>
		<li><?php echo '<a href="/towers/view/'.$tower['doveId'].'">' . $tower['place'].' <small>('.$tower['dedication'].')</small></a>'; ?></li>
<?php endforeach; ?>
	</ol>
<?php View::element( 'paging', compact( 'limit' ) ); ?>
</section>
<?php View::element( 'default.footer' ); ?>
