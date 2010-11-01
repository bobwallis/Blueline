<?php
namespace Blueline;
use \Helpers\Text;

$title_for_layout = 'Search | Towers | Blueline';
$breadcrumb = array(
	'<a href="/towers">Towers</a>'
);
$scripts_for_layout = array(
	'/scripts/general.js'
);
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

