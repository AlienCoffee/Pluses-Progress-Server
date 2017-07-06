<?php

	class GroupsManip {
		
		public static function create ($name, $head_teacher) {
			$db = $GLOBALS ['_db'];
			if ($db == null) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "database internal error"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$name = trim ($name);
			$name = htmlentities ($name, ENT_HTML5 
											| ENT_QUOTES);
			$groups = $db->query ("
				SELECT COUNT(*)
				FROM `groups`
				WHERE `name` = '$name'
				LIMIT 1
			")->fetch_assoc ();
			
			if ($groups ['COUNT(*)'] != 0) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "group already exists"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			ob_start ();
			$head = UsersManip::get_user_data ($head_teacher);
			ob_clean ();
			
			if ($head == null) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "head teacher doesn't exist"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			if (!UsersManip::has_access ($head, "vucg")) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "user given as teacher doesn't have enough rights"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$rand_table_list = Utils::random_string (16);
			$rand_table_topics = Utils::random_string (16);
			$db->query ("
				INSERT
				INTO `groups` (`name`, `table_list`, `table_topics`, `head_teacher`)
				VALUES('$name', '$rand_table_list', '$rand_table_topics', $head_teacher)
			");
			
			$group_id = $db->query ("
				SELECT `id`
				FROM `groups`
				WHERE `name` = '$name'
					AND `table_list` = '$rand_table_list'
					AND `table_topics` = '$rand_table_topics'
					AND `head_teacher` = $head_teacher
				LIMIT 1
			")->fetch_assoc () ['id'];
			
			$table_list_name = "group_".$group_id."_list";
			$db->query ("
				CREATE 
				TABLE `$table_list_name`
				(
					`id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`user_id` int(11) NOT NULL,
					`join_time` datetime NOT NULL,
					`leave_time` datetime NOT NULL,
					FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
				) COMMENT = ''
				COLLATE 'utf8_unicode_ci'
			");
			
			$table_topics_name = "group_".$group_id."_topics";
			$db->query ("
				CREATE 
				TABLE `$table_topics_name`
				(
					`id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`topic_id` int(11) NOT NULL,
					`table_results` mediumtext NOT NULL,
					`start_time` datetime NOT NULL,
					`end_time` datetime NOT NULL,
					FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`)
				) COMMENT = ''
				COLLATE 'utf8_unicode_ci'
			");
			
			$db_answer = $db->query ("
				UPDATE `groups`
				SET `table_list` = '$table_list_name',
					`table_topics` = '$table_topics_name'
				WHERE `id` = '$group_id'
				LIMIT 1
			");
			
			$enter_key = Utils::random_string (6).rand (0, 9).rand (0, 9);
			$db_answer = $db->query ("
				INSERT
				INTO `invite_codes` (`hcode`, `rights`)
				VALUES('$enter_key', 'group$group_id')
			");
			
			// Finishing registration
			$answer = Array (
				'type' => "success",
				'code' => "200",
				'message' => "successfully created"
			);
			
			echo (json_encode ($answer));
		}
		
		public static function get_group_data ($id) {
			$db = $GLOBALS ['_db'];
			if ($db == null) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "database internal error"
				);
				
				echo (json_encode ($answer));
				return null;
			}
			
			$id = Utils::clear_spaces ($id);
			$db_answer = $db->query ("
				SELECT *
				FROM `groups`
				WHERE `id` = '$id'
				LIMIT 1
			");
			
			if ($db_answer->num_rows != 1) {
				$answer = Array (
					'type' => "error",
					'message' => "group with given id doesn't exist"
				);
				
				echo (json_encode ($answer));
				return null;
			}
			
			$group = $db_answer->fetch_assoc ();
			echo (json_encode ($group));
			return $group;
		}
		
		public static function add_user ($group_id, $user_id) {
			$db = $GLOBALS ['_db'];
			if ($db == null) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "database internal error"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$group_id= Utils::clear_spaces ($group_id);
			$user_id= Utils::clear_spaces ($user_id);
			
			if (!$group_id || !$user_id) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "illegal arguments (group_id or user_id is empty)"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			ob_start ();
			$group = GroupsManip::get_group_data ($group_id);
			ob_clean ();
			
			if ($group == null) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "group doesn't exist"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			ob_start ();
			$user = UsersManip::get_user_data ($user_id);
			ob_clean ();
			
			if ($user == null) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "user doesn't exist"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$table_list_name = $group ['table_list'];
			
			$users = $db->query ("
				SELECT COUNT(*)
				FROM `$table_list_name`
				WHERE `user_id` = '$user_id'
					AND `join_time` = `leave_time`
			")->fetch_assoc ();
			
			if ($users ['COUNT(*)'] == 0) {
				$db_answer = $db->query ("
					INSERT
					INTO `$table_list_name` (`user_id`, `join_time`, `leave_time`)
					VALUES($user_id, UTC_TIMESTAMP(), UTC_TIMESTAMP())
				");
			} else {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "user already added"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			// Finishing registration
			$answer = Array (
				'type' => "success",
				'code' => "200",
				'message' => "successfully added"
			);
			
			echo (json_encode ($answer));
		}
		
		public static function join_group ($hcode) {
			$_user = $GLOBALS ['_user'];
			$db = $GLOBALS ['_db'];
			if ($db == null) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "database internal error"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$hcode = Utils::clear_spaces ($hcode);
			$db_answer = $db->query ("
				SELECT *
				FROM `invite_codes`
				WHERE `hcode` = '$hcode'
				LIMIT 1
			");
			
			if ($db_answer->num_rows == 0) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "wrong invite code"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$group = trim ($db_answer->fetch_assoc () ['rights']);
			if (!preg_match ("/^(group)[0-9]{2}$/i", $group)) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "wrong invite code"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$group_id = substr ($group, strlen ("group"));
			GroupsManip::add_user ($group_id, $_user ['id']);
		}
		
		public static function remove_user ($group_id, $user_id) {
			$db = $GLOBALS ['_db'];
			if ($db == null) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "database internal error"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$group_id= Utils::clear_spaces ($group_id);
			$user_id= Utils::clear_spaces ($user_id);
			
			$db_answer = $db->query ("
				SELECT `table_list`
				FROM `groups`
				WHERE `id` = '$group_id'
				LIMIT 1
			");
			
			if ($db_answer->num_rows != 1) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "group doesn't exist"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			// Also here must be removing from topic results !!!
			$table_list_name = $db->fetch_assoc () ['table_list'];
			$db_answer = $db->query ("
				DELETE
				FROM `$table_list_name`
				WHERE `user_id` = '$user_id'
			");
			
			// Finishing removing
			$answer = Array (
				'type' => "success",
				'code' => "200",
				'message' => "successfully removed"
			);
			
			echo (json_encode ($answer));
		}
		
		public static function add_topic ($group_id, $topic_id) {
			$db = $GLOBALS ['_db'];
			if ($db == null) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "database internal error"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$group_id= Utils::clear_spaces ($group_id);
			$topic_id= Utils::clear_spaces ($topic_id);
			
			$db_answer = $db->query ("
				SELECT *
				FROM `groups`
				WHERE `id` = '$group_id'
				LIMIT 1
			");
			
			if ($db_answer->num_rows != 1) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "group doesn't exist"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$group = $db_answer->fetch_assoc ();
			
			$db_answer = $db->query ("
				SELECT *
				FROM `topics`
				WHERE `id` = '$topic_id'
				LIMIT 1
			");
			
			if ($db_answer->num_rows != 1) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "topic doesn't exist"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$topic = $db_answer->fetch_assoc ();
			$table_results_name = "group_".$group ['id']."_topic_".$topic ['id']."_results";
			
			$db_answer = $db->query ("
				SELECT COUNT(*)
				FROM `$group[table_topics]`
				WHERE `$group[table_topics]`.`topic_id` = '$topic_id'
				LIMIT 1
			")->fetch_assoc () 
			or die ($db->error);
			
			if ($db_answer ['COUNT(*)'] != 0) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "topic already added"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$db_answer = $db->query ("
				INSERT
				INTO `$group[table_topics]` (`topic_id`, `table_results`, `start_time`, `end_time`)
				VALUES('$topic_id', '$table_results_name', UTC_TIMESTAMP(), UTC_TIMESTAMP())
			");
			
			// Creating table
			$db_answer = $db->query ("
				CREATE 
				TABLE `$table_results_name`
				(
					`id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`user_id` int(11) NOT NULL,
					`mask` text NOT NULL,
					FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
				) COMMENT = ''
				COLLATE 'utf8_unicode_ci'
			");
			
			if (!db_answer) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "internal error (try to remove topic and add again)"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			// Putting results mask into generated table
			$table_content_name = $topic ['table_content'];
			
			$db_answer = $db->query ("
				SELECT *
				FROM `$table_content_name`
				ORDER BY `index` ASC
			");
			
			$tasks_array = Array ();
			for ($i = 0; $i < $db_answer->num_rows; $i ++) {
				$tasks_array [] = $db_answer->fetch_assoc ();
			}
			$tasks_mask = GroupsManip::make_tasks_mask ($tasks_array);
			
			$table_list_name = $group ['table_list'];
			$db_answer = $db->query ("
				SELECT DISTINCT(`user_id`)
				FROM `$table_list_name`
				WHERE `join_time` = `leave_time`
			");
			
			$list_array = Array ();
			for ($i = 0; $i < $db_answer->num_rows; $i ++) {
				$list_array [] = $db_answer->fetch_assoc ();
			}
			
			foreach ($list_array as $key => $value) {
				$db_answer = $db->query ("
					INSERT
					INTO `$table_results_name` (`user_id`, `mask`)
					VALUES('$value[user_id]', '$tasks_mask')
				");
			}
			
			// Finishing creation
			$answer = Array (
				'type' => "success",
				'code' => "200",
				'message' => "successfully created"
			);
			
			echo (json_encode ($answer));
		}
		
		private static function make_tasks_mask ($tasks) {
			$string = "";
			
			if (!is_array ($tasks)) { return $string; }
			for ($i = 0; $i < count ($tasks); $i ++) {
				$string .= "*";
			}
			
			return $string;
		}
		
	}

?>