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
		
	}

?>