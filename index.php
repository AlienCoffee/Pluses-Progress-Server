<?php

	// Index page
    // All requests are redirected here

    // Loading server constants file
    $_consts_file = $_SERVER ['DOCUMENT_ROOT']."/src/server/conf/consts.php";
    if (!file_exists ($_consts_file)) {
        echo ("[ERROR] Failed to load configs file (consts.php) due to: not found");
        exit (0);
    }
    require_once $_consts_file;

    $_errors_file = __conf__."/errors.php";
    if (!file_exists ($_errors_file)) {
        echo ("[ERROR] Failed to load configs file (errors.php) due to: not found");
        exit (0);
    }
    require_once $_errors_file;

    // Loading necessary files for work
    

?>