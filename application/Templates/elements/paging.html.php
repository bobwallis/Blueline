<?php
namespace Blueline;
if( isset( $limit ) ) :
	$limit = explode( ',', $limit );
	if( isset( $limit[1] ) ) {
		$from = $limit[0];
		$increment = $limit[1];
	}
	else {
		$from = 0;
		$increment = $limit[0];
	}
	$page = array(
		'number' => max( 1, ceil( ($from+1)/$increment ) ),
		'of' => max( 1, ceil( $count / $increment ) )
	);
	$queryString = trim( preg_replace( '/(&|^)from=.*?(&|$)/', '&', Request::queryString() ), '&' );
?>
<div class="paging">
	<p><?php echo 'Page '.$page['number'].' of '.$page['of']; ?></p>
<?php if( $page['of'] > 1 ) : ?>
	<div class="pagingLinks"><?php
		if( $page['number'] > 1 ) {
			echo '<a href="search?'.$queryString.'&from='.max( 0, $from-$increment ).'">&laquo;</a>';
		}
		else {
			echo '<span>&laquo</span>';
		}
		
		// If there's more than seven pages
		if( $page['of'] > 7 ) {
			// If we're near the end
			if( $page['number'] >= $page['of']-2 ) {
				echo '<a href="search?'.$queryString.'&from=0">1</a>'
					. '| &hellip; |'
					. '<a href="search?'.$queryString.'&from='.(($page['number']-2)*$increment).'">'.($page['number']-1).'</a>'
					. ' | <span>'.$page['number'].'</span>';
				if( $page['number'] != $page['of'] ) {
					foreach( range( $page['number']+1, $page['of'] ) as $n ) {
						echo '|' . '<a href="search?'.$queryString.'&from='.(($n-1)*$increment).'">'.$n.'</a>';
					}
				}
			}
			// If we're near the start
			elseif( $page['number'] <= 3 ) {
				if( $page['number'] != 1 ) {
    			foreach( range( 1, $page['number']-1 ) as $n ) {
						echo '<a href="search?'.$queryString.'&from='.(($n-1)*$increment).'">'.$n.'</a> | ';
					}
				}
				echo '<span>'.$page['number'].'</span> | '
					. '<a href="search?'.$queryString.'&from='.($page['number']*$increment).'">'.($page['number']+1).'</a>'
					. ' | &hellip; | '
					. '<a href="search?'.$queryString.'&from='.(($page['of']-1)*$increment).'">'.$page['of'].'</a>';
			}
			// If we're in the middle somewhere
			else {
				echo '<a href="search?'.$queryString.'&from=0">1</a>'
					. '| &hellip; |'
					. '<a href="search?'.$queryString.'&from='.(($page['number']-2)*$increment).'">'.($page['number']-1).'</a>'
					. ' | <span>'.$page['number'].'</span>'
					. ' | ' . '<a href="search?'.$queryString.'&from='.($page['number']*$increment).'">'.($page['number']+1).'</a>'
					. ' | &hellip; |'
					. '<a href="search?'.$queryString.'&from='.(($page['of']-1)*$increment).'">'.$page['of'].'</a>';
			}
		}
		// If there's fewer than seven pages
		else {
			$links = array();
			foreach( range( 1, $page['of'] ) as $n ) {
				if( $n == $page['number'] ) { $links[] = '<span>'.$n.'</span>'; }
				else { $links[] = '<a href="search?'.$queryString.'&from='.(($n-1)*$increment).'">'.$n.'</a>'; }
			}
			echo implode( '|' , $links );
		}
		
		if( $page['number'] < $page['of'] ) {
			echo '<a href="search?'.$queryString.'&from='.(($page['number'])*$increment).'">&raquo;</a>';
		}
		else {
			echo '<span>&raquo</span>';
		}
?></div>
<?php endif; ?>
</div>
<?php endif; ?>
