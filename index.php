<?php

    // Index page
    // All requests are redirected here

    //
    // Loading server constants file //
    //
    $_consts_file = $_SERVER ['DOCUMENT_ROOT']."/src/server/conf/consts.php";
    if (!file_exists ($_consts_file)) {
        $error = Array (
            'code'    => 2000,
            'type'    => "Error",
            'name'    => "File not found",
            'message' => $_consts_file,
            'trace'   => Array ()
        );
        echo (json_encode ($error));
        exit (0);
    }
    require_once $_consts_file;

    $_answer_file = __conf__."/answer.php";
    if (!file_exists ($_answer_file)) {
        $error = Array (
            'code'    => 2000,
            'type'    => "Error",
            'name'    => "File not found",
            'message' => $_answer_file,
            'trace'   => Array ()
        );
        echo (json_encode ($error));
        exit (0);
    }
    require_once $_answer_file;

    //
    // Loading necessary files for work //
    //

    $_utils_file = __php__."/utils.php";
    if (!file_exists ($_utils_file)) {
        Answer::push ($E_FILE_NOT_FOUND->cmt ($_utils_file, __FILE__, __LINE__));
    }
    require_once $_utils_file;
    
    $_db_file = __php__."/db.php";
    if (!file_exists ($_db_file)) {
        Answer::push ($E_FILE_NOT_FOUND->cmt ($_db_file, __FILE__, __LINE__));
    }
    require_once $_db_file;
    DB::connect ();

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
        } else {
            Answer::push ($E_REQ_HAS_NO_TOKEN->cmt ("", __FILE__, __LINE__));
        }
    }
    check_input_data ($_token, __regexp_hex__, $E_EXP_NOT_IN_HEX_FORMAT);
    // Identifying user by given token
    $_user = identify_user ($_token);
    if ($_user instanceof Answer && $user ['type'] == "Error") {
        Answer::push ($_user->addTrace (__FILE__, __LINE__));
    }

    $_sources = load_config_file ("sources.json");
    if ($_sources instanceof Answer) {
        Answer::push ($_sources->addTrace (__FILE__, __LINE__));
    }

    //
    // Loading files and calling for methods
    //

    $path = $_request_parsed ['path'];
    $path = trim ($path);
    if ($path [0] == "/") {
        // Deleting the first slash symbol
        // to unify all requests' queries 
        $path = substr ($path, 1);
    }

    check_input_data ($path, __regexp_path__, $E_EXP_NOT_IN_PATH_FORMAT);
    $split_path = split ("\.", $path);

    $index = 0;
    $_object = $_sources;
    $_is_page = false;
    $_tmp_class = "";

    while ($index < count ($split_path)) {
        $current = $split_path [$index ++];
        if (!array_key_exists ($current, $_object)) {
            Answer::push ($E_DEST_NOT_FOUND->cmt ($current, 
                                                    __FILE__, 
                                                    __LINE__));
        }

        $_object = $_object [$current]; // One step down
        if (array_key_exists ("#file", $_object)) {
            // This means that FILE should be included
            $_context_file = $_object ['#file'];

            if (!check_for_rights ($_user, $_context_file ['rights'])) {
                Answer::push ($E_PERM_DENIED->cmt ("", __FILE__, 
                                                    __LINE__));
            }

            switch ($_context_file ['type']) {
                case ("page"):
                    if ($index < count ($split_path)) {
                        // If there is something in path
                        // then skip loading of page
                        continue;
                    }
                    
                    if (!file_exists (__frames__."/".$_context_file ['src'])) {
                        Answer::push ($E_FILE_NOT_FOUND->cmt ($_context_file ['src'],
                                                                __FILE__,
                                                                __LINE__));
                    }

                    require_once __frames__."/".$_context_file ['src'];
                    // Mark to exit after a loop immediately
                    $_is_page = true;
                    break 2;
                case ("lib"):
                    if (!file_exists (__php__."/".$_context_file ['src'])) {
                        Answer::push ($E_FILE_NOT_FOUND->cmt ($_context_file ['src'],
                                                                __FILE__,
                                                                __LINE__));
                    }

                    require_once __php__."/".$_context_file ['src'];
                    break;
                case ("class"):
                    if (!file_exists (__php__."/".$_context_file ['src'])) {
                        Answer::push ($E_FILE_NOT_FOUND->cmt ($_context_file ['src'],
                                                                __FILE__,
                                                                __LINE__));
                    }
                    require_once __php__."/".$_context_file ['src'];
                    $_tmp_class = $_context_file ['class'];
                    break;
            }
        }
    }

    // Now $_object - final stage (method or nothing)
    if ($_is_page) {
        @DB::close ();
        exit (0);
    }

    if (!array_key_exists ("function", $_object) 
            || !array_key_exists ("arguments", $_object)
            || !array_key_exists ("rights", $_object)) {
        Answer::push ($E_REQ_NOT_TERMINAL->cmt ($path, 
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__));
    }

    $_funtion_name = $_object ['function'];
    if ($_tmp_class != null) {
        // Be ready right now to call from class
        $_funtion_name = $_tmp_class."::".$_funtion_name;
    }

    $_funtion_args = $_object ['arguments'];
    $missed_argument = check_for_arguments ($_request_arguments, 
                                                $_funtion_args);
    if ($missed_argument != -1) {
        Answer::push ($E_NOT_ENOUGH_ARGS->cmt ($_funtion_args [$missed_argument],
                                                __FILE__."::".__FUNCTION__,
                                                __LINE__));
    }

    $_function_arguments = Array ();
    for ($i = 0; $i < count ($_object ['arguments']); $i ++) {
        // To send arguments to function in correct order (how in manifest)
        $arg_name = $_object ['arguments'][$i];
        if ($arg_name [0] == "?") {
            $arg_name = substr ($arg_name, 1);
        }

        $_function_arguments [] = $_request_arguments [$arg_name];
    }

    $_funtion_rights = $_object ['rights'];
    if (!check_for_rights ($_user, $_funtion_rights)) {
        Answer::push ($E_PERM_DENIED->cmt ("", __FILE__, 
                                                __LINE__));
    }

    $_result = call_user_func_array ($_funtion_name, $_function_arguments);
    if ($_result instanceof Answer) { Answer::push ($_result); }

?>