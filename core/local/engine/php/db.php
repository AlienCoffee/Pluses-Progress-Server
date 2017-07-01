<?php

	// Constants loaded from the calling script
	
	$_db = null;
	if (defined ("__db_profile__") && file_exists (__conf__."/db_profiles.ini")) {
		$profiles = @parse_ini_file (__conf__."/db_profiles.ini", true);
		if ($profiles !== false && isset ($profiles [__db_profile__])) {
			$prof = $profiles [__db_profile__];
			$_db = @new mysqli ($prof ['host'],
								 $prof ['login'],
								 $prof ['password'],
								 $prof ['database']);
			if (!$_db) {
				echo ("[DEBUG] Failed to connect to database".br);
				exit (0);
			}
		} else {
			echo ("[DEBUG] Failed to parse database profiles".br);
			exit (0);
		}
	} else {
		echo ("[DEBUG] Failed to read database properties".br);
		exit (0);
	}

?>