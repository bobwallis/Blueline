<?php
namespace Blueline;

$baseURL = Config::get( 'site.baseURL' );
?>
	<ShortName>Methods | Blueline</ShortName>
	<Description>Search the methods database</Description>
	<Attribution>Central Council of Church Bellringers</Attribution>
	<Url rel="results" type="text/html" indexOffset="0" template="<?php echo $baseURL; ?>/methods/search?q={searchTerms}&amp;count={count}&amp;from={startIndex}" />
	<Url rel="results" type="text/xml" indexOffset="0" template="<?php echo $baseURL; ?>/methods/search.xml?q={searchTerms}&amp;count={count}&amp;from={startIndex}" />
	<Url rel="suggestions" type="application/x-suggestions+json" indexOffset="0" template="<?php echo $baseURL; ?>/services/opensearch/suggestions/methods?q={searchTerms}&amp;count={count}&amp;from={startIndex}" />
	<Url rel="self" type="application/opensearchdescription+xml" template="<?php echo $baseURL; ?>/services/opensearch/methods.xml" />
	<moz:SearchForm><?php echo $baseURL; ?>/methods/search</moz:SearchForm>
