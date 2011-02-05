<?php echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"; ?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 <?php echo $dimensions['grid']['x']; ?> <?php echo $dimensions['grid']['y']; ?>" preserveAspectRatio="xMidYMid">
<?php
for( $i = 0; $i < count( $paths ); ++$i ) {
	echo "\t<path stroke-linejoin=\"round\" stroke-linecap=\"round\" fill=\"none\" stroke=\"{$colours[$i]}\" stroke-width=\"{$widths[$i]}\" d=\"{$paths[$i]}\" />\n";
}
?>
</svg>
