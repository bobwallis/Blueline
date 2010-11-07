<?php
echo json_encode( array(
	$q,
	$suggestions['queries'],
	array(),
	$suggestions['URLs']
) );
