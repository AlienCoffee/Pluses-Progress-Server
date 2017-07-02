<?php

	class Ping {
		
		public static function call () {
			$answer = Array (
				'type' => "success",
				'code' => "",
				'message' => "pong"
			);
			
			echo (json_encode ($answer));
		}
		
	}

?>