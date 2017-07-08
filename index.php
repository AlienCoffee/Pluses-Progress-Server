<?php

	// Index page
    // All requests are redirected here

    //
    // Loading server constants file //
    //
    $_consts_file = $_SERVER ['DOCUMENT_ROOT']."/src/server/conf/consts.php";
    if (!file_exists ($_consts_file)) {
        echo ("[ERROR] Failed to load configs file (consts.php) due to: not found");
        exit (0);
    }
    require_once $_consts_file;

    $_errors_file = __conf__."/errors.php";
    if (!file_exists ($_errors_file)) {
        echo ("[ERROR] Failed to load configs file (errors.php) due to: not found");
        exit (0);
    }
    require_once $_errors_file;

    //
    // Loading necessary files for work //
    //
    $_utils_file = __php__."/utils.php";
    if (!file_exists ($_utils_file)) {
        Error::push ($F_NOT_FOUND_E->cmt ($_utils_file));
    }
    require_once $_utils_file;
    
    $_db_file = __php__."/db.php";
    if (!file_exists ($_db_file)) {
        Error::push ($F_NOT_FOUND_E->cmt ($_db_file));
    }
    require_once $_db_file;
    $_db = DB::connect ();

    //
    // Getting request content //
    //
    $_request_str = $_SERVER ['REQUEST_URI'];
    if ($_request_str [0] == "/") {
        // Clear from front slash
        $_request_str = substr ($_request_str, 1);
    }

    if (strlen ($_request_str) == 0) {
        // Set value of default destination
        $_request_str = __def_dest__;
    }

    $_request_address = $_SERVER ['REQUEST_SCHEME']."://".__domain__."/".$_request_str;
    $_request_parsed = parse_url ($_request_address); // Parsed on components URL address

    $_request_arguments = Array ();
    // Getting all arguments after ? sign in request string
    parse_str ($_request_parsed ['query'], $_request_arguments);

    //
    // Indentifying request method
    //

    // * CALLING CONVENTIONS * //
    /*

        1. Only GET and POST methods supported
        2. In any request must be `token` argument
            2.1 If it's GET method  -> `token` must be in query string
            2.2 If it's GET method  -> `token` can be saved in cookie
            2.3 If it's POST method -> `token` must be in body
        3. (WARNING) 
           If in GET method `token` won't be found -> will be used default
        4. Other arguments can be passed in any order
            4.1 If it's POST method -> arguments can be sent both in query string and body
        5. In case of lack of any argument -> error will be returned

    */

    $_request_method = $_SERVER ['REQUEST_METHOD'];
    $_token = __def_token__; // Default token of guest user

    if ($_request_method == "GET") {
        if (isset ($_GET ['token'])) {
            $_token = strtolower (trim ($_GET ['token']));
        } else if (isset ($_COOKIE ['token'])) {
            $_token = strtolower (trim ($_COOKIE ['token']));
        } // Otherwise default value is already set
    } else if ($_request_method == "POST") {
        if (isset ($_POST ['token'])) {
            $_token = strtolower (trim ($_POST ['token']));

            // Merging body arguments to query arguments
            $_request_arguments = array_merge ($_request_arguments,
                                                $_POST);
        } else { Error::push ($RQ_NO_TOKEN_E); }
    }
    check_input_data ($_token, __regexp_hex__, $DF_NOT_HEX_E);
    // Identifying user by given token
    $_user = identify_user ($_token);

    //
    // Loading methods configuration
    //

    $_sources = load_config_file ("sources.json");
    load_path ($_request_parsed ['path']);

?>