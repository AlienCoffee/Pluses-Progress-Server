<?php

    function get_file_extension ($filename) {
        $index = strrpos ($filename, ".");
        if ($index === false) { return ""; }
        return substr ($filename, $index + 1);
    }

    function load_config_file ($filename) {
        global $E_FILE_NOT_FOUND, 
                $E_FILE_EMPTY_EXTENSION, 
                $E_FILE_WRONG_EXTENSION,
                $E_FILE_PARSE_FAILED;
        
        $extension = get_file_extension ($filename);
        if ($extension == "") {
            return $E_FILE_EMPTY_EXTENSION->cmt ("", __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
        }
        
        $file_path = __conf__."/".$filename;
        if (!file_exists ($file_path)) {
            return $E_FILE_NOT_FOUND->cmt ($file_path, 
                                            __FILE__."::".__FUNCTION__, 
                                            __LINE__);
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
        } else {
            return $E_FILE_WRONG_EXTENSION->cmt ("`ini` or `json` was expected",
                                                    __FILE__."::".__FUNCTION__,
                                                    __LINE__);
        }

        if ($data === null || $data === false) {
            return $E_FILE_PARSE_FAILED->cmt ($file_path,
                                                __FILE__."::".__FUNCTION__,
                                                __LINE__);
        }

        return $data;
    }

    function check_input_data ($data, $regexp, $error) {
        global $E_REG_EXP_WRONG_FORMAT,
                $E_EXP_NOT_A_STRING;

        if ($data == null && $regexp != "" && $error != null) {
            Answer::push ($error->cmt ("", __FILE__."::".__FUNCTION__, 
                                            __LINE__));
        }

        if (!is_string ($data) && !is_numeric ($data)) {
            Answer::push ($E_EXP_NOT_A_STRING->cmt ("data", __FILE__."::".__FUNCTION__, 
                                                        __LINE__));
        }

        $result = @preg_match ($regexp, $data);
        if ($result === 1) {
            return true;
        } else if ($result === 0) {
            if ($error != null) {
                Answer::push ($error->cmt ("", __FILE__."::".__FUNCTION__, 
                                                __LINE__));
            }
            return false;
        } else {
            Answer::push ($E_REG_EXP_WRONG_FORMAT->cmt ("", __FILE__."::".__FUNCTION__, 
                                                            __LINE__));
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

    function random_string ($length = 8) {
        $result = "";
        for ($i = 0; $i < $length; $i ++) {
            $result .= chr (ord ('a') + rand (0, 25));
        }
        return $result;
    }

    //
    // About user
    //

    function identify_user ($token) {
        $db = DB::connect ();
        if ($db instanceof Answer) {
            return $db->addTrace (__FILE__."::".__FUNCTION__, 
                                    __LINE__);
        }
        
        // Guest user pre-set
        $user = Array (
            'rights' => "v"
        );

        $token = clear_from_spaces ($token);
        $db_answer = DB::request ("
            SELECT `users`.`id`,
                    `phone`,
                    `rights`,
                    `device_id`,
                    `time_created`,
                    `time_duration`
            FROM `sessions`
            LEFT JOIN `users`
                ON `sessions`.`user_id` = `users`.`id`
            WHERE `token` = '$token'
            LIMIT 1
        ");
        if ($db_answer instanceof Answer) {
            return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                            __LINE__);
        }

        if ($db_answer->num_rows == 1) {
            // User was found -> return his data
            $user = $db_answer->fetch_assoc ();
        }

        return $user;
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
        global $E_EXP_NOT_AN_ARRAY;
        
        if (!is_array ($arguments) || !is_array ($need_arguments)) {
            Answer::push ($E_EXP_NOT_AN_ARRAY->cmt ("arguments", 
                                                        __FILE__."::".__FUNCTION__,
                                                        __LINE__));
        }

        $missed = -1;
        for ($i = 0; $i < count ($need_arguments); $i ++) {
            $argument = $need_arguments [$i];
            if ($argument [0] === "?") {
                // It's not necessary argument
                continue;
            }

            if (!array_key_exists ($argument, $arguments)) {
                $missed = $i;
                break;
            }
        }

        return $missed;
    }

?>