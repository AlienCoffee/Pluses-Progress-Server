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
			
			$name = Utils::clear_spaces ($name);
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
			") or die ($db->error);
			
			$table_topics_name = "group_".$group_id."_topics";
			$db->query ("
				CREATE 
				TABLE `$table_topics_name`
				(
					`id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`user_id` int(11) NOT NULL,
					`table_results` mediumtext NOT NULL,
					`start_time` datetime NOT NULL,
					`end_time` datetime NOT NULL,
					FOREIGN KEY (`user_id`) REFERENCES `topics` (`id`)
				) COMMENT = ''
				COLLATE 'utf8_unicode_ci'
			") or die ($db->error);
		}
		
	}

?>