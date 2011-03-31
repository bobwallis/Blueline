<?php
namespace Blueline;
use Pan\View;

View::cache( true );
$baseURL = $this->get( 'site[baseURL]' );

View::element( 'header' ); ?>
	<ShortName>Associations | Blueline</ShortName>
	<Description>Search the associations database</Description>
	<Attribution>Central Council of Church Bellringers</Attribution>
	<Url rel="results" type="text/html" indexOffset="0" template="<?=$baseURL?>/associations/search?q={searchTerms}&amp;count={count}&amp;from={startIndex}" />
	<Url rel="suggestions" type="application/x-suggestions+json" indexOffset="0" template="<?=$baseURL?>/services/suggest/associations?q={searchTerms}&amp;count={count}&amp;from={startIndex}" />
	<Url rel="self" type="application/opensearchdescription+xml" template="<?=$baseURL?>/services/opensearch/associations.xml" />
	<moz:SearchForm><?=$baseURL?>/associations/search</moz:SearchForm>
<?php View::element( 'footer' );