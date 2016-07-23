<?php
require_once './functions.php';
$selected_source_name = isset ( $_POST ["dc_source"] ) ? $_POST ["dc_source"] : "";
$total = 0;
$pageno = 1;
$num_results = isset ( $_POST ['num_results'] ) ? intval ( $_POST ['num_results'] ) : 10;
// print_r ( $num_results );
$facets = get_filter_names ();
$facets_left = array_slice ( $facets, 0, ceil ( count ( $facets ) / 2 ) );
$facets_right = array_slice ( $facets, count ( $facets_left ), count ( $facets ) - count ( $facets_left ), true );

if ($_POST) {
	// $pageno = isset ( $_POST ["pageno"] ) ? intval ( $_POST ["pageno"] ) : 1;
	require_once './classes.php';
	$query = new query ( $selected_source_name );
	$filter_fields = get_filter_names ();
	foreach ( $_POST as $key => $value ) {
		if ($key == "metadata_dc" || $key == "pageno" || $key == "metadata_lrmi")
			continue;
		if (in_array ( str_replace ( "_", ".", $key ), $filter_fields )) {
			
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
<script
	src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>
<script src="./lib/bootstrap-3.3.6-dist/js/bootstrap.min.js"></script>
<script type="text/javascript"
	src="./lib/bootstrap-select-1.9.3/js/bootstrap-select.js"></script>
<script type="text/javascript" src="./lib/pagination/Pagination.js"></script>
<link rel="stylesheet"
	href="./lib/bootstrap-3.3.6-dist/css/bootstrap.min.css">
<link rel="stylesheet"
	href="./lib/bootstrap-3.3.6-dist/css/bootstrap-theme.min.css">
<link rel="stylesheet" type="text/css"
	href="./lib/bootstrap-select-1.9.3/css/bootstrap-select.css">
<link rel="stylesheet" href="./lib/pagination/simplePagination.css">
<link rel="stylesheet"
	href="./lib/font-awesome-4.6.1/css/font-awesome.min.css">
<style type="text/css">
.facet-list {
	max-height: 200px;
	overflow-y: scroll;
	width: 100%;
}

.facet-heading {
	cursor: pointer;
	padding: 3px;
	font-size: 110%;
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
}

.title {
	
}

.overflow-off {
	white-space: nowrap;
	width: 100%;
	overflow: hidden;
	text-overflow: ellipsis;
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
</style>
</head>
<body>
	<div class="container-fluid">
		<!--  <div class="well well-sm">
			<h3 style="margin-top: 10px;">NDL's Annotation Interface</h3>
		</div> -->
		<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" role="form"
			id="main" method="post">
		<?php
		
		include_header ();
		
		if ($selected_source_name != "") {
			?>
		<nav class="navbar navbar-default top-fields">
				<div class="row">
					<?php include_top_fields (); ?>
	<!-- Navbar content -->
				</div>
			</nav><?php }?>
			<div class="message"><?php if($_POST) echo $total." results found";?></div>
			<div class="row">
				<div class="col-lg-2">
				<?php filter_panel($facets_left,"accordian_left");?>
			</div>
				<div class="col-lg-8" >
					<div class="list-group" style="padding: 0px">
				<?php
				if (isset ( $documents )) {
					foreach ( $documents as $doc ) {
						$content_uri = get_dspace_endpoint () . "/xmlui/handle/" . $doc ['handle'] . "?show=full";
						echo '<div class = "list-group-item row" ><div class="col-lg-11">';
						echo '<h4 class="list-group-item-heading overflow-off"><a title = "'. $doc ['dc.title'] .'" href="' . $content_uri . '" target="_blank">' . $doc ['dc.title'] . "</a></h4>";
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
								<div class="col-lg-1"><a class="btn btn-info btn-sm" href="./content.php?handle=' . $doc ['handle'] . '" target="_blank" title="View actual content">View</a></div>
							</div>';
					}
				}
				?>
				</div>
					<div id="paging" style="text-align: center">
				<?php
				
				if ($total > $GLOBALS ['num_results'])
					include_pagination ();
				?>
				</div>
					<input type="hidden" name="pageno" id="pageno">
				</div>
				<div class="col-lg-2">
				<?php filter_panel($facets_right,"accordian_right");?>
			</div>
			</div>
		</form>
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
	<div class="col-lg-2" style="text-align: right">
		<h4>Select fields to display</h4>
	</div>
	<div class="col-lg-3">
		<select id="dc-list" class="selectpicker show-tick boot-select"
			multiple="multiple" data-width="100%" data-live-search="true"
			title="Select the dc fields from this list" data-size="15"
			onchange="" name="metadata_dc[]">
	<?php
	$fields = get_field_names ();
	foreach ( $fields as $field => $class ) {
		// print_r(strpos($field, "dc."));
		// print_r(preg_match("/^dc.*/", $field));
		
		// continue;
		// $field = trim($field);
		if (preg_match ( "/^dc.*/", $field )) {
			
			$selected = isset ( $_POST ["metadata_dc"] ) && in_array ( $field, $_POST ["metadata_dc"] ) ? "selected" : "";
			// echo $selected; continue;
			echo '<option ' . $selected . ' value="' . $field . '">' . $field . '</option>';
		}
	}
	?>
	</select>
	</div>
	<div class="col-lg-3">

		<select id="lrmi-list" class="selectpicker show-tick boot-select"
			multiple="multiple" data-width="100%" data-live-search="true"
			title="Select the lrmi fields from this list" data-size="15"
			onchange="" name="metadata_lrmi[]">
	<?php
	// print_r($fields);// exit(0);
	foreach ( $fields as $field => $class ) {
		// $field = trim ( $field );
		// print_r(preg_match("/^lrmi.*/", $field));
		// continue;
		if (preg_match ( "/^lrmi.*/", $field )) {
			$selected = isset ( $_POST ["metadata_lrmi"] ) && in_array ( $field, $_POST ["metadata_lrmi"] ) ? "selected" : "";
			echo '<option ' . $selected . ' value="' . $field . '">' . $field . '</option>';
		}
	}
	?>
	</select>
	</div>
	<div class="col-lg-1">
		<button type="button" class="btn btn-info "
			onclick="$('#main').submit();">Submit</button>
	</div>

	<div class="col-lg-3">

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
			class="selectpicker show-tick boot-select" data-width="40%"
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
function include_header() {
	?>
<nav class="well well-sm top-panel">
		<div class="row">
			<div class="col-md-3">
				<h2 style="margin: 5px;">
					<img class="img-responsive" src="./img/logo.png" width="60"
						style="display: inline;" />Data Test Portal
				</h2>
			</div>
			<div class="col-md-6">
			<?php include_source_list();?>
		</div>
			<div class="col-md-3">
				<div class="btn-group" role="group" aria-label="...">
					<button type="button" class="btn btn-default">
						<span class="glyphicon glyphicon-download-alt"></span> CSV
					</button>

				</div>
			</div>
		</div>
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
function filter_panel($facets, $parent) {
	echo '<div id="' . $parent . '">';
	
	foreach ( $facets as $facet ) {
		
		if (strlen ( $facet ) > 20) {
			$temp = explode ( ".", $facet );
			$temp_display = end ( $temp );
		} else
			$temp_display = $facet;
		?>
			<div class="panel panel-info facet">

		<div title="<?php echo $facet; ?>" class="panel-heading" style="padding: 2px;">
			<div class="row">
				<div class="col-lg-10 col-sm-10" data-parent="<?php echo "#".$parent?>" data-toggle="collapse"
					href="<?php echo '#'.str_replace(".","_",$facet);?>"
					aria-expanded="true">
					<h5 class="panel-title facet-heading ">
							<?php echo $temp_display;?>
										</h5>

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
							$displayable = get_readable_value ( $facet, $str );
							$error_class = $displayable === null ? ' class="text-danger"' : "";
							echo '<div class="checkbox" title="' . $str . '">
										<label' . $error_class . '><input ' . $checked . ' value = "' . $str . '" onchange="$(\'#main\').submit();" type="checkbox" value="" name = "' . $each_facet . '[]">' . $str . '</label>
										<div style = "float : right"><span class = "badge" >' . $count . '</span></div>';
							echo '</div>';
						}
					}
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
//$("#paging ul").addClass("paging");
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