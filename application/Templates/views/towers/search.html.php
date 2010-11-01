<?php
namespace Blueline;
use \Helpers\Text;

$title_for_layout = 'Search | Towers | Blueline';
$breadcrumb = array(
	'<a href="/towers">Towers</a>'
);
$scripts_for_layout = array(
	'/scripts/general.js'
);
?>
<section class="search">
	<header>
		<form class="sectionSearch" action="/towers/search">
			<div>
				<input type="text" accesskey="/" name="q" spellcheck="false" autocomplete="off" placeholder="Search methods" value="<?php echo htmlentities( $q ); ?>" />
				<button type="submit" title="Search"><span class="hide">Search</span></button>
			</div>
			<p class="fleft"><?php echo Text::pluralise( $count, 'tower' ); ?></p>
			<br style="clear: both;" />
		</form>
	</header>
	<ol class="searchResults">
<?php foreach( $towers as $tower ) : ?>
		<li><?php echo '<a href="/towers/view/'.$tower['doveId'].'">' . $tower['place'].' <small>('.$tower['dedication'].')</small></a>'; ?></li>
<?php endforeach; ?>
	</ol>
<?php include( TEMPLATE_PATH.'/elements/paging.html.php' ); ?>
</section>

