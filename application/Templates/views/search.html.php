<?php
namespace Blueline;
use Pan\View, \Helpers\Text;

View::cache( true );

View::element( 'header', array(
	'title' => 'Search | Blueline',
	'q' => $this->get( 'q' ),
	'bigSearch' => array(
		'action' => '/search',
		'placeholder' => 'Search'
	)
) );
$associationCount = $this->get( 'associationCount', 0 );
$towerCount = $this->get( 'towerCount', 0 );
$methodCount = $this->get( 'methodCount', 0 );
$searchLimit = $this->get( 'searchLimit', 0 );
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
<?php foreach( $this->get( 'associations', array() ) as $association ) : ?>
				<li><a href="<?=$association->href()?>"><?=$association->name()?></a></li>
<?php endforeach; ?>
<?php if( $associationCount > $searchLimit ) : ?>
				<li><strong><a href="/associations/search?<?=$this->get( 'queryString' )?>">More associations &raquo;</a></strong></li>
<?php endif; ?>
			</ol>
		</li>
<?php endif; ?>
<?php if( $methodCount > 0 ) : ?>
		<li>
			<h3>Methods:</h3>
			<ol class="searchResults">
<?php foreach( $this->get( 'methods', array() ) as $method ) : ?>
				<li><a href="<?=$method->href()?>"><?=$method->title()?></a></li>
<?php endforeach; ?>
<?php if( $methodCount > $searchLimit ) : ?>
				<li><strong><a href="/methods/search?<?=$this->get( 'queryString' )?>">More methods &raquo;</a></strong></li>
<?php endif; ?>
			</ol>
		</li>
<?php endif; ?>
<?php if( $towerCount > 0 ) : ?>
		<li>
			<h3>Towers:</h3>
			<ol class="searchResults">
<?php foreach( $this->get( 'towers', array() ) as $tower ) : ?>
				<li><?="<a href=\"{$tower->href()}\">{$tower->place()} <small>({$tower->dedication()})</small></a>"?></li>
<?php endforeach; ?>
<?php if( $towerCount > $searchLimit ) : ?>
				<li><strong><a href="/towers/search?<?=$this->get( 'queryString' )?>">More towers &raquo;</a></strong></li>
<?php endif; ?>
			</ol>
		</li>
<?php endif; ?>
	</ul>
<?php endif; ?>
</section>
<?php View::element( 'footer' );
