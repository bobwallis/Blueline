<?php
namespace Blueline;
if( !Response::snippet() ) :
?>
<!doctype html>
<?php if( Config::get( 'development' ) ) : ?>
<html lang="en-gb">
<?php else: ?>
<html lang="en-gb" manifest="/site.manifest">
<?php endif; ?>
	<head>
		<meta charset="utf-8" />
		<!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><![endif]-->
		<title><?php echo isset( $title )? $title : 'Blueline'; ?></title>
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="format-detection" content="telephone=no" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<meta name="application-name" content="Blueline" />
		<meta name="msapplication-starturl" content="<?php echo $site['baseURL']; ?>" />
		<meta name="msapplication-navbutton-color" content="#002147" />
<?php if( isset( $ICBM ) ) : ?>
		<meta name="icbm" content="<?php echo $ICBM['latitude']; ?>, <?php echo $ICBM['longitude']; ?>" />
		<meta name="geo.position" content="<?php echo $ICBM['latitude']; ?>;<?php echo $ICBM['longitude']; ?>" />
		<meta name="geo.placename" content="<?php echo $ICBM['placename']; ?>" />
<?php endif; ?>
		<link rel="search" type="application/opensearchdescription+xml" href="/services/opensearch/associations.xml" title="Associations | Blueline" />
		<link rel="search" type="application/opensearchdescription+xml" href="/services/opensearch/methods.xml" title="Methods | Blueline" />
		<link rel="search" type="application/opensearchdescription+xml" href="/services/opensearch/towers.xml" title="Towers | Blueline" />
		<link rel="icon" type="image/svg+xml" href="/favicon.svg" sizes="any" /> 
		<link rel="alternate shortcut icon" type="image/x-ixon" href="/favicon.ico" sizes="16x16,24x24,48x48" />
		<!--[if lt IE 9]><script src="/scripts/ieCompat.js"></script><![endif]-->
		<link rel="stylesheet" media="all" href="/styles/core.css" />
		<link rel="stylesheet" media="screen" href="/styles/normal.css" />
		<link rel="stylesheet" media="handheld, screen and (max-width:480px)" href="/styles/small-device.css" />
		<link rel="stylesheet" media="print" href="/styles/print.css" />
		<noscript><link rel="stylesheet" media="all" href="/styles/noscript.css" /></noscript>
		<script data-main="/scripts/main" src="/scripts/require.js"></script>
<?php $gaTrackingCode = Config::get( 'ga.trackingCode' ); if( !empty( $gaTrackingCode ) ) : ?>
		<script>var _gaq=_gaq || [];_gaq.push(['_setAccount', '<?php echo $gaTrackingCode; ?>']);_gaq.push(['_trackPageview']);(function(){var ga=document.createElement('script');ga.type='text/javascript';ga.async=true;ga.src=('https:'==document.location.protocol?'https://ssl':'http://www')+'.google-analytics.com/ga.js';var s=document.getElementsByTagName('script')[0];s.parentNode.insertBefore(ga,s);})();</script>
<?php endif; ?>
	</head>
	<body>
		<div id="wrapper">
			<header id="top" role="banner">
				<h1><a href="/">Blueline</a></h1>
				<div id="breadcrumbContainer">
<?php if( isset( $breadcrumb) ) : foreach( $breadcrumb as $b ) : ?>
					<span class="headerSep">&raquo;</span>
					<h2><?php echo $b; ?></h2>
<?php endforeach; endif; ?>
				</div>
				<div id="topSearchContainer"<?php echo (!isset( $headerSearch ))?' style="display: none;"':''; ?>>
					<span class="headerSep small_hide">&raquo;</span>
					<form id="topSearch" role="search" action="<?php echo isset( $headerSearch['action'] )? $headerSearch['action'] : '/search'; ?>">
						<input type="text" accesskey="/" name="q" id="smallQ" spellcheck="false" autocomplete="off" placeholder="<?php echo isset($headerSearch['placeholder'])? $headerSearch['placeholder']:'Search'; ?>" />
						<button type="submit" title="Search"><span class="hide">Search</span></button>
					</form>
				</div>
			</header>
			<div id="bigSearchContainer"<?php echo (!isset( $bigSearch ))?' style="display: none;"':''; ?>>
				<form id="bigSearch" role="search" action="<?php echo isset( $bigSearch['action'] )? $bigSearch['action'] : '/search'; ?>">
					<div>
						<input type="text" accesskey="/" name="q" id="bigQ" spellcheck="false" autocomplete="off" placeholder="<?php echo isset( $bigSearch['placeholder'] )? $bigSearch['placeholder'] : 'Search'; ?>" value="<?php echo isset( $q )? htmlentities( $q ) : ''; ?>" />
						<button type="submit" title="Search"><span class="hide">Search</span></button>
					</div>
				</form>
			</div>
			<section id="content" role="main">
<?php endif; ?>
