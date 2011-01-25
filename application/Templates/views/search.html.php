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
<section class="content search">
<?php if( $associationCount == 0 && $towerCount == 0 && $methodCount == 0 ) : ?>
	<ol class="searchResults">
		<li>No results</li>
	</ol>
<?php else : ?>
	<ul class="searchResults">
<?php if( $associationCount > 0 ) : ?>
		<li>
			<h3>Associations:</h3>
			<ol class="searchResults">
<?php foreach( $associations as $association ) : ?>
				<li><a href="<?php echo $association->href(); ?>"><?php echo $association->name(); ?></a></li>
<?php endforeach; ?>
<?php if( $associationCount > $searchLimit ) : ?>
				<li><strong><a href="/associations/search?<?php echo $queryString; ?>">More associations &raquo;</a></strong></li>
<?php endif; ?>
			</ol>
		</li>
<?php endif; ?>
<?php if( $methodCount > 0 ) : ?>
		<li>
			<h3>Methods:</h3>
			<ol class="searchResults">
<?php foreach( $methods as $method ) : ?>
				<li><a href="<?php echo $method->href(); ?>"><?php echo $method->title(); ?></a></li>
<?php endforeach; ?>
<?php if( $methodCount > $searchLimit ) : ?>
				<li><strong><a href="/methods/search?<?php echo $queryString; ?>">More methods &raquo;</a></strong></li>
<?php endif; ?>
			</ol>
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
				<li><strong><a href="/towers/search?<?php echo $queryString; ?>">More towers &raquo;</a></strong></li>
<?php endif; ?>
			</ol>
		</li>
<?php endif; ?>
	</ul>
<?php endif; ?>
</section>
<?php View::element( 'default.footer' ); ?>
