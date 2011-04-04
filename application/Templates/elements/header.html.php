<?php
namespace Blueline;

if( !\Pan\Response::snippet() ) :
	$site = $this->get( 'site' );
?>
<!doctype html>
<?php echo ( (!$site['development'] && $this->get( 'manifest', false ))? '<html lang="en-gb" manifest="/site.manifest">' : '<html lang="en-gb">' )."\n" ?>
	<head>
		<meta charset="utf-8" />
		<!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><![endif]-->
		<title><?=$this->get( 'title', false )?:'Blueline'?></title>
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="format-detection" content="telephone=no" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<meta name="application-name" content="Blueline" />
		<meta name="msapplication-starturl" content="<?php echo $site['baseURL']; ?>" />
		<meta name="msapplication-navbutton-color" content="#002147" />
<?php if( isset( $ICBM ) ) : ?>
		<meta name="icbm" content="<?=$ICBM['latitude'].', '.$ICBM['longitude']?>" />
		<meta name="geo.position" content="<?=$ICBM['latitude'].';'.$ICBM['longitude']?>" />
		<meta name="geo.placename" content="<?=$ICBM['placename']?>" />
<?php endif; ?>
		<link rel="search" type="application/opensearchdescription+xml" href="/services/opensearch/associations.xml" title="Associations | Blueline" />
		<link rel="search" type="application/opensearchdescription+xml" href="/services/opensearch/methods.xml" title="Methods | Blueline" />
		<link rel="search" type="application/opensearchdescription+xml" href="/services/opensearch/towers.xml" title="Towers | Blueline" />
		<link rel="icon" type="image/svg+xml" href="/favicon.svg" sizes="any" />
		<link rel="alternate shortcut icon" type="image/x-ixon" href="/favicon.ico" sizes="16x16,24x24,48x48" />
		<!--[if lt IE 9]><script src="/scripts/ieCompat.js"></script><![endif]-->
		<link rel="stylesheet" media="all" href="/styles<?=$site['development']?'.built':''?>/main.css" />
		<link rel="stylesheet" media="print" href="/styles<?=$site['development']?'.built':''?>/print.css" />
<?php if( $site['development'] ) : ?>
		<script src="/scripts/helpers/jquery.js"></script>
		<script data-main="/scripts/main" src="/scripts/require.js"></script>
<?php else : ?>
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
		<script data-main="/scripts.built/main" src="/scripts.built/main.js"></script>
<?php endif; ?>
<?php if( !empty( $site['ga_trackingCode'] ) ) : ?>
		<script>var _gaq=_gaq || [];_gaq.push(['_setAccount', '<?=$site['ga_trackingCode']?>']);_gaq.push(['_trackPageview']);(function(){var ga=document.createElement('script');ga.type='text/javascript';ga.async=true;ga.src=('https:'==document.location.protocol?'https://ssl':'http://www')+'.google-analytics.com/ga.js';var s=document.getElementsByTagName('script')[0];s.parentNode.insertBefore(ga,s);})();</script>
<?php endif; ?>
	</head>
	<body>
		<div id="wrapper">
			<header id="top" role="banner">
				<h1><a href="/">Blueline</a></h1>
				<div id="breadcrumbContainer">
<?php foreach( $this->get( 'breadcrumb', array() ) as $b ) : ?>
					<span class="headerSep">&raquo;</span>
					<h2><?=$b?></h2>
<?php endforeach; ?>
				</div>
				<div id="topSearchContainer"<?=(!$this->get( 'headerSearch', false ))?' style="display: none;"':''?>>
					<span class="headerSep small_hide">&raquo;</span>
					<form id="topSearch" role="search" action="<?=$this->get( 'headerSearch[action]', false )?:'/search'?>">
						<input type="text" accesskey="/" name="q" id="smallQ" spellcheck="false" autocomplete="off" placeholder="<?=$this->get( 'headerSearch[placeholder]', false )?:'Search'?>" />
						<button type="submit" title="Search"><span class="hide">Search</span></button>
					</form>
				</div>
			</header>
			<div id="bigSearchContainer"<?=(!$this->get( 'bigSearch', false ))?' style="display: none;"':''?>>
				<form id="bigSearch" role="search" action="<?=$this->get( 'bigSearch[action]', false )?:'/search'?>">
					<div>
					<input type="text" accesskey="/" name="q" id="bigQ" spellcheck="false" autocomplete="off" placeholder="<?=$this->get( 'bigSearch[placeholder]', false )?:'Search'?>" value="<?=$this->encode( 'q', '' )?>" />
						<button type="submit" title="Search"><span class="hide">Search</span></button>
					</div>
				</form>
			</div>
			<section id="content" role="main">
<?php endif; ?>
