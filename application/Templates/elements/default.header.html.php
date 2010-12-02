<?php
namespace Blueline;
?>
<!doctype html>
<html lang="en-gb">
	<head>
		<meta charset="utf-8" />
		<!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><![endif]-->
		<title><?php echo isset( $title )? $title : 'Blueline'; ?></title>
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<meta name="application-name" content="Blueline"/>
<?php if( isset( $ICBM ) ) : ?>
		<meta name="icbm" content="<?php echo $ICBM['latitude']; ?>, <?php echo $ICBM['longitude']; ?>" />
		<meta name="geo.position" content="<?php echo $ICBM['latitude']; ?>;<?php echo $ICBM['longitude']; ?>" />
		<meta name="geo.placename" content="<?php echo $ICBM['placename']; ?>" />
<?php endif; ?>
		<link rel="search" type="application/opensearchdescription+xml" href="/services/opensearch/associations.xml" title="Associations | Blueline" />
		<link rel="search" type="application/opensearchdescription+xml" href="/services/opensearch/methods.xml" title="Methods | Blueline" />
		<link rel="search" type="application/opensearchdescription+xml" href="/services/opensearch/towers.xml" title="Towers | Blueline" />
		<link rel="shortcut icon" href="/favicon.ico" sizes="16x16" />
		<!--[if lt IE 9]><script src="/scripts/ieCompat.js"></script><![endif]-->
		<link rel="stylesheet" media="all" href="/styles/core.css" />
		<link rel="stylesheet" media="print" href="/styles/print.css" />
		<link rel="stylesheet" media="screen" href="/styles/normal.css" />
		<link rel="stylesheet" media="handheld, screen and (max-width:480px)" href="/styles/small-device.css" />
		<!--[if lt IE 9]><link rel="stylesheet" media="screen" href="/styles/normal.css" /><![endif]-->
		<noscript><link rel="stylesheet" media="all" href="/styles/noscript.css" /></noscript>
<?php if( isset( $scripts ) ): foreach( $scripts as $script ) : ?>
		<script src="<?php echo $script; ?>"></script>
<?php endforeach; endif; ?>
<?php $gaTrackingCode = Config::get( 'ga.trackingCode' ); if( !empty( $gaTrackingCode ) ) : ?>
		<script>var _gaq=_gaq || [];_gaq.push(['_setAccount', '<?php echo $gaTrackingCode; ?>']);_gaq.push(['_trackPageview']);(function(){var ga=document.createElement('script');ga.type='text/javascript';ga.async=true;ga.src=('https:'==document.location.protocol?'https://ssl':'http://www')+'.google-analytics.com/ga.js';var s=document.getElementsByTagName('script')[0];s.parentNode.insertBefore(ga,s);})();</script>
<?php endif; ?>
	</head>
	<body>
		<div id="wrapper">
			<header id="top" role="banner">
				<h1><a href="/">Blueline</a></h1>
<?php if( isset( $breadcrumb) ) : foreach( $breadcrumb as $b ) : ?>
				<span class="headerSep">&raquo;</span>
				<h2><?php echo $b; ?></h2>
<?php endforeach; endif; ?>
<?php if( isset( $headerSearch ) ) : ?>
				<span class="headerSep small_hide">&raquo;</span>
				<form id="topSearch" role="search" action="<?php echo $headerSearch['action']; ?>">
					<input type="text" accesskey="/" name="q" spellcheck="false" autocomplete="off" <?php echo (isset($headerSearch['placeholder']))? 'placeholder="'.$headerSearch['placeholder'].'"':''; ?> />
					<button type="submit" title="Search"><span class="hide">Search</span></button>
				</form>
<?php endif; ?>
			</header>
			<section id="content" role="main">

