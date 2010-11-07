<?php
namespace Blueline;
View::layout( 'blank' );
?>
<ul>
<?php
for( $i = 0; $i < count( $suggestions['queries'] ); ++$i ) {
	echo "<li><a href=\"{$suggestions['URLs'][$i]}\">{$suggestions['queries'][$i]}</a></li>\n";
}
?>
</ul>
