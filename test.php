<?php

    $post = $_POST;
    $get  = $_GET;

    $answer = Array (
        'code' => 200,
        'message' => "text answer",
        'post' => $post,
        'get' => $get,
        'method' => $_SERVER ['REQUEST_METHOD'],
        'uri' => $_SERVER ['REQUEST_URI']
    );

    echo (json_encode ($answer));

?>