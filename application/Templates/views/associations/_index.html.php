<?php
namespace Blueline;
use Pan\View;

View::cache( true );

View::element( 'header', array(
	'title' => 'Associations | Blueline',
	'breadcrumb' => array(
		'<a href="/associations">Associations</a>'
	),
	'headerSearch' => array(
		'action' => '/associations/search',
		'placeholder' => 'Search associations'
	)
) );
?>
<header>
	<h1>Associations</h1>
</header>
<div class="content">
	<ol id="associationsList">
<?php foreach( $this->get( 'associations', array() ) as $association ) : ?>
		<li><a href="<?=$association->href()?>"><?= htmlspecialchars( $association->name() )?></a></li>
<?php endforeach; ?>
	</ol>
</div>
<?php View::element( 'footer' ); ?>
