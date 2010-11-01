<?php
namespace Blueline;
use \Helpers\Text;

$title_for_layout = 'Blueline';
?>
<section class="search">
	<header>
		<form class="sectionSearch" action="/search">
			<div>
				<input type="text" accesskey="/" name="q" spellcheck="false" autocomplete="off" placeholder="Search" value="" />
				<button type="submit" title="Search"><span class="hide">Search</span></button>
			</div>
			<p class="fleft"><?php echo Text::toList( array( Text::pluralise( $associationCount, 'association' ), Text::pluralise( $methodCount, 'method' ), Text::pluralise( $towerCount, 'tower' ) ) ); ?></p>
			<br style="clear: both;" />
		</form>
	</header>
</section>
