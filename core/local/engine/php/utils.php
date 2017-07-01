<?php

	class Utils {
		
		public static function clear_spaces ($string) {
			if (!is_string ($string)) { return null; }
			
			$result = "";
			for ($i = 0; $i < strlen ($string); $i ++) {
				$char = $string [$i];
				if (!($char == ' ' || $char == '\n' || $char == '\r')) {
					$result .= $char;
				}
			}
			
			return $result;
		}
		
		public static function random_string ($length) {
			$result = "";
			for ($i = 0; $i < $length; $i ++) {
				$result .= chr (ord ('a') + rand (0, 25));
			}
			
			return $result;
		}
		
	}

?>