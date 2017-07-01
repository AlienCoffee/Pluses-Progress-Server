<?php

	// Short pathes
	define (__root__, $_SERVER ['DOCUMENT_ROOT']);
	
	define (__local__, __root__."/core/local");
	define (__frames__, __local__."/frames");
	define (__engine__, __local__."/engine");
	define (__conf__, __local__."/conf");
	define (__logs__, __root__."/logs");
	
	// Info about host and site
	define (__domain__, $_SERVER ['SERVER_NAME']);
	define (__db_time_offset__, +3);
	define (__server_time_offset__, +3); // In hours from UTC
										  // +3 - Moscow
	define (__def_request__, "home");
	define (__def_token__, "empty-token-value");
	define (__db_profile__, "localhost");
	
	// Support defines
	define (br, "<br />\n");
	
	// Security
	define (__key1__, "some value");
	define (__key2__, "some value");
	define (__key3__, "some value");
	
?>