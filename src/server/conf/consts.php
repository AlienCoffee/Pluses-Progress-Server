<?php

    // Sym-links
    define (__root__, $_SERVER ['DOCUMENT_ROOT']);
    define (__src__, __root__."/src");
    define (__server__, __src__."/server");
    define (__conf__, __server__."/conf");
    define (__php__, __server__."/php");

    // Server properties
    define (__domain__, $_SERVER ['SERVER_NAME']);
    define (__def_token__, "def");
    define (__def_dest__, "home");

    // Database
    define (__db_profile__, "local");

    // Regular expressions
    define (__regexp_hex__, "/^[a-f0-9]+$/i"); // At least one digit or letter must be

    // Abbreviations
    define (br, "<br />");

?>