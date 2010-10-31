<?php
namespace Blueline;

$title_for_layout = 'Blueline';
?>
<section class="search">
	<header>
		<h1><a href="associations">Associations</a></h1>
		<form id="sectionSearch" action="/associations/search">
			<div>
				<input type="text" accesskey="/" name="q" spellcheck="false" autocomplete="off" placeholder="Search associations" value="" />
				<button type="submit" title="Search"><span class="hide">Search</span></button>
			</div>
			<p class="fleft"><?php echo $associationCount; ?> associations</p>
			<br style="clear: both;" />
		</form>
	</header>
</section>
<section class="search">
	<header>
		<h1><a href="/methods">Methods</a></h1>
		<form id="sectionSearch" action="/methods/search">
			<div>
				<input type="text" accesskey="/" name="q" spellcheck="false" autocomplete="off" placeholder="Search methods" value="" />
				<button type="submit" title="Search"><span class="hide">Search</span></button>
			</div>
			<p class="fleft"><?php echo $methodCount; ?> methods</p>
			<br style="clear: both;" />
		</form>
	</header>
</section>
<section class="search">
	<header>
		<h1><a href="/towers">Towers</a></h1>
		<form id="sectionSearch" action="/towers/search">
			<div>
				<input type="text" accesskey="/" name="q" spellcheck="false" autocomplete="off" placeholder="Search towers" value="" />
				<button type="submit" title="Search"><span class="hide">Search</span></button>
			</div>
			<p class="fleft"><?php echo $towerCount; ?> towers</p>
			<br style="clear: both;" />
		</form>
	</header>
</section>
