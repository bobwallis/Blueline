<!doctype html>
<html lang="en-gb">
	<head>
		<meta charset="utf-8" />
		<!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><![endif]-->
		<title><?php echo $title_for_layout; ?></title>
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<meta name="application-name" content="Blueline"/>
<?php if( isset( $ICBM ) ) : ?>
		<meta name="icbm" content="<?php echo $ICBM['latitude']; ?>, <?php echo $ICBM['longitude']; ?>" />
		<meta name="geo.position" content="<?php echo $ICBM['latitude']; ?>;<?php echo $ICBM['longitude']; ?>" />
		<meta name="geo.placename" content="<?php echo $ICBM['placename']; ?>" />
<?php endif; ?>
		<!--[if lt IE 9]><script src="/scripts/html5.js"></script><![endif]-->
		<link rel="shortcut icon" href="/favicon.ico" sizes="16x16" />
		<link rel="stylesheet" media="all" href="/styles/core.css" />
		<link rel="stylesheet" media="print" href="/styles/print.css" />
		<link rel="stylesheet" media="screen and (max-width:480px)" href="/styles/small-device.css" />
		<link rel="stylesheet" media="screen and (min-width:481px)" href="/styles/normal.css" />
		<!--[if lt IE 9]><link rel="stylesheet" media="screen" href="/styles/normal.css" /><![endif]-->
		<noscript><link rel="stylesheet" media="all" href="/styles/noscript.css" /></noscript>
<?php if( isset( $scripts_for_layout ) ): foreach( $scripts_for_layout as $script ) : ?>
		<script src="<?php echo $script; ?>"></script>
<?php endforeach; endif; ?>
	</head>
	<body>
		<div id="wrapper">
			<header id="top">
				<h1><a href="/">Blueline</a></h1>
<?php if( isset( $breadcrumb) ) : foreach( $breadcrumb as $b ) : ?>
				<span class="headerSep">&raquo;</span>
				<h2><?php echo $b; ?></h2>
<?php endforeach; endif; ?>
<?php if( isset( $headerSearch ) ) : ?>
				<span class="headerSep small_hide">&raquo;</span>
				<form id="topSearch" action="<?php echo $headerSearch['action']; ?>">
					<input type="text" accesskey="/" name="q" spellcheck="false" autocomplete="off" <?php echo (isset($headerSearch['placeholder']))? 'placeholder="'.$headerSearch['placeholder'].'"':''; ?> />
					<button type="submit" title="Search"><span class="hide">Search</span></button>
				</form>
<?php endif; ?>
			</header>
			<section id="content">
<?php echo $content_for_layout; ?>
			</section>
			<div id="push"></div>
		</div>
		<footer id="bottom">
			<nav><a href="/">Home</a> | <a href="/associations">Associations</a> | 	<a href="/methods">Methods</a> | <a href="/towers">Towers</a></nav>
			<p><a href="/copyright">Copyright</a> | Blueline &copy; MMX</p>
		</footer>
	</body>
</html>
