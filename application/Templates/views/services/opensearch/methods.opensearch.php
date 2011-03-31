<?php
namespace Blueline;
use Pan\View;

View::cache( true );
$baseURL = $this->get( 'site[baseURL]' );

View::element( 'header' ); ?>
	<ShortName>Methods | Blueline</ShortName>
	<Description>Search the methods database</Description>
	<Attribution>Central Council of Church Bellringers</Attribution>
	<Url rel="results" type="text/html" indexOffset="0" template="<?=$baseURL?>/methods/search?q={searchTerms}&amp;count={count}&amp;from={startIndex}" />
	<Url rel="results" type="text/xml" indexOffset="0" template="<?=$baseURL?>/methods/search.xml?q={searchTerms}&amp;count={count}&amp;from={startIndex}" />
	<Url rel="suggestions" type="application/x-suggestions+json" indexOffset="0" template="<?=$baseURL?>/services/suggest/methods?q={searchTerms}&amp;count={count}&amp;from={startIndex}" />
	<Url rel="self" type="application/opensearchdescription+xml" template="<?=$baseURL?>/services/opensearch/methods.xml" />
	<moz:SearchForm><?=$baseURL?>/methods/search</moz:SearchForm>
<?php View::element( 'footer' );