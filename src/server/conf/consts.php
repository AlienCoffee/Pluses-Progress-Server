<?php

    // Sym-links
    define (__root__, $_SERVER ['DOCUMENT_ROOT']);
    define (__src__, __root__."/src");
    define (__server__, __src__."/server");
    define (__conf__, __server__."/conf");
    define (__php__, __server__."/php");
    define (__frames__, __server__."/frames");

    // Server properties
    define (__domain__, $_SERVER ['SERVER_NAME']);
    define (__def_token__, "def");
    define (__def_dest__, "home");

    define (__key_salt1__, "some value");

    // Database
    define (__db_profile__, "local");

    // Regular expressions
    define (__regexp_hex__, "/^[a-f0-9]+$/i"); // At least one digit or letter must be
    define (__regexp_path__, "/^(\w+\.)*\w+$/i"); // Example `api.users.show` or `home`
    define (__regexp_phone__, "/^(|8|\+7|)[0-9]{10}$/i"); // Example +71234567890

    // Abbreviations
    define (br, "<br />");

?>