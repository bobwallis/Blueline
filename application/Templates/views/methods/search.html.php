<?php
namespace Blueline;
use \Helpers\Text;

View::element( 'default.header', array(
	'title' => 'Search | Methods | Blueline',
	'breadcrumb' => array(
		'<a href="/methods">Methods</a>'
	)
) );
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
		<li><a href="<?php echo $method->href(); ?>"><?php echo $method->title(); ?></a></li>
<?php endforeach; ?>
	</ol>
<?php View::element( 'paging', compact( 'limit', 'count' ) ); ?>
</section>
<?php View::element( 'default.footer' ); ?>
