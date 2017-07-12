<?php

    function get_file_extension ($filename) {
        $index = strrpos ($filename, ".");
        if ($index === false) { return ""; }
        return substr ($filename, $index + 1);
    }

    function load_config_file ($filename) {
        global $F_NOT_FOUND_E, 
                $F_UNKNOWN_E, 
                $F_WRONG_E,
                $F_NOT_PARSED_E;
        
        $extension = get_file_extension ($filename);
        if ($extension == "") { Error::push ($F_UNKNOWN_E); }
        
        $file_path = __conf__."/".$filename;
        if (!file_exists ($file_path)) {
            Error::push ($F_NOT_FOUND_E->cmt ($file_path, __FUNCTION__));
        }

        $data = Array ();
        if ($extension == "ini") {
            // Parse as `ini` config file
            $data = @parse_ini_file ($file_path, true);
        } else if ($extension == "json") {
            // Parse as `json` config file
            $content = @file_get_contents ($file_path);
            $content = utf8_encode ($content);
            $data = @json_decode ($content, true);
        } else { Error::push ($F_WRONG_E); }

        if ($data === null || $data === false) {
            Error::push ($F_NOT_PARSED_E->cmt ($file_path, __FUNCTION__));
        }

        return $data;
    }

    function check_input_data ($data, $regexp, $error) {
        if ($data == null && $regexp != "") {
            Error::push ($error);
        }

        $result = @preg_match ($regexp, $data);
        if ($result === 1) {
            return true;
        } else if ($result === 0) {
            if ($error != null) { Error::push ($error); }
            return false;
        } else {
            global $DF_WRONG_REGEXP_E;
            Error::push ($DF_WRONG_REGEXP_E);
        }
    }

    function clear_from_spaces ($string) {
        if (!is_string ($string)) { return ""; }

        $result = "";
        for ($i = 0; $i < strlen ($string); $i ++) {
            $char = $string [$i];
            if (!($char == ' ' || $char == '\n' || $char == '\r')) {
                $result .= $char;
            }
        }
        
        return $result;
    }

    function identify_user ($token) {
        global $DB_FAILED_E;
        $db = DB::connect ();
        
        // Guest user pre-set
        $user = Array (
            'rights' => ""
        );

        $token = clear_from_spaces ($token);
        $db_answer = $db->query ("
            SELECT `user_id`,
                    `phone`,
                    `rights`,
                    `device_id`
            FROM `sessions`
            LEFT JOIN `users`
                ON `sessions`.`user_id` = `users`.`id`
            WHERE `token` = '$token'
            LIMIT 1
        ") or die ($DB_FAILED_E->cmt ($db->error, __FUNCTION__)->toJSON ());

        if ($db_answer->num_rows == 1) {
            // User was found -> return his data
            $user = $db_answer->fetch_assoc ();
        }

        return $user;
    }

    function get_by_path ($source, $path) {
        global $DF_NOT_ARRAY_E,
                $DF_NO_KEY_E;

        if (!is_array ($source)) {
            Error::push ($DF_NOT_ARRAY_E->cmt ("source", __FUNCTION__));
        } else if (!is_array ($path)) {
            Error::push ($DF_NOT_ARRAY_E->cmt ("path", __FUNCTION__));
        }

        for ($depth = 0; $depth < count ($path); $depth ++) {
            $section = $path [$depth];
            if (!array_key_exists ($section, $source)) {
                $source = null; // Mark that `source` is null
                break;

                // This is not necessary ... TODO: delete in future
                // Error::push ($DF_NO_KEY_E->cmt ($section, __FUNCTION__));
            }

            // Go one level down
            $source = $source [$section];
        }

        return $source;
    }

    function check_for_rights ($user, $need_rights) {
        if (!$user || !isset ($user ['rights'])) {
            return false;
        }

        $user_rights = $user ['rights'];
        $access = true;
        
        for ($i = 0; $i < strlen ($need_rights); $i ++) {
            $user_position = $i < strlen ($user_rights)
                                ? $user_rights [$i]
                                : "*";
            $need_position = $need_rights [$i];
            
            if ($need_position != "*" 
                    && $user_position != $need_position) {
                $access = false;
                break;
            }
        }
			
		return $access;
    }

    function check_for_arguments ($arguments, $need_arguments) {
        global $DF_NOT_ARRAY_E;
        
        if (!is_array ($arguments) || !is_array ($need_arguments)) {
            Error::push ($DF_NOT_ARRAY_E->cmt ("arguments", __FUNCTION__));
        }

        $missed = -1;
        for ($i = 0; $i < count ($need_arguments); $i ++) {
            $argument = $need_arguments [$i];

            if (!array_key_exists ($argument, $arguments)) {
                $missed = $i;
                break;
            }
        }

        return $missed;
    }

    function load_file ($file_object) {
        global $_user,
                $_sources;
        global $F_NOT_FOUND_E,
                $RQ_NO_RIGHTS_E,
                $DF_UNKNOWN_TYPE_E,
                $DF_NOT_ARRAY_E,
                $DF_NO_KEY_E;

        if (!is_array ($file_object)) {
            // Array with data was expected
            return $DF_NOT_ARRAY_E->cmt ("file_object", __FUNCTION__);
        }

        if (!array_key_exists ("type", $file_object)) {
            // Unknown file type
            return $DF_NO_KEY_E->cmt ("type", __FUNCTION__);
        }

        if (!array_key_exists ("src", $file_object)) {
            // Unknown file location
            return $DF_NO_KEY_E->cmt ("src", __FUNCTION__);
        }

        if (array_key_exists ("rights", $file_object)) {
            if (!check_for_rights ($_user, $file_object ['rights'])) {
                return $RQ_NO_RIGHTS_E;
            }
        }

        if ($file_object ['type'] == "class"
                || $file_object ['type'] == "page") {
            $prefix = ".";

            if ($file_object ['type'] == "class") {
                $prefix = __php__;
            } else if ($file_object ['type'] == "page") {
                $prefix = __frames__;
            }

            $path = $prefix."/".$file_object ['src'];
            if (!file_exists ($path)) { // Ooooops - no file found
                return $F_NOT_FOUND_E->cmt ($path, __FUNCTION__);
            }

            require_once $path; // DONE
            return true;
        } else {
            return $DF_UNKNOWN_TYPE_E->cmt ($file_object ['type'], __FUNCTION__);
        }
    }

    function load_path ($path) {
        global $_user,
                $_sources,
                $_request_arguments;
        global $F_NOT_FOUND_E,
                $F_NOT_LOADED_E,
                $RQ_NO_ARGUMENT_E,
                $RQ_NO_RIGHTS_E,
                $RQ_NOT_FOUND_E,
                $RQ_NOT_ENABLED_E,
                $RQ_NOT_IMPL_E,
                $DF_NOT_PATH_E,
                $DF_NO_KEY_E;

        if (is_string ($path) && $path [0] == '/') {
            // Removing first slash char `/`
            $path = substr ($path, 1);
        }
        check_input_data (trim ($path), __regexp_path__, $DF_NOT_PATH_E);

        $split_path = split ("\.", trim ($path));
        $object = get_by_path ($_sources, $split_path);
        $context = Array ();

        if ($object == null) {
            Error::push ($RQ_NOT_FOUND_E->cmt ($path, __FUNCTION__));
        }

        if (array_key_exists ("#file", $object)) {
            $load_result = load_file ($object ['#file']);
            if ($load_result instanceof Error) {
                Error::push ($load_result);
            }

            if (!array_key_exists ("type", $object ['#file'])) {
                Error::push ($DF_NO_KEY_E->cmt ("type", __FUNCTION__));
            }

            $context ['enabled'] = $object ['#file']['enabled'];
            $context ['type'] = $object ['#file']['type'];
            if ($context ['type'] == "class"
                    && !array_key_exists ("class", $object ['#file'])) {
                Error::push ($DF_NO_KEY_E->cmt ("class", __FUNCTION__));
            } else if ($context ['type'] == "class") {
                $context ['class'] = $object ['#file']['class'];
            }
        } else {
            // Seems to be a method
            $parent_split_path = $split_path;

            do {
                if (count ($parent_split_path) <= 0) {
                    // No information about the file to include
                    Error::push ($RQ_NOT_FOUND_E);
                }

                $parent_path_length = count ($parent_split_path);
                $parent_split_path = array_slice ($parent_split_path, 
                                                    0, $parent_path_length - 1);
                $parent_object = get_by_path ($_sources, $parent_split_path);
                if (array_key_exists ("#file", $parent_object)) {
                    $load_result = load_file ($parent_object ['#file']);
                    if ($load_result instanceof Error) {
                        Error::push ($load_result);
                    }

                    if (!array_key_exists ("type", $parent_object ['#file'])) {
                        Error::push ($DF_NO_KEY_E->cmt ("type", __FUNCTION__));
                    }

                    $context ['enabled'] = $parent_object ['#file']['enabled'];
                    $context ['type'] = $parent_object ['#file']['type'];
                    if ($context ['type'] == "class"
                            && !array_key_exists ("class", $parent_object ['#file'])) {
                        Error::push ($DF_NO_KEY_E->cmt ("class", __FUNCTION__));
                    } else if ($context ['type'] == "class") {
                        $context ['class'] = $parent_object ['#file']['class'];
                    }

                    break; // Context got from the parent node
                }
            } while (true);
        }

        if (array_key_exists ("function", $object)) {
            // Calling for the static function in some class
            if (!array_key_exists ("rights", $object)) { // No rights declared -> it's strange
                Error::push ($DF_NO_KEY_E->cmt ("rights", __FUNCTION__));
            }

            if (!check_for_rights ($_user, $object ['rights'])) {
                Error::push ($RQ_NO_RIGHTS_E->cmt ($_user ['login'], __FUNCTION__));
            }

            $missed_argument = check_for_arguments ($_request_arguments, $object ['arguments']);
            if ($missed_argument != -1) { // Argument with such index (missed_argument) is missed
                Error::push ($RQ_NO_ARGUMENT_E->cmt ($object ['arguments'][$missed_argument]));
            }

            $function_arguments = Array ();
            for ($i = 0; $i < count ($object ['arguments']); $i ++) {
                // To send arguments to function in correct order (how in manifest)
                $function_arguments [] = $_request_arguments [$object ['arguments'][$i]];
            }

            if (!$context ['enabled']) { // Is it accessable now or not (may be in work)
                Error::push ($RQ_NOT_ENABLED_E);
            }

            $method_name = $object ['function'];
            if ($context ['type'] == "class") {
                $class_name  = $context ['class'];

                // Calling for the requested funtion in custom class
                if (@call_user_func_array ("$class_name::$method_name", $function_arguments) === false) {
                    Error::push ($RQ_NOT_IMPL_E->cmt ("$class_name::$method_name"));
                }
            } else if ($context ['type'] == "library") {
                // Calling for the requested funtion in global namespace
                if (@call_user_func_array ("$method_name", $function_arguments) === false) {
                    Error::push ($RQ_NOT_IMPL_E->cmt ("$class_name::$method_name"));
                }
            }
        }
    }

?>