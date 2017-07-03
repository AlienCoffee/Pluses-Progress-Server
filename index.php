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
	
	// Loading information about pages
	$_pages_file = __conf__."/pages.json";
	$_pages_data = @file_get_contents ($_pages_file);
	if ($_pages_data === false) {
		$answer = Array (
			'type' => "error",
			'code' => "",
			'message' => "failed to find file with pages configuration"
		); end_loading ($answer);
	}
	
	$_pages_data = utf8_encode ($_pages_data);
	$_pages = @json_decode ($_pages_data, true);
	if ($_pages === null) {
		$answer = Array (
			'type' => "error",
			'code' => "",
			'message' => "failed to parse file with pages configuration"
		); end_loading ($answer);
	}
	
	// Loading information about methods
	$_methods_file = __conf__."/methods.json";
	$_methods_data = @file_get_contents ($_methods_file);
	if ($_methods_data === false) {
		$answer = Array (
			'type' => "error",
			'code' => "",
			'message' => "failed to find file with methods configuration"
		); end_loading ($answer);
	}
	
	$_methods_data = utf8_encode ($_methods_data);
	$_methods = @json_decode ($_methods_data, true);
	if ($_methods === null) {
		$answer = Array (
			'type' => "error",
			'code' => "",
			'message' => "failed to parse file with methods configuration"
		); end_loading ($answer);
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
			
			// Storing arguments from POST
			$_context = array_merge ($_context, $_POST);
		} else {
			$answer = Array (
				'type' => "error",
				'code' => "",
				'message' => "access token was not found in arguments"
			); end_loading ($answer);
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
	
	// Searching for the requested path
	$_requested_path = $_request_parsed ['path'][0] == '/'
						? substr ($_request_parsed ['path'], 1)
						: $_request_parsed ['path'];
						
	$_is_page = array_key_exists ($_requested_path, $_pages);
	$_is_method = array_key_exists ($_requested_path, $_methods);
	if ($_is_page || $_is_method) {
		$_object = null;
		$_src_path = "";
		
		if ($_is_page) {
			$_object = $_pages [$_requested_path];
			$_src_path = __frames__."/".$_object ['src'];
		} else if ($_is_method) {
			$_object = $_methods [$_requested_path];
			$_src_path = __core__."/".$_object ['src'];
		}
		
		if (!UsersManip::has_access ($_user, $_object ['rights'])) {
			$answer = Array (
				'type' => "error",
				'code' => "403", // Not sure
				'message' => "access forbiden"
			); end_loading ($answer);
		}
		
		
		if (!isset ($_object ['src']) || !file_exists ($_src_path)) {
			$answer = Array (
				'type' => "error",
				'code' => "",
				'message' => "requested file not implemented"
			); end_loading ($answer);
		}
		
		if ($_object ['visible'] != "visible") {
			$answer = Array (
				'type' => "error",
				'code' => "",
				'message' => "path can't be reached"
			); end_loading ($answer);
		}
		
		// Including requested file
		require_once $_src_path; // ...
		
		if ($_is_method) {
			$arguments = $_object ['arguments'];
			$args = Array ();
			foreach ($arguments as $key => $value) {
				$is_nec = ($value && $value [0] != "?");
				if (!$is_nec) { $value = substr ($value, 1); }
				
				if (!array_key_exists ($value, $_context) 
						&& $is_nec) {
					$answer = Array (
						'type' => "error",
						'code' => "",
						'message' => "argument `$value` missed"
					); end_loading ($answer);
				}
				
				$args [] = $_context [$value];
			}
			
			$class_name = $_object ['class'];
			$method_name = $_object ['call'];
			$answer = Array (
				'type' => "error",
				'code' => "",
				'message' => "failed to call requested method"
			);
			
			@call_user_func_array ("$class_name::$method_name", $args);
		}
		
		end_loading (null);
	} else {
		$answer = Array (
			'type' => "error",
			'code' => "404",
			'message' => "requested path not found"
		); end_loading ($answer);
	}
	
	function end_loading ($answer) {
		if ($answer != null) {
			echo (json_encode ($answer));
		}
		
		@$db->close ();
		exit (0);
	}
	
?>