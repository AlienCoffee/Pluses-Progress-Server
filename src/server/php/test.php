<?php

    class Test {

        public static function ping () {
            echo ("PONG".br);
        }

        public static function info () {
            global $_user;

            $post = $_POST;
            $get  = $_GET;

            $answer = Array (
                'code' => 200,
                'message' => "text answer",
                'post' => $post,
                'get' => $get,
                'method' => $_SERVER ['REQUEST_METHOD'],
                'uri' => $_SERVER ['REQUEST_URI'],
                'port' => $_SERVER ['SERVER_PORT'],
                'time' => time (),
                'user' => $_user
            );

            return (new Answer (0, "Answer", __FUNCTION__))
                    ->cmt ($answer, __FUNCTION__, 
                            __LINE__);
        }

    }

?>