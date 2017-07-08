<?php

    class DB {

        private static $db = null;

        public static function connect () {
            global $DB_PROFL_NOT_FOUND_E,
                    $DB_CONNECT_E;

            // Already connected to database
            if (DB::$db !== null) { return DB::$db; }

            $profiles = load_config_file ("db.profiles.ini");
            if (!array_key_exists (__db_profile__, $profiles)) {
                Error::push ($DB_PROFL_NOT_FOUND_E);
            }
            $profile = $profiles [__db_profile__];

            DB::$db = @new mysqli ($profile ['host'],
                                    $profile ['login'],
                                    $profile ['password'],
                                    $profile ['database']);
            // Check for the connected
            if (DB::$db->connect_errno) {
                Error::push ($DB_CONNECT_E->cmt (DB::$db->connect_error)); 
            }

            return DB::$db;
        }

        public static function close () {
            if (DB::$db === null) { return; }
            DB::$db->close ();
            DB::$db = null;
        }

    }

?>