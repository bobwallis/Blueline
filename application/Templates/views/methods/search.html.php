<?php
namespace Blueline;
use \Helpers\Text;

$title_for_layout = 'Search | Methods | Blueline';
$breadcrumb = array(
	'<a href="/methods">Methods</a>'
);
$scripts_for_layout = array(
	'/scripts/general.js'
);
?>
<section class="search">
	<header>
<?php
		View::element( 'sectionSearch', array(
			'action' => '/methods/search',
			'q' => $q,
			'placeholder' => 'Search methods',
			'extra' => Text::pluralise( $count, 'method' )
		) );
?>
	</header>
	<ol class="searchResults">
<?php foreach( $methods as $method ) : ?>
		<li><a href="/methods/view/<?php echo str_replace( ' ', '_', $method['title'] ); ?>"><?php echo $method['title']; ?></a></li>
<?php endforeach; ?>
	</ol>
<?php View::element( 'paging', compact( 'limit' ) ); ?>
</section>

