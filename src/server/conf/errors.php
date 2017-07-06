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

    // File problems - code format 2***
    $F_NOT_FOUND_E = new Error (2000, "File not found");
    $F_UNKNOWN_E   = new Error (2001, "Unknow file extension");
    $F_WRONG_E     = new Error (2002, "Wrong file extension");

?>