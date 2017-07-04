<?php

	class TopicsManip {
		
		public static function create ($name) {
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
			
			$name = trim ($name);
			$name = htmlentities ($name, ENT_HTML5 
											| ENT_QUOTES);
											
			$db_answer = $db->query ("
				SELECT COUNT(*)
				FROM `topics`
				WHERE `name` = '$name'
				LIMIT 1
			")->fetch_assoc ();
			
			if ($db_answer ['COUNT(*)'] != 0) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "topic already exists"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$table_content_name = Utils::random_string (16);
			$author = $_user ['id'];
			
			$db_answer = $db->query ("
				INSERT
				INTO `topics` (`name`, `table_content`, `author`)
				VALUES('$name', '$table_content_name', '$author')
			") or die ($db->error);
			
			$db_answer = $db->query ("
				SELECT `id`
				FROM `topics`
				WHERE `name` = '$name'
					AND `author` = '$_user[id]'
				LIMIT 1
			");
			
			$topic_id = $db_answer->fetch_assoc () ['id'];
			$table_content_name = "topic_".$topic_id."_content";
			$db_answer = $db->query ("
				UPDATE `topics`
				SET `table_content` = '$table_content_name'
				WHERE `id` = '$topic_id'
				LIMIT 1
			");
			
			$db_answer = $db->query ("
				CREATE 
				TABLE `$table_content_name`
				(
					`id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`index` int(11) NOT NULL,
					`name` mediumtext NOT NULL,
					`rating` bit(1) NOT NULL
				) COMMENT = ''
				COLLATE 'utf8_unicode_ci'
			");
			
			// Finishing registration
			$answer = Array (
				'type' => "success",
				'code' => "200",
				'message' => "successfully created"
			);
			
			echo (json_encode ($answer));
		}
		
		public static function get_topic_data ($topic_id) {
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
			
			$topic_id = Utils::clear_spaces ($topic_id);
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
					'message' => "topic with given id doesn't exist"
				);
				
				echo (json_encode ($answer));
				return null;
			}
			
			$topic = $db_answer->fetch_assoc ();
			echo (json_encode ($topic));
			return $topic;
		}
		
		public static function change_author ($topic_id, $author) {
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
			
			$author = Utils::clear_spaces ($author);
			$db_answer = $db->query ("
				SELECT *
				FROM `users`
				WHERE `id` = '$author'
				LIMIT 1
			");
			
			if ($db_answer->num_rows != 1) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "user with given id doesn't exist"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$user = $db_answer->fetch_assoc ();
			if (!UsersManip::has_access ($user, "v*c*t")) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "user doesn't have enough rights"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$topic_id = Utils::clear_spaces ($topic_id);
			$db_answer = $db->query ("
				SELECT COUNT(*)
				FROM `topics`
				WHERE `id` = '$topic_id'
				LIMIT 1
			")->fetch_assoc ();
			
			if ($db_answer ['COUNT(*)'] != 1) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "topic with given id doesn't exist"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$db_answer = $db->query ("
				UPDATE `topics`
				SET `author` = '$author'
				WHERE `id` = '$topic_id'
				LIMIT 1
			");
			
			// Finishing change
			$answer = Array (
				'type' => "success",
				'code' => "200",
				'message' => "successfully changed"
			);
			
			echo (json_encode ($answer));
		}
		
		public static function add_task ($topic_id, $name, $rating, $index) {
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
			
			$topic_id = Utils::clear_spaces ($topic_id);
			$db_answer = $db->query ("
				SELECT `table_content`
				FROM `topics`
				WHERE `id` = '$topic_id'
				LIMIT 1
			");
			
			if ($db_answer->num_rows != 1) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "topic with given id doesn't exist"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			$name = trim ($name);
			$name = htmlentities ($name, ENT_HTML5 
											| ENT_QUOTES);
			$table_content_name = $db_answer->fetch_assoc () ['table_content'];
			$db_answer = $db->query ("
				SELECT COUNT(*)
				FROM `$table_content_name`
				WHERE `name` = '$name'
				LIMIT 1
			")->fetch_assoc ();
			
			if ($db_answer ['COUNT(*)'] != 0) {
				$answer = Array (
					'type' => "error",
					'code' => "",
					'message' => "task already exists"
				);
				
				echo (json_encode ($answer));
				return;
			}
			
			if (!isset ($index)) {
				$db_answer = $db->query ("
					SELECT MAX(`index`)
					FROM `$table_content_name`
					WHERE 1
				")->fetch_assoc ();
				
				$max = $db_answer ['MAX(`index`)'];
				if (!$max) { $index = 0; }
				else { $index = intval ($max) + 1; }
			} else {
				$db_answer = $db->query ("
					UPDATE `$table_content_name`
					SET `index` = `index` + 1
					WHERE `index` >= $index
				");
			}
			
			if ($rating) { $rating = 1; }
			else { $rating = 0; }
			
			$db_answer = $db->query ("
				INSERT
				INTO `$table_content_name` (`name`, `index`, `rating`)
				VALUES('$name', $index, $rating)
			") or die ($db->error);
			
			// Finishing add
			$answer = Array (
				'type' => "success",
				'code' => "200",
				'message' => "successfully added"
			);
			
			echo (json_encode ($answer));
		}
		
	}

?>