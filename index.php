<?php
	
	// Loading configuration (all constants that can be useful)
	require_once $_SERVER ['DOCUMENT_ROOT']."/core/local/conf/consts.php";
	
	$_request = $_SERVER ['REQUEST_URI'];
	
	if ($_request [0] == '/') {
		// Skipping first solidus
		$_request = substr ($_request, 1);
	}
	
	if (strlen ($_request) == 0) {
		// Empty request afrer a domain
		$_request = __def_request__;
		// Redirecting to pre-setted
	}
	
	$_context = Array ();
	$_request_parsed = parse_url ($_SERVER ['REQUEST_SCHEME']
									."://".__domain__."/".$_request);
	parse_str ($_request_parsed ['query'], $_context);
	
	// Loading iformation about pages
	$_pages_file = __conf__."/pages.json";
	$_pages_data = @file_get_contents ($_pages_file);
	if ($_pages_data === false) {
		echo ("[DEBUG] Failed to find file with pages configuration");
		exit (0);
	}
	
	$_pages = @json_decode ($_pages_data, true);
	if ($_pages === null) {
		echo ("[DEBUG] Failed to parse file with pages configuration");
		exit (0);
	}
	
?>