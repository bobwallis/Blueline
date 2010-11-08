<?php
namespace Blueline;
use \Helpers\Text;

View::element( 'default.header', array(
	'title' => 'Towers | Blueline',
	'breadcrumb' => array(
		'<a href="/towers">Towers</a>'
	),
	'scripts' => array(
		'/scripts/general.js'
	)
) );
?>
<section class="search">
	<header>
		<form class="sectionSearch" action="/towers/search">
			<div>
				<input type="text" accesskey="/" name="q" spellcheck="false" autocomplete="off" placeholder="Search towers" value="<?php echo isset($q)?htmlentities( $q ):''; ?>" />
				<button type="submit" title="Search"><span class="hide">Search</span></button>
			</div>
			<p class="fleft"><?php echo Text::pluralise( $count, 'tower' ); ?></p>
			<br style="clear: both;" />
		</form>
	</header>
</section>
<?php View::element( 'default.footer' ); ?>
