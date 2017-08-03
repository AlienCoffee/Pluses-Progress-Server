<?php

    final class TopicsManip {

        public static function create_topic ($name) {
            global $_user,
                    $E_EXP_NOT_A_STRING,
                    $S_REQ_DONE;

            if (!is_string ($name) || strlen ($name) < 6) {
                return $E_EXP_NOT_A_STRING->cmt ("name (length must be at least 6)",
                                                    __FILE__."::".__FUNCTION__,
                                                    __LINE__);
            }

            $name = trim ($name);
			$name = htmlentities ($name, ENT_HTML5 
											| ENT_QUOTES);
            $author = $_user ['id'];

            $fake_content_table = random_string (32);
            $db_answer = DB::request ("
                INSERT
                INTO `topics` (`name`, `content_table`, `author`)
                VALUES ('$name', '$fake_content_table', '$author')
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            $db_answer = DB::request ("
                SELECT `id`
                FROM `topics`
                WHERE `name` = '$name'
                    AND `content_table` = '$fake_content_table'
                    AND `author` = '$author'
                LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            $topic_id = $db_answer->fetch_assoc () ['id'];
            $content_table_name = "topic-".$topic_id."-content-table";

            $db_answer = DB::request ("
                CREATE 
				TABLE `$content_table_name`
				(
					`id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`index` int(11) NOT NULL,
					`name` tinytext NOT NULL,
					`rating` bit(1) NOT NULL
				) COMMENT = ''
				COLLATE 'utf8_unicode_ci'
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            $db_answer = DB::request ("
                UPDATE `topics`
                SET `content_table` = '$content_table_name'
                WHERE `id` = '$topic_id'
                LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            return $S_REQ_DONE->cmt ("Successfully created", 
                                        __FILE__."::".__FUNCTION__, 
                                        __LINE__);
        }

        public static function get_topic_data ($topic_id) {
            global $E_TOPIC_NOT_EXISTS, 
                    $S_REQ_DONE;

            $db_answer = DB::request ("
                SELECT *
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

            $topic_data = $db_answer->fetch_assoc ();
            $content_table_name = $topic_data ['content_table'];

            $db_answer = DB::request ("
                SELECT COUNT(*)
                FROM `$content_table_name`
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            $total_tasks = $db_answer->fetch_assoc () ['COUNT(*)'];

            $db_answer = DB::request ("
                SELECT COUNT(*)
                FROM `$content_table_name`
                WHERE `rating` = TRUE
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            $rating_tasks = $db_answer->fetch_assoc () ['COUNT(*)'];

            $topic_data ['tasks'] = Array (
                'total' => $total_tasks,
                'rating' => $rating_tasks
            );

            return $S_REQ_DONE->cmt ($topic_data, 
                                        __FILE__."::".__FUNCTION__, 
                                        __LINE__);
        }

        public static function add_task ($topic_id, $name, $index = -1, $rating = 1) {
            global $E_EXP_NOT_A_STRING,
                    $E_EXP_NOT_POSITIVE,
                    $E_TOPIC_NOT_EXISTS,
                    $E_TASK_ALREADY_ADDED,
                    $S_REQ_DONE;

            if (!is_string ($name) || strlen ($name) < 1) {
                return $E_EXP_NOT_A_STRING->cmt ("name (length must be at least 1)",
                                                    __FILE__."::".__FUNCTION__,
                                                    __LINE__);
            }

            $name = trim ($name);
			$name = htmlentities ($name, ENT_HTML5 
											| ENT_QUOTES);

            if ($rating) { $rating = 1; }
            else         { $rating = 0; }

            if (!(is_numeric ($index) && ($index >= 0 || $index == -1))) {
                return $E_EXP_NOT_POSITIVE->cmt ("index: $index",
                                                    __FILE__."::".__FUNCTION__,
                                                    __LINE__);
            }
            
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

            $content_table_name = $db_answer->fetch_assoc () ['content_table'];

            $db_answer = DB::one_exists ("
                SELECT COUNT(*)
                FROM `$content_table_name`
                WHERE `name` = '$name'
                LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            } else if ($db_answer) {
                return $E_TASK_ALREADY_ADDED->cmt ($name,
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            // Move indexes in right order
            if ($index == -1) {
                // Push to the end
                $db_answer = DB::request ("
                    SELECT MAX(`index`)
                    FROM `$content_table_name`
                ");
                if ($db_answer instanceof Answer) {
                    return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                    __LINE__);
                }
                
                $max = $db_answer->fetch_assoc () ['MAX(`index`)'];
                if (!$max) { $index = 0; }
				else       { $index = intval ($max) + 1; }
            } else {
                // Push to the center and move other up
                $db_answer = DB::request ("
                    UPDATE `$content_table_name`
                    SET `index` = `index` + 1
					WHERE `index` >= $index
                ");
                if ($db_answer instanceof Answer) {
                    return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                    __LINE__);
                }
            }

            $db_answer = DB::request ("
                INSERT
                INTO `$content_table_name` (`name`, `index`, `rating`)
                VALUES ('$name', '$index', $rating)
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }

            return $S_REQ_DONE->cmt ("Successfully added",
                                        __FILE__."::".__FUNCTION__, 
                                        __LINE__);
        }

        public static function register_attempt ($user_id, $group_id, $topic_id, $name, $result) {
            global $E_EXP_NOT_BOOLEAN,
                    $E_USER_NOT_EXISTS,
                    $E_GROUP_NOT_EXISTS,
                    $E_USER_NOT_JOINED,
                    $E_TOPIC_NOT_EXISTS,
                    $E_TASK_NOT_EXISTS,
                    $E_CONTEST_TIME_LEFT,
                    $E_TOPIC_NOT_ADDED,
                    $E_SOLUT_FROM_CHECKER,
                    $S_REQ_DONE;

            if ($result != "true" && $result != "false") {
                return $E_EXP_NOT_BOOLEAN->cmt ("result",
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            $db_answer = DB::request ("
                SELECT `rights`
                FROM `users`
                WHERE `id` = '$user_id'
                LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            } else if ($db_answer->num_rows != 1) {
                return $E_USER_NOT_EXISTS->cmt ($user_id, 
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            $user_rights = $db_answer->fetch_assoc ();
            if (check_for_rights ($user_rights, "****m")) {
                return $E_SOLUT_FROM_CHECKER->cmt ($user_id, 
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

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

            $content_table_name = $db_answer->fetch_assoc () ['content_table'];
            ///////////////////////////////////////////////////////////////////

            if (!is_string ($name) || strlen ($name) < 1) {
                return $E_EXP_NOT_A_STRING->cmt ("name (length must be at least 1)",
                                                    __FILE__."::".__FUNCTION__,
                                                    __LINE__);
            }

            $name = trim ($name);
			$name = htmlentities ($name, ENT_HTML5 
											| ENT_QUOTES);

            $db_answer = DB::request ("
                SELECT `index`
                FROM `$content_table_name`
                WHERE `name` = '$name'
                LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            } else if ($db_answer->num_rows != 1) {
                return $E_TASK_NOT_EXISTS->cmt ($name, 
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            $task_index = $db_answer->fetch_assoc () ['index'];
            ///////////////////////////////////////////////////

            $db_answer = DB::request ("
                SELECT `topics_table`,
                        `list_table`
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

            $group_data = $db_answer->fetch_assoc ();
            $topic_table_name = $group_data ['topics_table'];
            $list_table_name = $group_data ['list_table'];
            ////////////////////////////////////////////////////////////////

            $db_answer = DB::one_exists ("
                SELECT COUNT(*)
                FROM `$list_table_name`
                WHERE `user_id` = '$user_id'
                    AND `join_time` = `leave_time`
                LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            } else if (!$db_answer) {
                return $E_USER_NOT_JOINED->cmt ($user_id, 
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            $db_answer = DB::request ("
                SELECT `results_table`,
                        `start_time`,
                        `end_time`
                FROM `$topic_table_name`
                WHERE `topic_id` = '$topic_id'
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            } else if ($db_answer->num_rows != 1) {
                return $E_TOPIC_NOT_ADDED->cmt ($topic_id, 
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            $results_data = $db_answer->fetch_assoc ();
            $date = new DateTime ($results_data ['end_time']);
            if ($results_data ['start_time'] != $results_data ['end_time']
                    && time () - __timezone__ * 60 * 60 > $date->getTimestamp ()) {
                /* UNCHECKED */
                return $E_CONTEST_TIME_LEFT->cmt ("",
                                                    __FILE__."::".__FUNCTION__, 
                                                    __LINE__);
            }

            $results_table_name = $results_data ['results_table'];
            $db_answer = DB::one_exists ("
                SELECT COUNT(*)
                FROM `$results_table_name`
                WHERE `task_index` = '$task_index'
                LIMIT 1
            ");
            if ($db_answer instanceof Answer) {
                return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                __LINE__);
            }
            
            if ($db_answer) {
                $db_answer = DB::request ("
                    UPDATE `$results_table_name`
                    SET `task_status` = '$result'
                    WHERE `task_index` = '$task_index'
                    LIMIT 1
                ");

                if ($db_answer instanceof Answer) {
                    return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                    __LINE__);
                }
            } else {
                $db_answer = DB::request ("
                    INSERT
                    INTO `$results_table_name` (`user_id`, `task_index`, `task_status`)
                    VALUES ('$user_id', '$task_index', '$result')
                ");

                if ($db_answer instanceof Answer) {
                    return $db_answer->addTrace (__FILE__."::".__FUNCTION__, 
                                                    __LINE__);
                }
            }

            return $S_REQ_DONE->cmt ("Successfully registered", 
                                        __FILE__."::".__FUNCTION__, 
                                        __LINE__);
        }

    }

?>