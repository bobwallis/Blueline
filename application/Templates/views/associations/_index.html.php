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
	)
) );
?>
<header>
	<h1>Associations</h1>
</header>
<div class="content">
	<ol id="associationsList">
<?php foreach( $associations as $association ) : ?>
		<li><a href="<?php echo $association->href(); ?>"><?php echo htmlspecialchars( $association->name() ); ?></a></li>
<?php endforeach; ?>
	</ol>
</div>
<?php View::element( 'default.footer' ); ?>
