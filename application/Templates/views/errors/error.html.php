<?php
namespace Blueline;
use Pan\View;

View::element( 'header', array(
	'title' => $this->get( 'errorTitle' ).' | Blueline',
	'headerSearch' => array(
		'action' => '/search',
		'placeholder' => 'Search'
	)
) );
?>
<header>
	<h1><?=$this->get( 'errorTitle', 'Unknown Error' )?:''?></h1>
</header>
<div class="content">
	<p>Try a search, or visit the homepage to find what you're looking for.</p>
	<?=($this->get( 'site[development]' ) && $this->get( 'errorMessage', false ) )? '<p>'.str_replace( "\n", "<br />\n", $this->get( 'errorMessage' ) ).'</p>' : ''?>
</div>
<?php View::element( 'footer' ); ?>
