<?php
namespace Blueline;

View::element( 'default.header', array(
	'title' => $errorTitle.' | Blueline',
	'headerSearch' => array( 
		'action' => '/search',
		'placeholder' => 'Search'
	)
) );
?>
<header>
	<h1><?php echo $errorTitle; ?></h1>
</header>
<div class="content wallOfText">
	<p>Try a search, or visit the homepage to find what you're looking for.</p>
	<?php echo ( Config::get( 'development' ) && isset( $errorMessage ) )? '<p>'.str_replace( "\n", "<br />\n", $errorMessage ).'</p>' : ''; ?>
</div>
<?php View::element( 'default.footer' ); ?>
