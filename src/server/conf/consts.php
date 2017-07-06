<?php

    // Sym-links
    define (__root__, $_SERVER ['DOCUMENT_ROOT']);
    define (__src__, __root__."/src");
    define (__server__, __src__."/server");
    define (__conf__, __server__."/conf");
    define (__php__, __server__."/php");

    // Database
    define (__db_profile__, "local");

    // Abbreviations
    define (br, "<br />");

?>