<?php
namespace Blueline;

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
		<form class="sectionSearch" action="/associations/search">
			<div>
				<input type="text" accesskey="/" name="q" spellcheck="false" autocomplete="off" placeholder="Search associations" value="<?php echo htmlentities( $q ); ?>" />
				<button type="submit" title="Search"><span class="hide">Search</span></button>
			</div>
			<p class="fleft"><?php echo $associationCount; ?> associations<?php echo ($associationCount > Model::$_searchLimit)? ' | <a href="/associations/search?'.$searchQuery.'">Show all &raquo;</a>' : ''; ?></p>
			<br style="clear: both;" />
		</form>
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
		<form class="sectionSearch" action="/methods/search">
			<div>
				<input type="text" accesskey="/" name="q" spellcheck="false" autocomplete="off" placeholder="Search methods" value="<?php echo isset($q)?htmlentities( $q ):'';; ?>" />
				<button type="submit" title="Search"><span class="hide">Search</span></button>
			</div>
			<p class="fleft"><?php echo $methodCount; ?> methods<?php echo ($methodCount > Model::$_searchLimit)? ' | <a href="/methods/search?'.$searchQuery.'">Show all &raquo;</a>' : ''; ?></p>
			<br style="clear: both;" />
		</form>
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
		<form class="sectionSearch" action="/towers/search">
			<div>
				<input type="text" accesskey="/" name="q" spellcheck="false" autocomplete="off" placeholder="Search methods" value="<?php echo htmlentities( $q ); ?>" />
				<button type="submit" title="Search"><span class="hide">Search</span></button>
			</div>
			<p class="fleft"><?php echo $towerCount; ?> towers<?php echo ($towerCount > Model::$_searchLimit)? ' | <a href="/towers/search?'.$searchQuery.'">Show all &raquo;</a>' : ''; ?></p>
			<br style="clear: both;" />
		</form>
	</header>
	<ol class="searchResults">
<?php foreach( $towers as $tower ) : ?>
		<li><?php echo '<a href="/towers/view/'.$tower['doveId'].'">' . $tower['place'].' <small>('.$tower['dedication'].')</small></a>'; ?></li>
<?php endforeach; ?>
	</ol>
</section>

