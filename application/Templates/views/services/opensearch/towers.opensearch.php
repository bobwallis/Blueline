<?php
namespace Blueline;
use Pan\View;

View::cache( true );
$baseURL = $this->get( 'site[baseURL]' );

View::element( 'header' ); ?>
	<ShortName>Towers | Blueline</ShortName>
	<Description>Search the towers database</Description>
	<Attribution>Central Council of Church Bellringers</Attribution>
	<Url rel="results" type="text/html" indexOffset="0" template="<?=$baseURL?>/towers/search?q={searchTerms}&amp;count={count}&amp;from={startIndex}" />
	<Url rel="suggestions" type="application/x-suggestions+json" indexOffset="0" template="<?=$baseURL?>/services/suggest/towers?q={searchTerms}&amp;count={count}&amp;from={startIndex}" />
	<Url rel="self" type="application/opensearchdescription+xml" template="<?=$baseURL?>/services/opensearch/towers.xml" />
	<moz:SearchForm><?=$baseURL?>/towers/search</moz:SearchForm>
<?php View::element( 'footer' );