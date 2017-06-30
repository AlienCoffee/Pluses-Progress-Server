<?php

	class UsersManip {
		
		public static function identify ($token) {
			$user = Array (
				'id' => 0,
				'login' => "guest",
				'rights' => "v"
			);
			
			return $user;
		}
		
		public static function has_access ($user, $need) {
			return false;
		}
		
		public static function register ($login, $hpass, $email) {
			
		}
		
		public static function remove ($id) {
			
		}
	
		public static function get_user_data ($id) {
			
		}
	
	}

?>