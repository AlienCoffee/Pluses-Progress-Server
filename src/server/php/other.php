<?php

    function register_device ($code, $name, $os) {
        global $E_EXP_NOT_IN_HEX_FORMAT,
                $E_EXP_NOT_A_STRING,
                $E_DEVICE_ALREADY_REG,
                $S_REQ_DONE;

        check_input_data ($code, __regexp_hex__, $E_EXP_NOT_IN_HEX_FORMAT);

        if (!is_string ($name) || strlen ($name) < 3) {
            return $E_EXP_NOT_A_STRING->cmt ("name (length must be at least 3)",
                                                __FILE__."::".__FUNCTION__,
                                                __LINE__);
        }

        $name = trim ($name);
        $name = strtolower ($name);
        $name = htmlentities ($name, ENT_HTML5 
                                        | ENT_QUOTES);

        if (!is_string ($os) || strlen ($os) < 3) {
            return $E_EXP_NOT_A_STRING->cmt ("os (length must be at least 3)",
                                                __FILE__."::".__FUNCTION__,
                                                __LINE__);
        }

        $os = trim ($os);
        $os = strtolower ($os);
        $os = htmlentities ($os, ENT_HTML5 
                                    | ENT_QUOTES);

        $db_answer = DB::one_exists ("
            SELECT COUNT(*)
            FROM `devices`
            WHERE `code` = '$code'
            LIMIT 1
        ");
        if ($db_answer instanceof Answer) {
            return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                            __LINE__);
        } else if ($db_answer) {
            return $E_DEVICE_ALREADY_REG->cmt ($name." (os: $os)", 
                                                __FILE__."::".__FUNCTION__, 
                                                __LINE__);
        }

        $db_answer = DB::request ("
            INSERT
            INTO `devices` (`code`, `name`, `system`)
            VALUES ('$code', '$name', '$os')
        ");
        if ($db_answer instanceof Answer) {
            return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                            __LINE__);
        }

        return $S_REQ_DONE->cmt ("Successfully registered", 
                                    __FILE__."::".__FUNCTION__, 
                                    __LINE__);
    }

?>