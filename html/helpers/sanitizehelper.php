<?php
	//Added class to help with escaping/sanitization
	class SanitizeHelper {

		/** Construct a new Auth helper */
		public function __construct($controller) {
			$this->controller = $controller;
		}
		
		public function escapeOutput($str)
		{
			return htmlspecialchars($str);
		}
		
		public function sanitizeInput($str)
		{
			return striptags($str);
		}
	}

?>
