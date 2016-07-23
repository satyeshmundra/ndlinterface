<?php
require_once './functions.php';
$selected_source_name = isset ( $_POST ["dc_source"] ) ? $_POST ["dc_source"] : "";
$total = 0;
$pageno = 1;
$num_results = isset ( $_POST ['num_results'] ) ? intval ( $_POST ['num_results'] ) : 10;
$facets = get_filter_names ();
$facets_left = array_slice ( $facets, 0, ceil ( count ( $facets ) / 2 ), true );
$facets_right = array_slice ( $facets, count ( $facets_left ), count ( $facets ) - count ( $facets_left ), true );
if ($_POST) {
	// $pageno = isset ( $_POST ["pageno"] ) ? intval ( $_POST ["pageno"] ) : 1;
	require_once './classes.php';
	$query = new query ( $selected_source_name );
	$filter_fields = get_filter_names ();
	foreach ( $_POST as $key => $value ) {
		if ($key == "metadata_dc" || $key == "pageno" || $key == "metadata_lrmi")
			continue;
		if (in_array ( str_replace ( "_", ".", $key ), array_keys ( $filter_fields ) )) {
			$query->add_filter_values ( str_replace ( "_", ".", $key ), $value );
		}
	}
	if (isset ( $_POST ["metadata_dc"] )) {
		foreach ( $_POST ["metadata_dc"] as $field ) {
			$query->add_field ( $field );
		}
	}
	if (isset ( $_POST ["metadata_lrmi"] )) {
		foreach ( $_POST ["metadata_lrmi"] as $field ) {
			$query->add_field ( $field );
		}
	}
	if (($_POST ["pageno"])) {
		$pageno = ( int ) ($_POST ["pageno"]);
		$query->set_result_start ( ($pageno - 1) * $num_results );
	}
	if (isset ( $_POST ["sort_by"] ) && $_POST ["sort_by"]) {
		$sort = explode ( " ", $_POST ["sort_by"] );
		$query->set_result_order ( $sort [0], $sort [1] );
	}
	$query->set_result_rows ( $num_results );
	$query->execute_query ();
	$documents = $query->get_documents ();
	$total = $query->get_count ();
	
	$facet_values = $query->get_facets ();
	// print_r($documents); exit(0);
	$vocabulary = load_vocabulary_map ();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>NDL's Data Testing Portal</title>
<link rel="stylesheet"
	href="./lib/bootstrap-3.3.6-dist/css/bootstrap.min.css">
<link rel="stylesheet"
	href="./lib/bootstrap-3.3.6-dist/css/bootstrap-theme.min.css">
<link rel="stylesheet" type="text/css"
	href="./lib/bootstrap-select-1.9.3/css/bootstrap-select.css">
<link rel="stylesheet" href="./lib/pagination/simplePagination.css">
<link rel="stylesheet"
	href="./lib/font-awesome-4.6.1/css/font-awesome.min.css">


<script src="./lib/jquery/jquery-1.11.3.min.js"></script>
<script src="./lib/bootstrap-3.3.6-dist/js/bootstrap.min.js"></script>
<script type="text/javascript"
	src="./lib/bootstrap-select-1.9.3/js/bootstrap-select.js"></script>
<script type="text/javascript" src="./lib/pagination/Pagination.js"></script>

<style type="text/css">
.facet-list {
	max-height: 200px;
	overflow-y: scroll;
	width: 100%;
}

.facet-heading {
	cursor: pointer;
	padding: 1px;
}

.facet {
	margin-bottom: 5px;
}

.top-panel {
	margin-bottom: 3px;
	padding: 0px;
}

.top-fields {
	margin-bottom: 5px;
	min-height: 100%;
}

.overflow-off {
	white-space: nowrap;
	width: 100%;
	overflow: hidden;
	text-overflow: ellipsis;
	overflow: hidden;
}

.overflow-facet-off {
	max-width: 220px;
	text-overflow: ellipsis;
	white-space: nowrap;
	overflow: hidden;
}

.metadata {
	cursor: default
}

.message {
	text-align: center;
	margin-bottom: 5px;
}

.counter {
	margin-top: 2px;
	padding: 0px;
	visibility: visible;
	cursor: pointer;
}

.filter-label {
	text-overflow: ellipsis;
	overflow: hidden;
}

.badge {
	font-size: small;
	padding: 3px 5px;
}

.checkbox {
	border-bottom: thin;
	border-bottom-color: #000000;
	border-bottom-style: dotted;
}

.back-cover {
	display: block;
	margin: 0 auto;
	opacity: 0.6;
	filter: alpha(opacity = 60);
}
</style>
</head>
<body>
	<div class="container-fluid">
		<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" role="form"
			id="main" method="post">
			<?php include_header ( $selected_source_name ); ?>
			<div class="message"><?php if($_POST) echo $total." results found";?></div>
			<div class="row">
				<div class="col-md-3" style="padding-right: 2px;">
				<?php if($_POST) filter_panel($facets_left,"accordian_left");?>
				</div>
				<div class="col-md-6">
						<?php
						if (isset ( $documents )) {
							echo '<div class="list-group" style="padding: 0px">';
							foreach ( $documents as $doc ) {
								$content_uri = get_dspace_endpoint () . "/xmlui/handle/" . $doc ['handle'] . "?show=full";
								echo '<div class = "list-group-item row" ><div class="col-md-11">';
								echo '<h4 class="list-group-item-heading overflow-off"><a title = "' . $doc ['dc.title'] . '" href="' . $content_uri . '" target="_blank">' . $doc ['dc.title'] . "</a></h4>";
								echo '<span class="text-muted">Handle:</span><code>' . $doc ['handle'] . "</code><br/>";
								$meta_values = array ();
								foreach ( $doc as $metadata => $value ) {
									if ($metadata == "dc.title" || $metadata == "handle")
										continue;
									
									$class = get_css_classname ( $metadata );
									$string = gettype ( $value ) == "array" ? implode ( " | ", $value ) : $value;
									array_push ( $meta_values, '<span class="' . $class . '" title="' . $metadata . '">' . $string . '</span>' );
								}
								echo implode ( " ", $meta_values );
								echo '</div>
										<div class="col-md-1"><a class="btn btn-info btn-sm" href="./content.php?handle=' . $doc ['handle'] . '" target="_blank" title="View actual content">View</a></div>
									</div>';
							}
							echo '</div>';
						} else {
							?>
					<blockquote>
						<code>Step 1:</code>
						Select source from the above dropdown<br />
						<code>Step 2:</code>
						Choose the filters from left and right facets<br />
						<code>Step 3:</code>
						Select the fields you want to see from the top two dropdowns
					</blockquote>
					<div align="center" class="text-info">
						<img class="back-cover" src="./img/logo.png" width="500" />
					</div>
					<br />
							<?php
						}
						?>
					<div id="paging" style="text-align: center">
				<?php
				
				if ($total > $GLOBALS ['num_results'])
					include_pagination ();
				?>
				</div>
					<input type="hidden" name="pageno" id="pageno">
				</div>
				<div class="col-md-3" style="padding-left: 2px;">
				<?php if($_POST) filter_panel($facets_right,"accordian_right");?>
			</div>
			</div>
		</form>
		<div class="panel panel-primary">
			<div class="panel-heading">
				<i class="fa fa-external-link-square"></i> Important links (will be
				opened in new tab)
			</div>
			<table class="table table-responsive">
				<tr>
					<td><i class="fa fa-code"></i> <a target="_blank"
						href="http://www.jsoneditoronline.org/">Online JSON editor</a></td>
					<td><i class="fa fa-list-ol"></i> <a target="_blank"
						href="http://10.3.100.22:8080/toc">NDL's Table of Contents (TOC)
							generator</a></td>
					<td><i class="fa fa-file-excel-o"></i> <a target="_blank"
						href="https://drive.google.com/open?id=1BjXleBDV2QhChiv3TSoi4n5jLwVFy06dV-HLMfyu21w">NDL's
							Online Metadata specs</a></td>
				</tr>
				<tr>
					<td><i class="fa fa-users"></i> <a target="_blank"
						href="http://10.3.100.22:8080/vocab">NDL's Controlled vocabulary
							portal</a></td>
					<td><i class="fa fa-file-word-o"></i> <a target="_blank"
						href="https://drive.google.com/open?id=1zHPpereTl4OMwsRDyN7fnj87QbSRAolIY-pA6r-1cZg">NDL's
							Online Annotation scheme</a></td>
					<td><i class="fa fa-chain-broken"></i> <a target="_blank" href="/"
						onclick="return false;">Some link #2</a></td>
				</tr>
			</table>
		</div>
	</div>
<?php
function get_css_classname($metadata_field) {
	$fields = get_field_names ();
	foreach ( $fields as $field => $class ) {
		if ($metadata_field == $field) {
			return $class;
		}
	}
}
function include_top_fields() {
	?>
	<div class="col-md-2" style="text-align: right">
		<h5>Select fields to display</h5>
	</div>
	<div class="col-md-3">
		<select id="dc-list" class="selectpicker show-tick boot-select"
			multiple="multiple" data-width="100%" data-live-search="true"
			title="Select the dc fields from this list" data-size="15"
			onchange="" name="metadata_dc[]">
	<?php
	$fields = get_field_names ();
	foreach ( $fields as $field => $class ) {
		if (preg_match ( "/^dc.*/", $field )) {
			$selected = isset ( $_POST ["metadata_dc"] ) && in_array ( $field, $_POST ["metadata_dc"] ) ? "selected" : "";
			echo '<option ' . $selected . ' value="' . $field . '">' . $field . '</option>';
		}
	}
	?>
	</select>
	</div>
	<div class="col-md-3">
		<select id="lrmi-list" class="selectpicker show-tick boot-select"
			multiple="multiple" data-width="100%" data-live-search="true"
			title="Select the lrmi fields from this list" data-size="15"
			onchange="" name="metadata_lrmi[]">
	<?php
	foreach ( $fields as $field => $class ) {
		if (preg_match ( "/^lrmi.*/", $field )) {
			$selected = isset ( $_POST ["metadata_lrmi"] ) && in_array ( $field, $_POST ["metadata_lrmi"] ) ? "selected" : "";
			echo '<option ' . $selected . ' value="' . $field . '">' . $field . '</option>';
		}
	}
	?>
	</select>
	</div>
	<div class="col-md-2">
		<div class="btn-group">
			<button type="button" class="btn btn-info"
				onclick="$('#main').submit();">Submit</button>
			<button type="button" class="btn btn-warning"
				onclick="$('#lrmi-list option:selected').removeAttr('selected');$('#dc-list option:selected').removeAttr('selected');$('#main').submit();">Reset</button>
		</div>
	</div>

	<div class="col-md-2">
		<select id="sort_by" class="selectpicker show-tick boot-select"
			data-width="40%" title="Sort by" data-size="15"
			onchange="$('#main').submit();" name="sort_by">
		<?php
	$options = get_sorting ();
	foreach ( $options as $val ) {
		$selected = isset ( $_POST ['sort_by'] ) && ($val . ' ASC' == $_POST ['sort_by']) ? "selected" : "";
		echo '<option ' . $selected . ' value = "' . $val . ' ASC" >' . $val . ' ascending </option>';
		$selected = isset ( $_POST ['sort_by'] ) && ($val . ' DESC' == $_POST ['sort_by']) ? "selected" : "";
		echo '<option ' . $selected . ' value = "' . $val . ' DESC" >' . $val . ' descending </option>';
	}
	?>
		
		
		</select> <select id="num_results"
			class="selectpicker show-tick boot-select" data-width="50%"
			title="Results per page" data-size="15"
			onchange="$('#main').submit();" name="num_results">
		<?php
	$num = get_results_per_page ();
	foreach ( $num as $val ) {
		if (isset ( $_POST ['num_results'] ))
			$selected = ($val == $_POST ['num_results']) ? "selected" : "";
		else
			$selected = ($val == "10") ? "selected" : "";
		echo '<option ' . $selected . ' value = "' . $val . '" >' . $val . ' results </option>';
	}
	?>
		</select>
	</div>
	<?php
}
function include_header($selected_source_name) {
	?>
	<nav class="well well-sm top-panel" style="margin-bottom: 0px;">
		<div class="row">
			<div class="col-md-3">
				<h3 style="margin: 5px;">
					<a href="<?php echo $_SERVER["PHP_SELF"]; ?>"
						style="text-decoration: none;"> <img class="img-responsive"
						src="./img/logo.png" width="40" style="display: inline;" />Data
						Testing Portal
					</a>
				</h3>
			</div>
			<div class="col-md-6">
		<?php include_source_list();?>
			</div>
			<div class="col-md-3">
				<!-- 
			<div class="btn-group" role="group" aria-label="...">
				<button type="button" class="btn btn-default">
					<span class="glyphicon glyphicon-download-alt"></span> CSV
				</button>

			</div>
			 -->
			</div>
		</div>
		<?php
	if ($selected_source_name != "") {
		?>
		<div class="row">
			<?php include_top_fields (); ?>
		</div>
		<?php }?>
	</nav>	
<?php
}
function include_source_list() {
	$sources = get_list_of_sources ();
	?>
<select id="source-list" class="selectpicker show-tick boot-select"
		data-width="100%" data-live-search="true"
		title="Select the dc.source from this list" data-size="15"
		onchange="$( 'input:checked' ).removeAttr('checked'); $('#main').submit();"
		name="dc.source">
	<?php
	foreach ( $sources as $source => $count ) {
		$selected = $GLOBALS ["selected_source_name"] == $source ? "selected" : "";
		echo '<option ' . $selected . ' value="' . $source . '" data-subtext=" ' . $count . ' items">' . $source . '</option>';
	}
	?>
</select>
<?php
}
?>

<?php
function is_valid_hirarchy($name, $value) {
	switch ($name) {
		case "ddc" :
			$doc = new DOMDocument ();
			$doc->loadXML ( load_ddc_file () );
			$xpath = new DOMXPath ( $doc );
			$result = $xpath->query ( '//stored[contains(.,"' . $value . '")]' );
			if ($result)
				return true;
			break;
		case "mesh" :
			$doc = new DOMDocument ();
			$doc->loadXML ( load_mesh_file () );
			$xpath = new DOMXPath ( $doc );
			$result = $xpath->query ( '//stored[contains(.,"' . $value . '")]' );
			if ($result)
				return true;
			break;
	}
	return false;
}
function is_valid_json($metaValue) {
	json_decode ( $metaValue );
	return (json_last_error () == JSON_ERROR_NONE);
}
function is_datatype_matched($value, $defined_type) {
	$stored_type = gettype ( $value );
	switch ($defined_type) {
		case "str" :
			if ($stored_type === "string")
				return true;
			break;
		case "int" :
			if ($stored_type === "integer" || $stored_type === "double")
				return true;
			break;
	}
	return false;
}
function check_validity($meta_name, $value, $type) {
	switch ($type) {
		case "ctrl" :
			return get_readable_value ( $meta_name, $value );
			break;
		case "ddc" :
			if (is_valid_hirarchy ( "ddc", $value ))
				return true;
			break;
		case "mesh" :
			if (is_valid_hirarchy ( "mesh", $value ))
				return true;
			break;
		case "extent" :
			if (! is_valid_json ( $value ))
				return false;
			$json_obj = json_decode ( $value, true );
			if (count ( $json_obj ) !== 1)
				return false;
			$json_key = key ( $json_obj );
			$json_val = current ( $json_obj );
			$json_key_list = array (
					"startingPage" => "int",
					"endingPage" => "int",
					"pageCount" => "int",
					"size_in_Bytes" => "int" 
			);
			if (array_key_exists ( $json_key, $json_key_list )) {
				if (is_datatype_matched ( $json_val, $json_key_list [$json_key] ))
					return true;
			}
			break;
		case "contrib_other" :
			if (! is_valid_json ( $value ))
				return false;
			$json_obj = json_decode ( $value, true );
			if (count ( $json_obj ) !== 1)
				return false;
			$json_key = key ( $json_obj );
			$json_val = current ( $json_obj );
			$json_key_list = array (
					"proofListener" => "str",
					"metaCoordinator" => "str",
					"bookCoordinator" => "str",
					"reader" => "str",
					"director" => "str",
					"screenplay" => "str",
					"cinematographer" => "str",
					"producer" => "str",
					"productionHouse" => "str",
					"owner" => "str" 
			);
			if (array_key_exists ( $json_key, $json_key_list )) {
				if (is_datatype_matched ( $json_val, $json_key_list [$json_key] ))
					return true;
			}
			break;
		case "free" :
			return true;
		case "name" :
			if (preg_match ( "/^[A-Z]([a-z]+)(\,)([ ][A-Z](\.|[a-z]+))+$/", $value )) {
				$config = json_decode ( load_configuration () );
				foreach ( $config->name_exception_words as $word ) {
					if (stripos ( " $word ", $value ) !== false)
						return false;
				}
				return true;
			}
			break;
		case "date" :
			if (preg_match ( "/^(\d{4})-(\d{2})-(\d{2})$/", $value, $matches )) {
				if (checkdate ( $matches [2], $matches [3], $matches [1] ) && $matches [1] <= date ( "Y" ))
					return true;
			}
			break;
		case "year" :
			if (preg_match ( "/^(\d{4})$/", $value, $matches )) {
				if ($matches [1] <= date ( "Y" ))
					return true;
			}
			break;
	}
	return false;
}
function filter_panel($facets, $parent) {
	echo '<div id="' . $parent . '">';
	foreach ( $facets as $facet => $desc ) {
		if (strlen ( $facet ) > 40) {
			$temp = explode ( ".", $facet );
			$temp_display = end ( $temp );
		} else
			$temp_display = $facet;
		?>
			<div class="panel panel-<?php echo $desc["class"]; ?> facet">

		<div title="<?php echo $facet; ?>" class="panel-heading"
			style="padding: 2px;">
			<div class="row">
				<div class="col-lg-10 col-sm-10"
					data-parent="<?php echo "#".$parent?>" data-toggle="collapse"
					href="<?php echo '#'.str_replace(".","_",$facet);?>"
					aria-expanded="true">
					<h4 class="panel-title facet-heading">
							<?php echo $temp_display;?>
										</h4>

				</div>
				<div class="col-lg-2 col-sm-2 counter"
					onclick="remove_filter($(this));">
		<?php
		if (isset ( $_POST [str_replace ( ".", "_", $facet )] )) {
			echo count ( $_POST [str_replace ( ".", "_", $facet )] ) . '&nbsp;<i class="fa fa-trash"></i>';
		}
		
		?>
		
		</div>
			</div>
		</div>
		<div class="panel-collapse collapse facet-list"
			id="<?php echo str_replace(".","_",$facet);?>">
						<?php
		if (isset ( $GLOBALS ["facet_values"] )) {
			foreach ( $GLOBALS ["facet_values"] as $each_facet => $values ) {
				if ($each_facet == $facet) {
					foreach ( $values as $str => $count ) {
						if (! $count)
							continue;
						$checked = "";
						if (isset ( $_POST [str_replace ( ".", "_", $facet )] ) && $_POST [str_replace ( ".", "_", $facet )]) {
							if (in_array ( $str, $_POST [str_replace ( ".", "_", $facet )] ) || in_array ( "", $_POST [str_replace ( ".", "_", $facet )] ))
								$checked = "checked";
						}
						if ($str === "_undefined_property_name") {
							echo '<div class="checkbox" title="Unassigned">
								<label class="text-danger"><input ' . $checked . ' value = "" onchange="$(\'#main\').submit();" type="checkbox" value="" name = "' . $each_facet . '[]">Unassigned</label>
								<div style = "float : right"><span class = "badge" >' . $count . '</span></div>';
							echo '</div>';
						} else {
							$valid = check_validity ( $facet, $str, $desc ["type"] );
							if ($valid === null) {
								$error_class = "text-danger ";
								$error_class_bg = "bg-danger";
							} else if ($valid === false) {
								$error_class = "text-danger ";
								$error_class_bg = "bg-danger";
							} else {
								$error_class = "";
								$error_class_bg = "";
							}
							echo '<div class="checkbox ' . $error_class_bg . '" title="' . $str . '">
										<label class="' . $error_class . 'overflow-facet-off"><input ' . $checked . ' value = "' . $str . '" onchange="$(\'#main\').submit();" type="checkbox" value="" name = "' . $each_facet . '[]">' . $str . '</label>
										<div style = "float : right"><span class = "badge" >' . $count . '</span></div>';
							echo '</div>';
						}
					}
					if (count ( ( array ) $values ) >= get_facet_limit ())
						echo '<span class="text-danger">More...</span>';
				}
			}
		}
		?>			
		</div>
	</div>
<?php
	}
	echo "</div>";
}

?>
<?php

function include_pagination() {
	?>
	<script type="text/javascript">
$("#paging").pagination({
	items: <?php echo $GLOBALS["total"];?>,
	itemsOnPage: <?php echo $GLOBALS['num_results'];?>,
	cssStyle: "compact-theme",
	onPageClick: function(page, event){
		$('#pageno').val(page);
		//alert($('#pageno').val());
 		$('#main').submit();
	} 
});

$("#paging").pagination('drawPage', <?php echo $GLOBALS["pageno"];?>);
// $("#paging ul").addClass("pagination pagination-sm");
	</script>
<?php
}

?>
<script type="text/javascript">
function remove_filter(facet){
	var id = facet.siblings().attr('href');
	$(id+" input:checked" ).removeAttr('checked'); 
	$('#main').submit();
}

</script>
</body>
</html>