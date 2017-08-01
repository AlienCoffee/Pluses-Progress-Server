<?php

    final class UsersManip {

        public static function auth ($phone, $hpass, $device_id = 0) {
            global $E_USER_NOT_EXISTS,
                    $E_USER_WRONG_PASSWORD,
                    $S_REQ_DONE;

                    echo ($device_id.br);

            $hpass = md5 (__key_salt1__.$hpass.__key_salt1__);
            $phone = UsersManip::prepare_number ($phone);

            $db_answer = DB::request ("
                SELECT `id`,
                        `hpass`
                FROM `users`
                WHERE `phone` = '$phone'
                LIMIT 1
            ");

            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            if ($db_answer->num_rows != 1) {
                return $E_USER_NOT_EXISTS->cmt ("", 
                                                __FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }
            $user = $db_answer->fetch_assoc ();

            if ($user ['hpass'] != $hpass) {
                return $E_USER_WRONG_PASSWORD;
            }

            //////////////////////////////////

            $db_answer = DB::request ("
                SELECT `token`
                FROM `sessions`
                WHERE `user_id` = '$user[id]'
                    AND `device_id` = '$device_id'
                LIMIT 1
            ");

            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            if ($db_answer->num_rows == 1) {
                $token = $db_answer->fetch_assoc () ['token'];

                $answer = Array ( 'token' => $token );
                return $S_REQ_DONE->cmt ($answer,
                                            __FILE__."::".__FUNCTION__, 
                                            __LINE__);
            }

            $timestamp = sha1 (time ().random_string (8));
            $extra_salt = random_string (8);
            $token = md5 (__key_salt1__.$device_id
							.__key_salt2__.$user ['id']
							.__key_salt3__.$extra_salt).$timestamp;
            $db_answer = DB::request ("
                INSERT
                INTO `sessions`
                VALUES('$user[id]', '$device_id', '$token', NOW(), 0, 'ip address')
            ");

            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            $answer = Array ( 'token' => $token );
            return $S_REQ_DONE->cmt ($answer,
                                        __FILE__."::".__FUNCTION__, 
                                        __LINE__);
        }

        public static function create_user ($phone, $hpass, $hcode = "none") {
            global $E_EXP_NOT_IN_PHONE_FORMAT,
                    $E_WRONG_INVITE_CODE,
                    $E_USER_EXISTS,
                    $S_REQ_DONE;

            $phone = UsersManip::prepare_number ($phone);
            check_input_data ($phone, __regexp_phone__, $E_EXP_NOT_IN_PHONE_FORMAT);

            $user_exists = DB::one_exists ("
                SELECT COUNT(*)
                FROM `users`
                WHERE `phone` = '$phone'
                LIMIT 1
            ");
            if ($user_exists instanceof Answer) {
                return $user_exists->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            } else if ($user_exists) {
                return $E_USER_EXISTS;
            }

            $hpass = md5 (__key_salt1__.$hpass.__key_salt1__);
            $rights = "v";

            if (check_input_data ($hcode, __regexp_hex__, null)) {
                $db_answer = DB::request ("
                    SELECT `value`
                    FROM `codes`
                    WHERE `hcode` = '$hcode'
                        AND `type` = 'rights'
                    LIMIT 1
                ");
                if ($db_answer instanceof Answer) {
                    return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                    __LINE__);
                }

                if ($db_answer->num_rows != 1) {
                    return $E_WRONG_INVITE_CODE;
                }

                $rights = $db_answer->fetch_assoc () ['value'];
            }

            $fake_name = random_string (32);
            $fake_last_name = random_string (32);
            $fake_second_name = random_string (32);

            $db_answer = DB::request ("
                INSERT
                INTO `users-data` (`name`, `last_name`, `second_name`, `birthday`)
                VALUES ('$fake_name', '$fake_last_name', '$fake_second_name', '01-01-0001')
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            $db_answer = DB::request ("
                SELECT `id`
                FROM `users-data`
                WHERE `name` = '$fake_name'
                    AND `last_name` = '$fake_last_name'
                    AND `second_name` = '$fake_second_name'
                LIMIT 1
            ");

            // ID of line with personal data about current user
            $users_data_id = $db_answer->fetch_assoc () ['id'];

            $db_answer = DB::request ("
                UPDATE `users-data`
                SET `name` = '', 
                    `last_name` = '', 
                    `second_name` = ''
                WHERE `id` = $users_data_id
                LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            $db_answer = DB::request ("
                INSERT
                INTO `users` (`phone`, `hpass`, `rights`, `data_id`)
                VALUES ('$phone', '$hpass', '$rights', $users_data_id)
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            return $S_REQ_DONE;
        }

        private static function prepare_number ($phone) {
			if (!is_string ($phone)) { return ""; }
            $phone = clear_from_spaces ($phone);
			
            if ($phone [0] == '+' && $phone [1] == '7') {
                $phone = substr ($phone, 2);
            } else if ($phone [0] == '8' && strlen ($phone) == 11) {
                $phone = substr ($phone, 1);
            }

			$result = "";
			for ($i = 0; $i < strlen ($phone); $i ++) {
				$char = $phone [$i];
				if (!($char == '(' || $char == ')' || $char == '-')) {
					$result .= $char;
				}
			}

			return $result;
		}

    }

?>