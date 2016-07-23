<?php
/**
 * CONFIGURTION LOADER
 */
function load_configuration() {
	if (apcu_exists ( "config" )) {
		return apcu_fetch ( "config" );
	} else {
		$config = file_get_contents ( "./config.json" );
		apcu_store ( "config", $config );
	}
	return $config;
}
function get_filter_names() {
	$config = json_decode ( load_configuration (), true );
	return $config ["filters"];
}
function get_field_names() {
	$config = json_decode ( load_configuration () );
	return $config->fields;
}
function get_results_per_page() {
	$config = json_decode ( load_configuration () );
	return $config->results_per_page;
}
function get_sorting() {
	$config = json_decode ( load_configuration () );
	return $config->sorting;
}
function get_solr_endpoint() {
	$config = json_decode ( load_configuration () );
	$endpoints = $config->endpoints;
	$selected = explode ( ":", $endpoints [mt_rand ( 0, count ( $endpoints ) - 1 )] );
	$endpoint = new stdClass ();
	$endpoint->host = $selected [0];
	$endpoint->port = $selected [1];
	return $endpoint;
}
function get_dspace_endpoint() {
	$config = json_decode ( load_configuration () );
	return $config->dspace_endpoint;
}
function get_facet_limit() {
	$config = json_decode ( load_configuration () );
	return $config->facet_limit;
}
function get_common_last_names() {
	return array ();
}
/**
 * VOCABULARY MANAGEMENT
 */
function load_remote_file($filename) {
	if (apcu_exists ( $filename )) {
		return apcu_fetch ( $filename );
	} else {
		$url = "http://10.3.100.22:8080/vocab/vocabulary/$filename";
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
		apcu_store ( $filename, $response );
	}
	return $response;
}
function load_vocabulary_map() {
	$response = load_remote_file ( "domain.xml" );
	$object = ( array ) simplexml_load_string ( $response );
	return ($object ["metadata"]);
}
function load_ddc_file() {
	$response = load_remote_file ( "ddcE.xml" );
	return $response;
}
function load_mesh_file() {
	$response = load_remote_file ( "mesh.xml" );
	return $response;
}
/**
 * OTHER UTILITIES
 */
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
	return null;
}
?>