<li>
<?php
for( $i = 0; $i < count( $suggestions['queries'] ); ++$i ) {
	echo "<li><a href=\"{$suggestions['URLs'][$i]}\">{$suggestions['queries'][$i]}</a></li>\n";
}
?>
</li>
