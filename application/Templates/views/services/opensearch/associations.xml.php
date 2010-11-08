<?php View::element( 'opensearch.header' ); ?>
	<ShortName>Associations | Blueline</ShortName>
	<Description>Search the associations database</Description>
	<Attribution>Central Council of Church Bellringers</Attribution>
	<Url rel="results" type="text/html" indexOffset="0" template="<?php echo $site['baseURL']; ?>/associations/search?q={searchTerms}&amp;count={count}&amp;from={startIndex}" />
	<Url rel="suggestions" type="application/x-suggestions+json" indexOffset="0" template="<?php echo $site['baseURL']; ?>/services/suggest/associations?q={searchTerms}&amp;count={count}&amp;from={startIndex}" />
	<Url rel="self" type="application/opensearchdescription+xml" template="<?php echo $site['baseURL']; ?>/services/opensearch/associations.xml" />
	<moz:SearchForm><?php echo $site['baseURL']; ?>/associations/search</moz:SearchForm>
<?php View::element( 'opensearch.footer', compact( 'site' ) ); ?>
