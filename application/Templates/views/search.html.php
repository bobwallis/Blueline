<?php
namespace Blueline;
use \Helpers\Text;

View::element( 'default.header', array(
	'title' => 'Search | Blueline'
) );
?>
<section class="search">
	<header>
<?php
View::element( 'sectionSearch', array(
	'action' => '/search',
	'q' => $q,
	'placeholder' => 'Search',
	'extra' => Text::toList( array( Text::pluralise( $associationCount, 'association' ), Text::pluralise( $methodCount, 'method' ), Text::pluralise( $towerCount, 'tower' ) ) )
) );
?>
	</header>
	<ul>
<?php if( $associationCount > 0 ) : ?>
		<li>
			<h3>Associations:</h3>
			<ol class="searchResults">
<?php foreach( $associations as $association ) : ?>
				<li><a href="/associations/view/<?php echo urlencode( $association['abbreviation'] ); ?>"><?php echo $association['name']; ?></a></li>
<?php endforeach; ?>
			</ol>
<?php if( $associationCount > Model::$_searchLimit ) : ?>
			<h4><a href="/associations/search?<?php echo $queryString; ?>">More associations &raquo;</a></h4>
<?php endif; ?>
		</li>
<?php endif; ?>
<?php if( $methodCount > 0 ) : ?>
		<li>
			<h3>Methods:</h3>
			<ol class="searchResults">
<?php foreach( $methods as $method ) : ?>
				<li><a href="/methods/view/<?php echo str_replace( ' ', '_', $method['title'] ); ?>"><?php echo $method['title']; ?></a></li>
<?php endforeach; ?>
			</ol>
<?php if( $methodCount > Model::$_searchLimit ) : ?>
			<h4><a href="/methods/search?<?php echo $queryString; ?>">More methods &raquo;</a></h4>
<?php endif; ?>
		</li>
<?php endif; ?>
<?php if( $towerCount > 0 ) : ?>
		<li>
			<h3>Towers:</h3>
			<ol class="searchResults">
<?php foreach( $towers as $tower ) : ?>
				<li><?php echo '<a href="/towers/view/'.$tower['doveId'].'">' . $tower['place'].' <small>('.$tower['dedication'].')</small></a>'; ?></li>
<?php endforeach; ?>
<?php if( $towerCount > Model::$_searchLimit ) : ?>
			</ol><h4><a href="/towers/search?<?php echo $queryString; ?>">More towers &raquo;</a></h4>
<?php endif; ?>
		</li>
<?php endif; ?>
	</ul>
</section>
<?php View::element( 'default.footer' ); ?>
