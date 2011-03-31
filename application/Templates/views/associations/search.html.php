<?php
namespace Blueline;
use Pan\View, \Helpers\Text;

View::cache( true );

$associations = $this->get( 'associations', array() );

View::element( 'header', array(
	'title' => 'Search | Associations | Blueline',
	'q' => $this->get( 'q' ),
	'breadcrumb' => array(
		'<a href="/associations">Associations</a>'
	),
	'bigSearch' => array(
		'action' => '/associations/search',
		'placeholder' => 'Search associations'
	)
) );
?>
<section class="content search">
<?php if( count( $associations ) == 0 ) : ?>
	<ol class="searchResults">
		<li>No results</li>
	</ol>
<?php else : ?>
	<ol class="searchResults">
<?php foreach( $associations as $association ) : ?>
		<li><a href="<?php echo $association->href(); ?>"><?php echo $association->name(); ?></a></li>
<?php endforeach; ?>
	</ol>
<?php View::element( 'paging', array( 'limit' => $this->get( 'limit' ), 'count' => $this->get( 'count' ), 'queryString' => $this->get( 'queryString' ) ) ); ?>
<?php endif; ?>
</section>
<?php View::element( 'footer' ); ?>

