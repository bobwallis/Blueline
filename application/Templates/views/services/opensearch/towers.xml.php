<?php
namespace Blueline;

View::element( 'opensearch.header' ); ?>
	<ShortName>towers | Blueline</ShortName>
	<Description>Search the towers database</Description>
	<Attribution>Central Council of Church Bellringers</Attribution>
	<Url rel="results" type="text/html" indexOffset="0" template="<?php echo $site['baseURL']; ?>/towers/search?q={searchTerms}&amp;count={count}&amp;from={startIndex}" />
	<Url rel="suggestions" type="application/x-suggestions+json" indexOffset="0" template="<?php echo $site['baseURL']; ?>/services/suggest/towers?q={searchTerms}&amp;count={count}&amp;from={startIndex}" />
	<Url rel="self" type="application/opensearchdescription+xml" template="<?php echo $site['baseURL']; ?>/services/opensearch/towers.xml" />
	<moz:SearchForm><?php echo $site['baseURL']; ?>/towers/search</moz:SearchForm>
<?php View::element( 'opensearch.footer', compact( 'site' ) ); ?>
