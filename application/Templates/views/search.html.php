<?php
namespace Blueline;
use \Helpers\Text;

View::element( 'default.header', array(
	'title' => 'Search | Blueline',
	'q' => $q,
	'bigSearch' => array(
		'action' => '/search',
		'placeholder' => 'Search'
	)
) );
?>
<section class="search">
	<ul class="searchResults">
<?php if( $associationCount > 0 ) : ?>
		<li>
			<h3>Associations:</h3>
			<ol class="searchResults">
<?php foreach( $associations as $association ) : ?>
				<li><a href="<?php echo $association->href(); ?>"><?php echo $association->name(); ?></a></li>
<?php endforeach; ?>
			</ol>
<?php if( $associationCount > $searchLimit ) : ?>
			<h4><a href="/associations/search?<?php echo $queryString; ?>">More associations &raquo;</a></h4>
<?php endif; ?>
		</li>
<?php endif; ?>
<?php if( $methodCount > 0 ) : ?>
		<li>
			<h3>Methods:</h3>
			<ol class="searchResults">
<?php foreach( $methods as $method ) : ?>
				<li><a href="<?php echo $method->href(); ?>"><?php echo $method->title(); ?></a></li>
<?php endforeach; ?>
			</ol>
<?php if( $methodCount > $searchLimit ) : ?>
			<h4><a href="/methods/search?<?php echo $queryString; ?>">More methods &raquo;</a></h4>
<?php endif; ?>
		</li>
<?php endif; ?>
<?php if( $towerCount > 0 ) : ?>
		<li>
			<h3>Towers:</h3>
			<ol class="searchResults">
<?php foreach( $towers as $tower ) : ?>
				<li><?php echo "<a href=\"{$tower->href()}\">{$tower->place()} <small>({$tower->dedication()})</small></a>"; ?></li>
<?php endforeach; ?>
<?php if( $towerCount > $searchLimit ) : ?>
			</ol><h4><a href="/towers/search?<?php echo $queryString; ?>">More towers &raquo;</a></h4>
<?php endif; ?>
		</li>
<?php endif; ?>
	</ul>
</section>
<?php View::element( 'default.footer' ); ?>
