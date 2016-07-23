<?php
$handle = $_GET ["handle"];
// $handle = "12345678_ncert/255786"; // with asset
// $handle = "1234567_ieee/45340"; // without asset
header ( "Location: " . get_identifier_uri ( $handle ) );
?>
<?php
// functions
function get_identifier_uri($handle) {
	$config = json_decode ( file_get_contents ( "./config.json" ) );
	$url = $config->dspace_endpoint . "/rest/handle/$handle?expand=metadata,bitstreams";
	
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
	libxml_use_internal_errors ( true );
	$object = simplexml_load_string ( $response );
	if (libxml_get_errors ()) {
		$url = $config->dspace_endpoint . '/rest/';
		echo '<br/><br/><br/><div align="center"><h1 style="color:#ff0000">DSpace REST gone :(<br/>Inform SysAdmins<br/><br/><a href="' . $url . '">' . $url . '</a></h1></div>';
		exit ( 0 );
	}
	if (count ( $object->bitstreams )) {
		foreach ( $object->bitstreams as $bitstream ) {
			if ($bitstream->bundleName == "ORIGINAL" && $bitstream->name != "thumb.jpg")
				return ($config->dspace_endpoint . "/xmlui/bitstream/handle/" . $handle . "/" . $bitstream->name);
		}
	}
	foreach ( $object->{"metadata"} as $metadata ) {
		if ($metadata->{"key"} == "dc.identifier.uri")
			return (current ( $metadata->{"value"} ));
	}
}
?>