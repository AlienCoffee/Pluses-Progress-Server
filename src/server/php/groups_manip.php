<?php

    final class GroupsManip {

        public static function create_group ($name, $head_teacher) {
            global $E_EXP_NOT_A_STRING,
                    $E_NO_RIGHTS_FOR_HEAD,
                    $S_REQ_DONE;

            if (!is_string ($name) || strlen ($name) < 6) {
                return $E_EXP_NOT_A_STRING->cmt ("name (length must be at least 6)",
                                                    __FILE__."::".__FUNCTION__,
                                                    __LINE__);
            }

            $name = trim ($name);
			$name = htmlentities ($name, ENT_HTML5 
											| ENT_QUOTES);

            require_once __php__."/users_manip.php";
            $teacher = UsersManip::get_user_data ($head_teacher);
            if ($teacher instanceof Answer && $teacher ['type'] == "Error") {
                return $teacher->addTrace (__FILE__."::".__FUNCTION__,
                                            __LINE__);
            }

            $teacher_rights = $teacher ['message']['rights'];
            if (!check_for_rights (Array ( 'rights' => $teacher_rights ), "vugt")) {
                return $E_NO_RIGHTS_FOR_HEAD->cmt ($head_teacher, 
                                                    __FILE__."::".__FUNCTION__,
                                                    __LINE__);
            }

            ////////////////////////////////////////

            $fake_list_table   = random_string (32);
            $fake_topics_table = random_string (32);

            $db_answer = DB::request ("
                INSERT
                INTO `groups` (`name`, `list_table`, `topics_table`, `head_teacher`)
                VALUES ('$name', '$fake_list_table', '$fake_topics_table', '$head_teacher')
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            $db_answer = DB::request ("
                SELECT `id`
                FROM `groups`
                WHERE `list_table` = '$fake_list_table'
                    AND `topics_table` = '$fake_topics_table'
                    AND `name` = '$name'
                    AND `head_teacher` = '$head_teacher'
                LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            $group_id = $db_answer->fetch_assoc () ['id'];
            $list_table_name = "group-$group_id-list-table";
            $topics_table_name = "group-$group_id-topics-table";

            $db_answer = DB::request ("
                CREATE 
				TABLE `$list_table_name`
				(
					`id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`user_id` int(11) NOT NULL,
					`join_time` datetime NOT NULL,
					`leave_time` datetime NOT NULL,
					FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
				) COMMENT = ''
				COLLATE 'utf8_unicode_ci'
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            $db_answer = DB::request ("
                CREATE 
				TABLE `$topics_table_name`
				(
					`id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`topic_id` int(11) NOT NULL,
					`results_table` mediumtext NOT NULL,
					`start_time` datetime NOT NULL,
					`end_time` datetime NOT NULL,
					FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`)
				) COMMENT = ''
				COLLATE 'utf8_unicode_ci'
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            $db_answer = DB::request ("
                UPDATE `groups`
				SET `list_table` = '$list_table_name',
					`topics_table` = '$topics_table_name'
				WHERE `id` = '$group_id'
				LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            $enter_key = random_string (8).rand (0, 9).rand (0, 9);
            $db_answer = DB::request ("
                INSERT
                INTO `codes` (`hcode`, `type`, `value`)
                VALUES ('$enter_key', 'group', '$group_id')
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            return $S_REQ_DONE->cmt (Array ( 'code' => $enter_key ), 
                                        __FILE__."::".__FUNCTION__, 
                                        __LINE__);
        }

        public static function get_group_data ($group_id) {
            global $E_GROUP_NOT_EXISTS,
                    $S_REQ_DONE;

            $db_answer = DB::request ("
                SELECT *
                FROM `groups`
                WHERE `id` = '$group_id'
                LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            if ($db_answer->num_rows != 1) {
                return $E_GROUP_NOT_EXISTS->cmt ($group_id, 
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            $data = $db_answer->fetch_assoc ();
            $answer = Array (
                'name' => $data ['name'],
                'head_teacher' => $data ['head_teacher'],
                'list' => null,
                'topics' => null
            );

            ///////////////////////////

            $db_answer = DB::request ("
                SELECT COUNT(*)
                FROM `$data[list_table]`
                WHERE `join_time` = `leave_time`
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            $answer ['list'] = Array (
                'table' => $data ['list_table'],
                'size'  => $db_answer->fetch_assoc () ['COUNT(*)']
            );

            ///////////////////////////

            $db_answer = DB::request ("
                SELECT COUNT(*)
                FROM `$data[topics_table]`
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            $answer ['topics'] = Array (
                'table' => $data ['topics_table'],
                'size'  => $db_answer->fetch_assoc () ['COUNT(*)']
            );

            return $S_REQ_DONE->cmt ($answer, __FILE__."::".__FUNCTION__, 
                                                __LINE__);
        }

        public static function join_group ($code) {
            global $_user,
                    $E_USER_NOT_LOGINED,
                    $E_WRONG_INVITE_CODE;

            if (!array_key_exists ("id", $_user)) {
                return $E_USER_NOT_LOGINED->cmt ("guest",
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            $db_answer = DB::request ("
                SELECT `value`
                FROM `codes`
                WHERE `hcode` = '$code'
                    AND `type` = 'group'
                LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            if ($db_answer->num_rows != 1) {
                return $E_WRONG_INVITE_CODE->cmt ($code, 
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            $group_id = $db_answer->fetch_assoc () ['value'];

            $result = GroupsManip::add_user ($group_id, $_user ['id']);
            if ($result instanceof Answer) {
                return $result->addTrace (__FILE__."::".__FUNCTION__, 
                                            __LINE__);
            } else {
                return $result;
            }
        }

        public static function leave_group ($group_id) {
            global $_user,
                    $E_USER_NOT_LOGINED;

            if (!array_key_exists ("id", $_user)) {
                return $E_USER_NOT_LOGINED->cmt ("guest",
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            $result = GroupsManip::remove_user ($group_id, $_user ['id']);
            if ($result instanceof Answer) {
                return $result->addTrace (__FILE__."::".__FUNCTION__, 
                                            __LINE__);
            } else {
                return $result;
            }
        }

        public static function add_user ($group_id, $user_id) {
            global $E_GROUP_NOT_EXISTS,
                    $E_USER_ALREADY_JOINED,
                    $S_REQ_DONE;

            $db_answer = DB::request ("
                SELECT `list_table`
                FROM `groups`
                WHERE `id` = '$group_id'
                LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            if ($db_answer->num_rows != 1) {
                return $E_GROUP_NOT_EXISTS->cmt ($group_id, 
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            $list_table_name = $db_answer->fetch_assoc () ['list_table'];

            $already_joined = DB::one_exists ("
                SELECT COUNT(*)
                FROM `$list_table_name`
                WHERE `join_time` = `leave_time`
                    AND `user_id` = '$user_id'
                LIMIT 1
            ");
            if ($already_joined instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            if ($already_joined) {
                return $E_USER_ALREADY_JOINED->cmt ($group_id, 
                                                        __FILE__."::".__FUNCTION__, 
                                                        __LINE__);
            }

            $db_answer = DB::request ("
                INSERT
                INTO `$list_table_name` (`user_id`, `join_time`, `leave_time`)
                VALUES ('$user_id', UTC_TIMESTAMP(), UTC_TIMESTAMP())
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            return $S_REQ_DONE->cmt ("Successfully joined", 
                                        __FILE__."::".__FUNCTION__, 
                                        __LINE__);
        }

        public static function remove_user ($group_id, $user_id) {
            global $E_GROUP_NOT_EXISTS,
                    $E_USER_NOT_JOINED,
                    $S_REQ_DONE;

            $db_answer = DB::request ("
                SELECT `list_table`
                FROM `groups`
                WHERE `id` = '$group_id'
                LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            if ($db_answer->num_rows != 1) {
                return $E_GROUP_NOT_EXISTS->cmt ($group_id, 
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            $list_table_name = $db_answer->fetch_assoc () ['list_table'];

            $joined = DB::one_exists ("
                SELECT COUNT(*)
                FROM `$list_table_name`
                WHERE `user_id` = '$user_id'
                    AND `join_time` = `leave_time`
                LIMIT 1
            ");
            if ($joined instanceof Answer) {
                return $joined->addTrace (__FILE__."::".__FUNCTION__, 
                                            __LINE__);
            }

            if (!$joined) {
                return $E_USER_NOT_JOINED->cmt ($group_id, 
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            $db_answer = DB::request ("
                UPDATE `$list_table_name`
                SET `leave_time` = UTC_TIMESTAMP()
                WHERE `user_id` = '$user_id'
                    AND `join_time` = `leave_time`
                LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            return $S_REQ_DONE->cmt ("Successfully leaved", 
                                        __FILE__."::".__FUNCTION__, 
                                        __LINE__);
        }

        /* UNCHECKED */
        public static function move_user ($group_id_from, $group_id_to, $user_id) {
            global $E_UNEXPECTED,
                    $S_REQ_DONE;

            $result = GroupsManip::add_user ($group_id_to, $user_id);
            if (!($result instanceof Answer)) {
                return $E_UNEXPECTED->cmt ("addUser", 
                                            __FILE__."::".__FUNCTION__, 
                                            __LINE__);
            }

            if ($result ['type'] == "Error") {
                return $result->addTrace (__FILE__."::".__FUNCTION__, 
                                            __LINE__);
            }

            $result = GroupsManip::remove_user ($group_id_from, $user_id);
            if (!($result instanceof Answer)) {
                return $E_UNEXPECTED->cmt ("removeUser", 
                                            __FILE__."::".__FUNCTION__, 
                                            __LINE__);
            }

            if ($result ['type'] == "Error") {
                return $result->addTrace (__FILE__."::".__FUNCTION__, 
                                            __LINE__);
            }

            return $S_REQ_DONE->cmt ("Successfully moved",
                                        __FILE__."::".__FUNCTION__, 
                                        __LINE__);
        }

        public static function rename_group ($group_id, $name) {
            global $E_EXP_NOT_A_STRING,
                    $E_GROUP_NOT_EXISTS,
                    $S_REQ_DONE;

            if (!is_string ($name) || strlen ($name) < 6) {
                return $E_EXP_NOT_A_STRING->cmt ("name (length must be at least 6)",
                                                    __FILE__."::".__FUNCTION__,
                                                    __LINE__);
            }

            $name = trim ($name);
			$name = htmlentities ($name, ENT_HTML5 
											| ENT_QUOTES);

            $group_exists = DB::one_exists ("
                SELECT COUNT(*)
                FROM `groups`
                WHERE `id` = '$group_id'
                LIMIT 1
            ");
            if ($group_exists instanceof Answer) {
                return $group_exists->addTrace (__FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            } else if (!$group_exists) {
                $E_GROUP_NOT_EXISTS->cmt ($group_id,
                                            __FILE__."::".__FUNCTION__, 
                                            __LINE__);
            }

            $db_answer = DB::request ("
                UPDATE `groups`
                SET `name` = '$name'
                WHERE `id` = '$group_id'
                LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            return $S_REQ_DONE->cmt ("Successfully renamed",
                                        __FILE__."::".__FUNCTION__, 
                                        __LINE__);
        }

        public static function add_topic ($group_id, $topic_id) {
            global $E_GROUP_NOT_EXISTS,
                    $E_TOPIC_NOT_EXISTS,
                    $E_TOPIC_ALREADY_ADDED,
                    $S_REQ_DONE;

            $db_answer = DB::request ("
                SELECT `topics_table`
                FROM `groups`
                WHERE `id` = '$group_id'
                LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            } else if ($db_answer->num_rows != 1) {
                return $E_GROUP_NOT_EXISTS->cmt ($group_id,
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            $topics_table_name = $db_answer->fetch_assoc () ['topics_table'];

            $db_answer = DB::request ("
                SELECT `content_table`
                FROM `topics`
                WHERE `id` = '$topic_id'
                LIMIT 1 
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            } else if ($db_answer->num_rows != 1) {
                return $E_TOPIC_NOT_EXISTS->cmt ($topic_id, 
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            /* UNUSED */
            $content_table_name = $db_answer->fetch_assoc () ['content_table'];

            $db_answer = DB::one_exists ("
                SELECT COUNT(*)
                FROM `$topics_table_name`
                WHERE `topic_id` = '$topic_id'
                LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            } else if ($db_answer) {
                return $E_TOPIC_ALREADY_ADDED->cmt ($topic_id, 
                                                        __FILE__."::".__FUNCTION__, 
                                                        __LINE__);
            }

            $results_table_name = "group-".$group_id."-topic-".$topic_id."-results-table";

            $db_answer = DB::request ("
                CREATE 
				TABLE `$results_table_name`
				(
					`id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`user_id` int(11) NOT NULL,
					`task_index` int(11) NOT NULL,
                    `task_status` tinytext NOT NULL,
					FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
				) COMMENT = ''
				COLLATE 'utf8_unicode_ci'
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            $db_answer = DB::request ("
                INSERT
                INTO `$topics_table_name` (`topic_id`, `results_table`, `start_time`, `end_time`)
                VALUES ('$topic_id', '$results_table_name', UTC_TIMESTAMP(), UTC_TIMESTAMP())
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            return $S_REQ_DONE->cmt ("Successfully added",
                                        __FILE__."::".__FUNCTION__, 
                                        __LINE__);
        }

    }

?>