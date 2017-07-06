<?php

    final class Error {

        public $value;

        public function __construct ($code, $message) {
            $this->value = Array (
                'code' => $code,
                'message' => $message
            );
        }

        public function toJSON () {
            return json_encode ($this->value);
        }

        public function __toString () {
            return "[ERROR - ".$this->value ['code']."] "
                    .$this->value ['message'];
        }

    }

    // DB problems - code format 1***
    $DB_PROPS_NOT_FOUND_E  = new Error (1000, "Properties not found");
    $DB_PROPS_NOT_PARSED_E = new Error (1001, "Failed to parse properties");
    $DB_PROFL_NOT_FOUND_E  = new Error (1002, "DB profile not found");
    $DB_CONNECT_E          = new Error (1003, "Connection failed");

    // File problems - code format 2***
    $F_NOT_FOUND_E = new Error (2000, "File not found");

?>