<?php
echo json_encode( array(
	$q,
	$opensearch_results['queries'],
	$opensearch_results['readable'],
	$opensearch_results['URLs']
) );
