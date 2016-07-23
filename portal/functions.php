<?php
function get_list_of_sources() {
	$query = new SolrQuery ();
	$query->setQuery ( "*" );
	$query->addFilterQuery ( "search.resourcetype:2" );
	$query->setRows ( 0 );
	$query->setFacet ( true );
	$query->setFacetMinCount ( 1 );
	$query->setFacetLimit ( 2000 );
	$query->setFacetSort ( SolrQuery::FACET_SORT_INDEX );
	$query->addFacetField ( "unstemmed_dc.source" );
	$endpoint = get_solr_endpoint ();
	$optionsSolr = array (
			"hostname" => $endpoint->host,
			"login" => '',
			"password" => '',
			"port" => $endpoint->port,
			"path" => "solr/search/" 
	);
	$client = new SolrClient ( $optionsSolr );
	$response = $client->query ( $query )->getResponse ();
	return $response->facet_counts->facet_fields->{"unstemmed_dc.source"};
}
function get_filter_names() {
	$config = json_decode ( file_get_contents ( "./config.json" ) );
	return $config->filters;
}
function get_field_names() {
	$config = json_decode ( file_get_contents ( "./config.json" ) );
	return $config->fields;
}
function get_results_per_page() {
	$config = json_decode ( file_get_contents ( "./config.json" ) );
	return $config->results_per_page;
}
function get_sorting() {
	$config = json_decode ( file_get_contents ( "./config.json" ) );
	return $config->sorting;
}
function get_solr_endpoint() {
	$config = json_decode ( file_get_contents ( "./config.json" ) );
	$endpoints = $config->endpoints;
	$selected = explode ( ":", $endpoints [mt_rand ( 0, count ( $endpoints ) - 1 )] );
	$endpoint = new stdClass ();
	$endpoint->host = $selected [0];
	$endpoint->port = $selected [1];
	return $endpoint;
}
function get_dspace_endpoint() {
	$config = json_decode ( file_get_contents ( "./config.json" ) );
	return $config->dspace_endpoint;
}
function load_vocabulary_map() {
	$url = "http://10.3.100.22:8080/vocab/vocabulary/domain.xml";
	$curl_options = array (
			CURLOPT_HTTPHEADER => array (
					"Accept:application/xml" 
			),
			CURLOPT_RETURNTRANSFER => true 
	);
	$curl = curl_init ( $url );
	curl_setopt_array ( $curl, $curl_options );
	$response = curl_exec ( $curl );
	curl_close ( $curl );
	$object = ( array ) simplexml_load_string ( $response );
	return ($object ["metadata"]);
}
/**
 *
 * @param string $metadata
 *        	metadata name
 * @param string $value
 *        	metadatda value
 *        	@
 */
function get_readable_value($metadata, $value) {
	foreach ( $GLOBALS ["vocabulary"] as $vocab ) {
		if (current ( $vocab->attributes () ["field"] ) == $metadata) {
			foreach ( $vocab->value as $node ) {
				if ($node->attributes () ["actual"] == $value)
					return $node->__toString;
			}
			return null;
		}
	}
	return "";
}
?>