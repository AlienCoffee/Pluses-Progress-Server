<?php
	
	// Loading configuration (all constants that can be useful)
	require_once $_SERVER ['DOCUMENT_ROOT']."/core/local/conf/consts.php";
	require_once __engine__."/php/users_manip.php";
	require_once __engine__."/php/utils.php";
	require_once __engine__."/php/db.php";
	UsersManip::set_db ($_db);
	
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
		echo ("[DEBUG] Failed to find file with pages configuration".br);
		exit (0);
	}
	
	$_pages_data = utf8_encode ($_pages_data);
	$_pages = @json_decode ($_pages_data, true);
	if ($_pages === null) {
		echo ("[DEBUG] Failed to parse file with pages configuration".br);
		exit (0);
	}
	
	// Detecting what kind of request got
	$_request_method = $_SERVER ['REQUEST_METHOD'];
	$_token = __def_token__; // Init value of token
	
	if ($_request_method == "POST") {
		// API request -> token must be inside
		// Site request can be possible too 
		// but anyway "token must be inside"
		
		if (isset ($_POST ['token'])) {
			$_token = htmlspecialchars ($_POST ['token']);
			$_token = trim ($_token);
		} else {
			echo ("[ERROR] Access token was not found in arguments");
			exit (0);
		}
	} else if ($_request_method == "GET") {
		// Site request -> token must be in cookie
		// API request can be possible too
		// then token must be in arguments
		
		if (isset ($_COOKIE ['token'])) {
			$_token = htmlspecialchars ($_COOKIE ['token']);
			$_token = trim ($_token);
		} else if (isset ($_context ['token'])) {
			$_token = htmlspecialchars ($_context ['token']);
			$_token = trim ($_token);
		} else {
			$_token = __def_token__;
		}
	} else {
		echo ("[WARNING] Unsupported request method (use GET or POST only)".br);
		exit (0);
	}
	
	// Identifying user by given token
	$_user = UsersManip::identify ($_token);
	//UsersManip::register ("+7(123)1231212", md5 ("test"), "");
	//UsersManip::authorize ("+7(123)1231212", md5 ("test"), "device", "123");
	print_r ($_user);
	
?>