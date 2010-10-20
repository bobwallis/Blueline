<?php
namespace Blueline;

$title_for_layout = 'Associations | Blueline';
$headerSearch = array( 
	'action' => '/associations/search',
	'placeholder' => 'Search associations'
);
?>
<header>
	<h1>Associations</h1>
</header>
<ol id="associationsList">
<?php foreach( $associations as $association ) : ?>
	<li><a href="/associations/view/<?php echo urlencode( $association['abbreviation'] ); ?>"><?php echo htmlspecialchars( $association['name'] ); ?></a></li>
<?php endforeach; ?>
</ol>
