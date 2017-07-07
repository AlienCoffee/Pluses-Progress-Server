<?php

    function get_file_extension ($filename) {
        $index = strrpos ($filename, ".");
        if ($index === false) { return ""; }
        return substr ($filename, $index + 1);
    }

    function load_config_file ($filename) {
        global $F_NOT_FOUND_E, 
                $F_UNKNOWN_E, 
                $F_WRONG_E;
        
        $extension = get_file_extension ($filename);
        if ($extension == "") { Error::push ($F_UNKNOWN_E); }
        
        $file_path = __conf__."/".$filename;
        if (!file_exists ($file_path)) {
            Error::push ($F_NOT_FOUND_E->cmt ($file_path, __FUNCTION__));
        }

        $data = Array ();
        if ($extension == "ini") {
            // Parse as `ini` config file
            $data = parse_ini_file ($file_path, true);
        } else if ($extension == "json") {
            // Parse as `json` config file
            $content = @file_get_contents ($file_path);
            $content = utf8_encode ($content);
            $data = json_decode ($content, true);
        } else { Error::push ($F_WRONG_E); }

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

?>