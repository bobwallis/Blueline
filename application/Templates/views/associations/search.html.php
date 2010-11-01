<?php
namespace Blueline;
use \Helpers\Text;

$title_for_layout = 'Search | Associations | Blueline';
$breadcrumb = array(
	'<a href="/associations">Associations</a>'
);
$scripts_for_layout = array(
	'/scripts/general.js'
);
?>
<section class="search">
	<header>
<?php
View::element( 'sectionSearch', array(
	'action' => '/associations/search',
	'q' => $q,
	'placeholder' => 'Search associations',
	'extra' => Text::pluralise( $count, 'association' )
) );
?>
	</header>
	<ol class="searchResults">
<?php foreach( $associations as $association ) : ?>
		<li><a href="/associations/view/<?php echo urlencode( $association['abbreviation'] ); ?>"><?php echo $association['name']; ?></a></li>
<?php endforeach; ?>
	</ol>
<?php View::element( 'paging', compact( 'limit' ) ); ?>
</section>

