<?php
namespace Blueline;
use \Helpers\Text;

View::element( 'default.header', array(
	'title' => 'Search | Towers | Blueline',
	'breadcrumb' => array(
		'<a href="/associations">Associations</a>'
	)
) );
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
<?php View::element( 'default.footer' ); ?>

