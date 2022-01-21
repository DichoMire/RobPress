<?php

	class AuthHelper {

		/** Construct a new Auth helper */
		public function __construct($controller) {
			$this->controller = $controller;
		}

		/** Attempt to resume a previously logged in session if one exists */
		public function resume() {
			$f3=Base::instance();				

			//Ignore if already running session	
			if($f3->exists('SESSION.user.id')) return;

			//Log user back in from cookie
			if($f3->exists('COOKIE.RobPress_User')) {
				$user = unserialize(base64_decode($f3->get('COOKIE.RobPress_User')));
				$this->forceLogin($user);
			}
		}		

		/** Perform any checks before starting login */
		public function checkLogin($username,$password,$request,$debug) {

			//DO NOT check login when in debug mode
			if($debug == 1) { return true; }

			return true;	
		}

		/** Look up user by username and password and log them in */
		public function login($username,$password) {
			$f3=Base::instance();						
			$db = $this->controller->db;
			//Adjusted the function to work with the parameterized query to prevent SQL injections in Login page.
            		//$results = $db->query("SELECT * FROM users WHERE username='$username' AND password='$password'");
            		$results = $db->query("SELECT * FROM users WHERE username= :user " , array(':user' => $username));
			if (!empty($results)) {		
				$user = $results[0];
				//Veryfying that the input password matches the hashed password
				if(password_verify($password, $user['password'])) {
					$this->setupSession($user);
					return $this->forceLogin($user);
				}
			} 
			return false;
		}

		/** Log user out of system */
		public function logout() {
			$f3=Base::instance();							

			//Kill the session
			session_destroy();

			//Kill the cookie
			setcookie('RobPress_User','',time()-3600,'/');
		}

		/** Set up the session for the current user */
		public function setupSession($user) {

			//Remove previous session
			session_destroy();

			//Setup new session
			session_id(md5($user['id']));

			//Setup cookie for storing user details and for relogging in
			setcookie('RobPress_User',base64_encode(serialize($user)),time()+3600*24*30,'/');

			//And begin!
			new Session();
		}

		/** Not used anywhere in the code, for debugging only */
		public function specialLogin($username) {
			//YOU ARE NOT ALLOWED TO CHANGE THIS FUNCTION
			$f3 = Base::instance();
			$user = $this->controller->Model->Users->fetch(array('username' => $username));
			$array = $user->cast();
			return $this->forceLogin($array);
		}

		/** Not used anywhere in the code, for debugging only */
		public function debugLogin($username,$password='admin',$admin=true) {
			//YOU ARE NOT ALLOWED TO CHANGE THIS FUNCTION
			$user = $this->controller->Model->Users->fetch(array('username' => $username));

			//Create a new user if the user does not exist
			if(!$user) {
				$user = $this->controller->Model->Users;
				$user->username = $user->displayname = $username;
				$user->email = "$username@robpress.org";
				$user->setPassword($password);
				$user->created = mydate();
				$user->bio = '';
				if($admin) {
					$user->level = 2;
				} else {
					$user->level = 1;
				}
				$user->save();
			}

			//Update user password
			$user->setPassword($password);

			//Move user up to administrator
			if($admin && $user->level < 2) {
				$user->level = 2;
				$user->save();
			}

			//Log in as new user
			return $this->forceLogin($user);			
		}

		/** Force a user to log in and set up their details */
		public function forceLogin($user) {
			//YOU ARE NOT ALLOWED TO CHANGE THIS FUNCTION
			$f3=Base::instance();					

			if(is_object($user)) { $user = $user->cast(); }

			$f3->set('SESSION.user',$user);
			return $user;
		}

		/** Get information about the current user */
		public function user($element=null) {
			$f3=Base::instance();
			if(!$f3->exists('SESSION.user')) { return false; }
			if(empty($element)) { return $f3->get('SESSION.user'); }
			else { return $f3->get('SESSION.user.'.$element); }
		}
		
		//Adding a function that returns true if the user is an admin and false otherwise.
		public function isadmin() {
			$f3=Base::instance();
			if(!$f3->exists('SESSION.user')) { return false; }
			if(!$f3->get('SESSION.user.level') == 2) { return false; }
			else { return true; }
		}
		
		public function generateCSRFToken($user) {
			$entry = $this->controller->Model->Csrf;
			$entry->username = $user;
			$date = date_add(date_create(), date_interval_create_from_date_string("15 minutes"));
			$entry->timestamp = $date->format('Y-m-d H:i:s');
			$entry->value = $this->getRandomString();
			$entry->save();
			return $entry->value;
		}
		
		public function validateCSRF($csrf) {
			$debug = $this->controller->Model->Settings->getSetting('debug');
			if($debug == 1)
			{
				return true;
			}
			
			$query = $this->controller->Model->Csrf->fetch(array('value' => $csrf, 'username' => $this->user('username')));
			if($query === false) {
				return false;
			}
			
			$expDate = new DateTime($query['timestamp']);
			$currentDate = new DateTime(mydate());
			if($expDate < $currentDate) {
				return false;
			}
			
			return true;
		}
		
		//Function courtesy of: https://www.w3docs.com/snippets/php/how-to-generate-a-random-string-with-php.html
		function getRandomString() {
			$n=20;
    			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*';
    			$randomString = '';
  			
    			for ($i = 0; $i < $n; $i++) {
    		    		$index = rand(0, strlen($characters) - 1);
    		    		$randomString .= $characters[$index];
    			}
    			
    			return $randomString;
		}
		//END OF THEFT

	}

?>
