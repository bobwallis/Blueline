<?php
namespace Blueline;

View::element( 'default.header', array(
	'title' => 'Associations | Blueline',
	'breadcrumb' => array(
		'<a href="/associations">Associations</a>'
	),
	'headerSearch' => array( 
		'action' => '/associations/search',
		'placeholder' => 'Search associations'
	),
	'scripts' => array(
		'/scripts/general.js'
	)
) );
?>
<header>
	<h1>Associations</h1>
</header>
<ol id="associationsList">
<?php foreach( $associations as $association ) : ?>
	<li><a href="<?php echo $association->href(); ?>"><?php echo htmlspecialchars( $association->name() ); ?></a></li>
<?php endforeach; ?>
</ol>
<?php View::element( 'default.footer' ); ?>
