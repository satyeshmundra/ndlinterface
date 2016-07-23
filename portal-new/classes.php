<?php
class query {
	private $config;
	private $map;
	private $solr_filters = array ();
	private $response;
	private $facets;
	private $fields = array (
			"dc.title",
			"handle" 
	);
	private $start = 0;
	private $rows = 10;
	private $result_sort_field = "handle";
	private $result_sort_order = "ASC";
	public function __construct($source_name) {
		$this->config = json_decode ( file_get_contents ( "./config.json" ) );
		$this->map = $this->config->map_dspace_solr;
		$this->solr_filters [$this->get_solr_field_name ( "dc.source" )] = '"' . $source_name . '"';
	}
	public function add_field($name) {
		array_push ( $this->fields, $name );
	}
	/**
	 *
	 * @param string $name        	
	 * @param array $value        	
	 */
	public function add_filter_values($name, $values) {
		if (count ( $values )) {
			$name = $this->get_solr_field_name ( $name );
			$this->solr_filters [$name] = ($values [0]) ? '("' . implode ( '" AND "', $values ) . '")' : "(-*)";
		}
	}
	public function execute_query() {
		$query = new SolrQuery ();
		$query->setQuery ( "*" );
		$query->setStart ( $this->start );
		$query->setRows ( $this->rows );
		$query->addSortField ( $this->result_sort_field, ($this->result_sort_order == "DESC") ? 1 : 0 );
		$query->addFilterQuery ( "search.resourcetype:2" );
		foreach ( $this->solr_filters as $name => $value ) {
			$query->addFilterQuery ( "$name:$value" );
		}
		$query->setFacet ( true );
		$query->setFacetSort ( SolrQuery::FACET_SORT_INDEX );
		$query->setFacetMinCount ( 1 );
		$query->setFacetLimit ( $this->config->facet_limit );
		$query->setFacetMissing ( true );
		foreach ( $this->map as $dspace => $solr ) {
			$query->addFacetField ( $solr );
		}
		foreach ( $this->fields as $field ) {
			$query->addField ( $field );
		}
		$endpoint = get_solr_endpoint ();
		$optionsSolr = array (
				"hostname" => $endpoint->host,
				"login" => '',
				"password" => '',
				"port" => $endpoint->port,
				"path" => "solr/search/" 
		);
		// echo $query->__toString ();
		// exit ( 0 );
		$client = new SolrClient ( $optionsSolr );
		$response = $client->query ( $query )->getResponse ();
		$this->facets = new stdClass ();
		foreach ( $response->facet_counts->facet_fields as $facet_name => $facet_values ) {
			$name = $this->get_dspace_field_name ( $facet_name );
			$this->facets->$name = $facet_values;
		}
		$this->response = $response->response;
	}
	public function get_documents() {
		return $this->response->docs;
	}
	public function get_count() {
		return $this->response->numFound;
	}
	public function get_facets() {
		return $this->facets;
	}
	public function set_result_rows($count) {
		$this->rows = $count;
	}
	public function set_result_start($start) {
		$this->start = $start;
	}
	private function get_solr_field_name($dspace_fieldname) {
		foreach ( $this->map as $dspace => $solr ) {
			if ($dspace == $dspace_fieldname)
				return $solr;
		}
	}
	private function get_dspace_field_name($solr_fieldname) {
		foreach ( $this->map as $dspace => $solr ) {
			if ($solr == $solr_fieldname)
				return $dspace;
		}
	}
	/**
	 *
	 * @param string $fieldname
	 *        	<p> possible values: all possible 'fields' values, default 'handle'</p>
	 * @param string $order
	 *        	<p> possible values: ASC or DESC, default "ASC"</p>
	 *        	
	 */
	public function set_result_order($fieldname, $order = "ASC") {
		$this->result_sort_field = $this->get_solr_field_name ( $fieldname );
		$this->result_sort_order = $order;
	}
}
?>