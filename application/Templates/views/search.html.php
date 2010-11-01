<?php
namespace Blueline;
use \Helpers\Text;

$title_for_layout = 'Search | Blueline';
$headerSearch = array( 
	'action' => '/search',
	'placeholder' => 'Search'
);
$scripts_for_layout = array(
	'/scripts/general.js'
);
?>
<section class="search">
	<header>
		<h1>Associations</h1>
<?php
View::element( 'sectionSearch', array(
	'action' => '/associations/search',
	'q' => $q,
	'placeholder' => 'Search associations',
	'extra' => Text::pluralise( $associationCount, 'association' )
) );
?>
	</header>
	<ol class="searchResults">
<?php foreach( $associations as $association ) : ?>
		<li><a href="/associations/view/<?php echo urlencode( $association['abbreviation'] ); ?>"><?php echo $association['name']; ?></a></li>
<?php endforeach; ?>
	</ol>
</section>
<section class="search">
	<header>
		<h1>Methods</h1>
<?php
View::element( 'sectionSearch', array(
	'action' => '/methods/search',
	'q' => $q,
	'placeholder' => 'Search methods',
	'extra' => Text::pluralise( $methodCount, 'method' )
) );
?>
	</header>
	<ol class="searchResults">
<?php foreach( $methods as $method ) : ?>
		<li><a href="/methods/view/<?php echo str_replace( ' ', '_', $method['title'] ); ?>"><?php echo $method['title']; ?></a></li>
<?php endforeach; ?>
	</ol>
</section>
<section class="search">
	<header>
		<h1>Towers</h1>
<?php
View::element( 'sectionSearch', array(
	'action' => '/towers/search',
	'q' => $q,
	'placeholder' => 'Search towers',
	'extra' => Text::pluralise( $towerCount, 'tower' )
) );
?>
	</header>
	<ol class="searchResults">
<?php foreach( $towers as $tower ) : ?>
		<li><?php echo '<a href="/towers/view/'.$tower['doveId'].'">' . $tower['place'].' <small>('.$tower['dedication'].')</small></a>'; ?></li>
<?php endforeach; ?>
	</ol>
</section>

