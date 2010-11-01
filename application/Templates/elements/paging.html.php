<?php
namespace Blueline;
if( isset( $limit ) ) :
	$page = array(
		'number' => (ceil(($limit['current']+1)/$limit['increase'])?:'1'),
		'of' => ceil($limit['of']/$limit['increase'])
	);
	$queryString = trim( preg_replace( '/(&|^)from=.*?(&|$)/', '&', Request::queryString() ), '&' );
?>
<div class="paging">
	<p><?php echo 'Page '.$page['number'].' of '.$page['of']; ?></p>
<?php if( $page['of'] > 1 ) : ?>
	<div class="pagingLinks"><?php
		if( $page['number'] > 1 ) {
			echo '<a href="search?'.$queryString.'&from='.max( 0, $limit['current']-$limit['increase'] ).'">&laquo;</a>';
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
					. '<a href="search?'.$queryString.'&from='.(($page['number']-2)*$limit['increase']).'">'.($page['number']-1).'</a>'
					. ' | <span>'.$page['number'].'</span>';
				if( $page['number'] != $page['of'] ) {
					foreach( range( $page['number']+1, $page['of'] ) as $n ) {
						echo '|' . '<a href="search?'.$queryString.'&from='.(($n-1)*$limit['increase']).'">'.$n.'</a>';
					}
				}
			}
			// If we're near the start
			elseif( $page['number'] <= 3 ) {
				if( $page['number'] != 1 ) {
    			foreach( range( 1, $page['number']-1 ) as $n ) {
						echo '<a href="search?'.$queryString.'&from='.(($n-1)*$limit['increase']).'">'.$n.'</a> | ';
					}
				}
				echo '<span>'.$page['number'].'</span> | '
					. '<a href="search?'.$queryString.'&from='.($page['number']*$limit['increase']).'">'.($page['number']+1).'</a>'
					. ' | &hellip; | '
					. '<a href="search?'.$queryString.'&from='.(($page['of']-1)*$limit['increase']).'">'.$page['of'].'</a>';
			}
			// If we're in the middle somewhere
			else {
				echo '<a href="search?'.$queryString.'&from=0">1</a>'
					. '| &hellip; |'
					. '<a href="search?'.$queryString.'&from='.(($page['number']-2)*$limit['increase']).'">'.($page['number']-1).'</a>'
					. ' | <span>'.$page['number'].'</span>'
					. ' | ' . '<a href="search?'.$queryString.'&from='.($page['number']*$limit['increase']).'">'.($page['number']+1).'</a>'
					. ' | &hellip; |'
					. '<a href="search?'.$queryString.'&from='.(($page['of']-1)*$limit['increase']).'">'.$page['of'].'</a>';
			}
		}
		// If there's fewer than seven pages
		else {
			$links = array();
			foreach( range( 1, $page['of'] ) as $n ) {
				if( $n == $page['number'] ) { $links[] = '<span>'.$n.'</span>'; }
				else { $links[] = '<a href="search?'.$queryString.'&from='.(($n-1)*$limit['increase']).'">'.$n.'</a>'; }
			}
			echo implode( '|' , $links );
		}
		
		if( $page['number'] < $page['of'] ) {
			echo '<a href="search?'.$queryString.'&from='.(($page['number'])*$limit['increase']).'">&raquo;</a>';
		}
		else {
			echo '<span>&raquo</span>';
		}
?></div>
<?php endif; ?>
</div>
<?php endif; ?>
