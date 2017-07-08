<?php

    final class Error {

        public $value;

        public function __construct ($code, $message) {
            $this->value = Array (
                'code' => $code,
                'message' => $message,
                'comment' => "",
                'cause' => ""
            );
        }

        // Comment (add comment)
        public function cmt ($comment, $cause = "") {
            $error = new Error ($this->value ['code'],
                                $this->value ['message']);
            $error->value ['comment'] = $comment;
            $error->value ['cause'] = $cause;
            return $error;
        }

        public function toJSON () {
            return json_encode ($this->value);
        }

        public function __toString () {
            return "[ERROR - ".$this->value ['code']."] "
                    .$this->value ['message'].(
                        $this->value ['comment']
                            ? " (".$this->value ['comment'].(
                                $this->value ['cause']
                                    ? " in ".$this->value ['cause']
                                    : ""
                                ).")"
                            : ""
                    );
        }

        // ===| STATIC AREA |=== //

        public static function push ($error) {
            if (!($error instanceof Error) || !$error) { return; }
            echo ($error->toJSON ().br);
            @DB::close ();
            exit (0);
        }

    }

    // DB problems - code format 1***
    $DB_PROPS_NOT_FOUND_E  = new Error (1000, "Properties not found");
    $DB_PROPS_NOT_PARSED_E = new Error (1001, "Failed to parse properties");
    $DB_PROFL_NOT_FOUND_E  = new Error (1002, "DB profile not found");
    $DB_CONNECT_E          = new Error (1003, "Connection failed");
    $DB_NO_CONNEC_E        = new Error (1004, "Not connected to database");

    // File problems - code format 2***
    $F_NOT_FOUND_E  = new Error (2000, "File not found");
    $F_UNKNOWN_E    = new Error (2001, "Unknow file extension");
    $F_WRONG_E      = new Error (2002, "Wrong file extension");
    $F_NOT_PARSED_E = new Error (2003, "Failed to parse file");
    $F_NOT_LOADED_E = new Error (2004, "Failed to load file");

    // Request problems - code format 3***
    $RQ_WRONG_METHOD_E = new Error (3000, "Wrong request method");
    $RQ_NO_TOKEN_E     = new Error (3001, "Token was not found in arguments");
    $RQ_NO_ARGUMENT_E  = new Error (3002, "Required argument missed");

    $RQ_NO_RIGHTS_E    = new Error (3403, "Not enough rights for user");
    $RQ_NOT_FOUND_E    = new Error (3404, "Request destination not found");
    $RQ_NOT_ENABLED_E  = new Error (3423, "Request destination is not enabled now");

    // Data formats problems - code format 4***
    $DF_WRONG_REGEXP_E = new Error (4000, "Wrong regular expression");
    $DF_NOT_HEX_E      = new Error (4001, "String hasn't HEX format");
    $DF_NOT_PATH_E     = new Error (4002, "String hasn't PATH format");
    
    $DF_UNKNOWN_TYPE_E = new Error (4010, "Unknown file type");

    $DF_NOT_ARRAY_E    = new Error (4100, "Array was expected");
    $DF_NO_KEY_E       = new Error (4101, "Array key is absent");
    $DF_NOT_STRING_E   = new Error (4102, "String was expected");

?>