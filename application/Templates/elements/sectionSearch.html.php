<form class="sectionSearch" role="search" action="<?php echo isset( $action )? $action : '/search'; ?>">
	<div>
		<input type="text" accesskey="/" name="q" spellcheck="false" autocomplete="off" placeholder="<?php echo isset( $placeholder )? $placeholder : 'Search'; ?>" value="<?php echo isset( $q )? htmlentities( $q ) : ''; ?>" />
		<button type="submit" title="Search"><span class="hide">Search</span></button>
	</div>
	<?php echo ( isset( $extra ) )? "<p>{$extra}</p>\n" : ''; ?>
</form>
