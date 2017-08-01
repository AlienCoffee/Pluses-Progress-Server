<?php

    final class Answer implements ArrayAccess {

        // Local container
        private $data;

        /* ===| CLASS METHODS |=== */

        public function __construct ($code, $type, $name) {
            $this->data = Array (
                'code'    => $code,
                'type'    => $type,
                'name'    => $name,
                'message' => "",
                'trace'   => Array ()
            );
        }

        public function toJSON () {
            return json_encode ($this->data);
        }

        public function cmt ($message = "", $method = "", $line = -1) {
            $answer = new Answer ($this ['code'],
                                    $this ['type'],
                                    $this ['name']);
            $answer ['message'] = $message;
            if ($method != "" || $line != -1) {
                $answer->data ['trace'] = array_slice ($this ['trace'], 0);
                array_push ($answer->data ['trace'], Array ($method, $line));
            }
            return $answer;
        }

        public function addTrace ($method, $line) {
            return $this->cmt ($this ['message'], $method, $line);
        }

        public function __toString () {
            $string  = "[ ".$this ['type']." ".$this ['code']
                            ." ] ".$this ['name']
                            .($this ['message'] 
                                ? ": ".$this ['message'] 
                                : "").br;
            $trace = $this ['trace'];
            for ($i = 0; $i < count ($trace); $i ++) {
                $string .= "\t ".($i + 1).". In <b>"
                            .($trace [$i][0]
                                ? $trace [$i][0]
                                : "...")
                            ."</b> (line ".($trace [$i][1] != -1
                                        ? $trace [$i][1]
                                        : "...").")".br;
            }
            return $string;
        }

        public static function push ($answer) {
            if (!($answer instanceof Answer) || !$answer) { return; }
            echo ($answer->toJSON ().br);
            @DB::close ();
            exit (0);
        }

        /* ===| INTERFACE METHODS |=== */

        public function offsetGet ($offset) {
            return isset ($this->data [$offset]) 
                    ? $this->data [$offset] 
                    : null;
        }

        public function offsetSet ($offset, $value) {
            if ($offset === null) {
                $this->data [] = $value;
            } else {
                $this->data [$offset] = $value;
            }
        }

        public function offsetExists ($offset) {
            return isset ($this->data [$offset]);
        }

        public function offsetUnset ($offset) {
            unset ($this->data [$offset]);
        }

    }

    // Errors //

    // DB ( 1*** ) //
    $E_DB_PROFILE_NOT_FOUND = new Answer (1000, "Error", "No such DB profile in properties");
    $E_DB_CONNECTION_FAILED = new Answer (1001, "Error", "Connection failed");
    $E_DB_REQUEST_FAILED    = new Answer (1002, "Error", "Request to DB failed");

    // FILES ( 2*** ) //
    $E_FILE_NOT_FOUND       = new Answer (2000, "Error", "File not found");
    $E_FILE_EMPTY_EXTENSION = new Answer (2001, "Error", "Empty file extension unexpected");
    $E_FILE_WRONG_EXTENSION = new Answer (2002, "Error", "Wrong file extension");
    $E_FILE_PARSE_FAILED    = new Answer (2003, "Error", "Failed to parse file");

    // EXPRESSIONS ( 3*** ) //
    $E_REG_EXP_WRONG_FORMAT    = new Answer (3000, "Error", "Wrong regexp format");
    $E_EXP_NOT_IN_HEX_FORMAT   = new Answer (3001, "Error", "Wrong hex format");
    $E_EXP_NOT_IN_PATH_FORMAT  = new Answer (3002, "Error", "Wrong path format");
    $E_EXP_NOT_A_STRING        = new Answer (3003, "Error", "String was expected");
    $E_EXP_NOT_AN_ARRAY        = new Answer (3004, "Error", "Array was expected");
    $E_EXP_NOT_IN_PHONE_FORMAT = new Answer (3005, "Error", "Wrong phone format");

    // REQUESTS ( 4*** ) //
    $E_REQ_HAS_NO_TOKEN = new Answer (4000, "Error", "No token found in POST request");
    $E_DEST_NOT_FOUND   = new Answer (4001, "Error", "Requested destination not found");
    $E_PERM_DENIED      = new Answer (4002, "Error", "Permission denied");
    $E_NOT_ENOUGH_ARGS  = new Answer (4003, "Error", "Argument missed");
    $E_REQ_NOT_TERMINAL = new Answer (4004, "Error", "Undefined request path");

    // SECTIONS ( 5*** ) //
    $E_USER_NOT_EXISTS     = new Answer (5000, "Error", "User doesn't exists");
    $E_USER_WRONG_PASSWORD = new Answer (5001, "Error", "Wrong password or phone");
    $E_USER_EXISTS         = new Answer (5002, "Error", "User already exists");
    $E_WRONG_INVITE_CODE   = new Answer (5003, "Error", "Wrong invite code");

    // Success //

    $S_REQ_DONE = new Answer (1000, "Success", "Request done");

?>