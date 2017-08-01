<?php

    final class DB {

        private static $db = null;

        public function connect () {
            global $E_DB_PROFILE_NOT_FOUND,
                    $E_DB_CONNECTION_FAILED;

            if (DB::$db != null) {
                return DB::$db;
            }

            $profiles = load_config_file ("db.profiles.ini");
            if ($profiles instanceof Answer) {
                return $profiles->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            if (!array_key_exists (__db_profile__, $profiles)) {
                return $E_DB_PROFILE_NOT_FOUND->cmt (__db_profile__, 
                                                        __FILE__."::".__FUNCTION__, 
                                                        __LINE__);
            }
            $profile = $profiles [__db_profile__];

            DB::$db = @new mysqli ($profile ['host'],
                                    $profile ['login'],
                                    $profile ['password'],
                                    $profile ['database']);
            // Check for the connected
            if (DB::$db->connect_errno) {
                return $E_DB_CONNECTION_FAILED->cmt (DB::$db->connect_error, 
                                                        __FILE__."::".__FUNCTION__, 
                                                        __LINE__);
            }

            return DB::$db;
        }

        public function request ($query) {
            global $E_DB_REQUEST_FAILED;
            $db = DB::connect ();

            $db_answer = $db->query ($query);
            if (!$db_answer) {
                return $E_DB_REQUEST_FAILED->cmt ($db->error, 
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            return $db_answer;
        }

        public function one_exists ($query) {
            global $E_DB_REQUEST_FAILED;
            $db = DB::connect ();

            $db_answer = $db->query ($query);
            if (!$db_answer) {
                return $E_DB_REQUEST_FAILED->cmt ($db->error, 
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            return @$db_answer->fetch_assoc () ['COUNT(*)'] == 1;
        }

        public function close () {
            // Already closeds
            if (DB::$db === null) {
                return;
            }

            DB::$db->close ();
            DB::$db = null;
        }

    }

?>