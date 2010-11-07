<?php
echo json_encode( array(
	$q,
	$opensearch_suggestions['queries'],
	$opensearch_suggestions['readable'],
	$opensearch_suggestions['URLs']
) );
