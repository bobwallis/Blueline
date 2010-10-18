<?php
namespace Blueline;

$title_for_layout = 'Associations | Blueline';
$headerSearch = array( 
	'action' => '/search',
	'placeholder' => 'Search'
);
?>
<header>
	<h1>Associations</h1>
</header>
<ol id="associationsList">
<?php foreach( $associations as $association ) : ?>
	<li><a href="/associations/view/<?php echo htmlspecialchars( $association['abbreviation'] ); ?>"><?php echo htmlspecialchars( $association['name'] ); ?></a></li>
<?php endforeach; ?>
</ol>
