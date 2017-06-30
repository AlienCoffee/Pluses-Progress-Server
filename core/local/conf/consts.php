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
	define (__time_offset__, +3); // In hours from UTC
	                               // +3 - Moscow
	define (__def_request__, "home");
	
?>