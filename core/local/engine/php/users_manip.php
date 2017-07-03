<?php

	// Constants loaded from the calling script

	class UsersManip {
		
		private static $db;
		
		public static function set_db ($data_base) {
			UsersManip::$db = $data_base;
		}
		
		public static function identify ($token) {
			$user = Array (
				'id' => 0,
				'login' => "guest",
				'rights' => "v"
			);
			
			$db = UsersManip::$db;
			if ($db == null) {
				return $user;
			}
			
			$token = Utils::clear_spaces ($token);
			
			$startTimer = microtime (true);
			$db_answer = $db->query ("
				SELECT `users`.`id`,
						`phone`, 
						`rights`,
						`name`,
						`second_name`,
						`last_name`,
						`birthday`,
						`type`,
						`ip_address`
				FROM `sessions`
				LEFT JOIN `users`
					ON `sessions`.`user_id` = `users`.`id`
				LEFT JOIN `users_data`
					ON `users`.`data_id` = `users_data`.`id`
				LEFT JOIN `devices`
					ON `sessions`.`device_id` = `devices`.`id`
				WHERE `token` = '$token'
				LIMIT 1
			");
			$endTimer = microtime (true);
			//echo ("DB Transaction time: ".round ($endTimer - $startTimer, 3).br);
			
			if ($db_answer->num_rows) { $user = $db_answer->fetch_assoc (); }
			return $user; 
		}
		
		public static function has_access ($user, $need) {
			if (!$user || !isset ($user ['rights'])) {
				return false;
			}
			
			$user_rights = $user ['rights'];
			$access = true;
			
			for ($i = 0; $i < strlen ($need); $i ++) {
				$user_position = $i < strlen ($user_rights)
									? $user_rights [$i]
									: "*";
				$need_position = $need [$i];
				
				if ($need_position != "*" 
						&& $user_position != $need_position) {
					$access = false;
					break;
				}
			}
			
			return $access;
		}
		
		public static function authorize ($phone, $hpass, $device, $device_key) {
			$db = UsersManip::$db;
			if ($db == null) {
				$answer = Array (
					'type' => "error",
					'message' => "database internal error"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			// Check entered data
			$salt = __key1__;
			$hpass = md5 ($salt.$hpass.$salt);
			$phone = UsersManip::check_phone ($phone, $db);
			
			$db_answer = $db->query ("
				SELECT *
				FROM `users`
				WHERE `phone` = '$phone'
				LIMIT 1
			");
			
			if ($db_answer->num_rows != 1) {
				$answer = Array (
					'type' => "error",
					'message' => "phone not registered"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$db_user = $db_answer->fetch_assoc ();
			if ($db_user ['hpass'] != $hpass) {
				$answer = Array (
					'type' => "error",
					'message' => "wrong login or passport"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$user_id = $db_user ['id'];
			
			// Managing device 
			$device = Utils::clear_spaces ($device);
			$device = htmlentities ($device, ENT_HTML5 | ENT_QUOTES);
			
			$device_id = -1;
			// Check if device is registered
			$db_answer = $db->query ("
				SELECT COUNT(*)
				FROM `devices`
				WHERE `type` = '$device'
					AND `private_code` = MD5('".__key1__."$device_key')
				LIMIT 1
			")->fetch_assoc ();
			
			$p_code = md5 (__key1__.$device_key);
			if ($db_answer ['COUNT(*)'] == 0) {
				$db->query ("
					INSERT
					INTO `devices` (`type`, `private_code`, `registered`)
					VALUES('$device', '$p_code', UTC_TIMESTAMP())
				");
			}
			
			// Getting device ID
			$device_id = $db->query ("
				SELECT (`id`)
				FROM `devices`
				WHERE `type` = '$device'
					AND `private_code` = '$p_code'
				LIMIT 1
			")->fetch_assoc () ['id'];
			
			// Creating session and token
			$db_answer = $db->query ("
				SELECT COUNT(*)
				FROM `sessions`
				WHERE `user_id` = '$user_id'
					AND `device_id` = '$device_id'
					AND `duration` = 0 
				LIMIT 1
			")->fetch_assoc ();
			
			if ($db_answer ['COUNT(*)'] != 0) {
				$answer = Array (
					'type' => "error",
					'message' => "token is already renerated"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$timestamp = sha1 (time ().Utils::random_string (8));
			$extra_salt = Utils::random_string (8);
			$token = md5 (__key1__.$device_id
							.__key2__.$user_id
							.__key3__.$extra_salt).$timestamp;
			$db->query ("
				INSERT 
				INTO `sessions` (`user_id`, `device_id`, `token`, `created`, `duration`, `ip_address`) 
				VALUES($user_id, $device_id, '$token', '2000-01-01 00:00:00', 0, 'not detected')
			");
			
			$answer = Array (
				'type' => "success",
				'message' => "",
				'token' => $token
			);
			
			echo (json_encode ($answer));
		}
		
		public static function register ($phone, $hpass, $hcode) {
			$db = UsersManip::$db;
			if ($db == null) {
				$answer = Array (
					'type' => "error",
					'message' => "database internal error"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$phone = UsersManip::check_phone ($phone, $db);
			
			$db_answer = $db->query ("
				SELECT COUNT(*)
				FROM `users`
				WHERE `phone` = '$phone'
				LIMIT 1
			")->fetch_assoc ();
			$number = $db_answer ['COUNT(*)'];
			
			if ($number != 0) {
				$answer = Array (
					'type' => "error",
					'message' => "phone number is used"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$salt = __key1__;
			$hpass = md5 ($salt.$hpass.$salt);
			
			$rights = "v";
			if (strlen ($hcode) > 0) {
				$db_answer = $db->query ("
					SELECT (`rights`)
					FROM `invite_codes`
					WHERE `hcode` = '$hcode'
					LIMIT 1
				");
				
				if ($db_answer->num_rows != 1) {
					$answer = Array (
						'type' => "error",
						'message' => "wrong invite code"
					);
					
					echo (json_encode ($answer));
					return;
				}
				
				$rights = $db_answer->fetch_assoc () ['rights'];
				$db->query ("
					REMOVE 
					FROM `invite_codes` 
					WHERE `hcode` = '$hcode'
					LIMIT 1
				");
			}
			
			// Auto generated temp data 
			$name = Utils::random_string (16);
			$sec_name = Utils::random_string (16);
			$lst_name = Utils::random_string (16);
			
			// Creating new line in `users_data` table
			$db->query ("
				INSERT
				INTO `users_data` (`name`, `second_name`, `last_name`, `birthday`)
				VALUES('$name', '$sec_name', '$lst_name', '0000-01-01')
			");
			
			// Getting id of new line in data base
			$db_id = $db->query ("
				SELECT (`id`)
				FROM `users_data`
				WHERE `name` = '$name'
					AND `second_name` = '$sec_name'
					AND `last_name` = '$lst_name'
				LIMIT 1
			")->fetch_assoc () ['id'];
			
			// Clearing auto generated data to default
			$db->query ("
				UPDATE `users_data`
				SET `name` = '',
					`second_name` = '',
					`last_name` = ''
				WHERE `id` = $db_id
				LIMIT 1
			");
			
			// Creating new line of user
			$db->query ("
				INSERT
				INTO `users` (`data_id`, `phone`, `hpass`, `rights`, `registered`)
				VALUES($db_id, '$phone', '$hpass', '$rights', UTC_TIMESTAMP())
			");
			
			// Finishing registration
			$answer = Array (
				'type' => "success",
				'code' => "200",
				'message' => "successfully registered"
			);
			
			echo (json_encode ($answer));
		}
		
		// NOTICE: Unsecured (need to be rewritten)
		public static function remove ($id) {
			$_user = $GLOBALS ['_user'];
			
			$db = UsersManip::$db;
			if ($db == null) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "database internal error"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$id = Utils::clear_spaces ($id);
			$db_answer = $db->query ("
				SELECT `data_id`
				FROM `users`
				WHERE `id` = '$id'
				LIMIT 1
			");
			
			if ($db_answer->num_rows == 0) {
				$answer = Array (
					'type' => "success",
					'code' => "",
					'message' => "user doesn't exist"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$data_id = $db_answer->fetch_assoc () ['data_id'];
			require_once __core__."/api/groups_manip.php";
			
			for ($i = 0; $i < $db_answer->num_rows; $i ++) {
				$group_id = $db_answer->fetch_assoc () ['id'];
				
				ob_start ();
				GroupsManip::remove_user ($group_id, $id);
				ob_clean ();
			}
			
			$db->query ("
				DELETE 
				FROM `sessions`
				WHERE `user_id` = '$id'
			");
			$db->query ("
				DELETE
				FROM `users`
				WHERE `id` = '$id'
				LIMIT 1
			");
			$db->query ("
				DELETE
				FROM `users_data`
				WHERE `id` = '$data_id'
				LIMIT 1
			");
			
			// TODO: Delete from the group
			$db_answer = $db->query ("
				SELECT `id`
				FROM `groups`
			");
			
			$answer = Array (
				'type' => "success",
				'code' => "",
				'message' => "user removed"
			);
			
			echo (json_encode ($answer));
		}
	
		// NOTICE: Unsecured (need to be rewritten)
		public static function get_user_data ($id) {
			$_user = $GLOBALS ['_user'];
			
			// Data is already loaded
			if ($id == $_user ['id']) {
				return json_encode ($_user);
			}
			
			$db = UsersManip::$db;
			if ($db == null) {
				$answer = Array (
					'type' => "error",
					'message' => "database internal error"
				);
				
				echo (json_encode ($answer));
				return null;
			}
			
			$id = Utils::clear_spaces ($id);
			$db_answer = $db->query ("
				SELECT `phone`,
						`rights`,
						`name`,
						`second_name`,
						`last_name`,
						`birthday`
				FROM `users`
				LEFT JOIN `users_data`
					ON `users`.`data_id` = `users_data`.`id`
				WHERE `users`.`id` = '$id'
				LIMIT 1
			");
			
			if ($db_answer->num_rows != 1) {
				$answer = Array (
					'type' => "error",
					'message' => "user with given id doesn't exist"
				);
				
				echo (json_encode ($answer));
				return null;
			}
			
			$user = $db_answer->fetch_assoc ();
			$user ['id'] = $id;
			
			echo (json_encode ($user));
			return $user;
		}
		
		private static function check_phone ($phone, $db) {
			$phone = Utils::clear_spaces ($phone);
			$phone = UsersManip::prepare_number ($phone);
			//$phone = htmlentities ($phone, ENT_HTML5 
			//								| ENT_QUOTES);
			$phone_gerexp = "/^(|8|\+7|)[0-9]{10}$/i";
			if (!preg_match ($phone_gerexp, $phone)) {
				$answer = Array (
					'type' => "error",
					'message' => "wrong phone format"
				);
				
				echo (json_encode ($answer));
				$db->close ();
				exit (0);
			}
			
			if ($phone [0] == '8' && strlen ($phone) == 1) {
				$phone = substr ($phone, 1);
			} else if ($phone [0] == '+') {
				$phone = substr ($phone, 2);
			}
			
			return $phone;
		}
		
		private static function prepare_number ($phone) {
			if (!is_string ($phone)) { return null; }
			
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