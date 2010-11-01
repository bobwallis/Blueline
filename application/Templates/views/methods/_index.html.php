<?php
namespace Blueline;
use \Helpers\Text;

$title_for_layout = 'Methods | Blueline';
$breadcrumb = array(
	'<a href="/methods">Methods</a>'
);
$scripts_for_layout = array(
	'/scripts/general.js'
);
?>
<section class="search">
	<header>
		<form class="sectionSearch" action="/methods/search">
			<div>
				<input type="text" accesskey="/" name="q" spellcheck="false" autocomplete="off" placeholder="Search methods" value="<?php echo isset($q)?htmlentities( $q ):''; ?>" />
				<button type="submit" title="Search"><span class="hide">Search</span></button>
			</div>
			<p class="fleft"><?php echo Text::pluralise( $count, 'method' ); ?></p>
			<br style="clear: both;" />
		</form>
	</header>
</section>

