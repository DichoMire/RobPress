<?php
class User extends Controller {

	public function view($f3) {
		$userid = $f3->get('PARAMS.3');
		$u = $this->Model->Users->fetch($userid);

		$articles = $this->Model->Posts->fetchAll(array('user_id' => $userid));
		$comments = $this->Model->Comments->fetchAll(array('user_id' => $userid));

		$f3->set('u',$u);
		$f3->set('articles',$articles);
		$f3->set('comments',$comments);
	}

	public function add($f3) {
		if($this->request->is('post')) {
			extract($this->request->data);
			$check = $this->Model->Users->fetch(array('username' => $username));
			if (!empty($check)) {
				StatusMessage::add('User already exists','danger');
			} else if(strlen($password) < 8 || strlen($password) > 64) {
				StatusMessage::add('Password must be between 8 and 64 characters', 'danger');
			}
			else if(!preg_match('/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD', $email)) {
				StatusMessage::add('Invalid email', 'danger');
			}
			else if($password != $password2) {
				StatusMessage::add('Passwords must match','danger');
			} else {
				$user = $this->Model->Users;
				$user->copyfrom('POST');
				$user->created = mydate();
				$user->bio = '';
				$user->level = 1;
				$user->setPassword($password);
				if(empty($displayname)) {
					$user->displayname = $user->username;
				}

				//I don't know why this was duplicated.
				//Set the users password
				//$user->setPassword($user->password);

				$user->save();	
				StatusMessage::add('Registration complete','success');
				return $f3->reroute('/user/login');
			}
		}
	}

	public function login($f3) {
		/** YOU MAY NOT CHANGE THIS FUNCTION - Make any changes in Auth->checkLogin, Auth->login and afterLogin() (AuthHelper.php) */
		if ($this->request->is('post')) {

			//Check for debug mode
			$settings = $this->Model->Settings;
			$debug = $settings->getSetting('debug');

			//Either allow log in with checked and approved login, or debug mode login
			list($username,$password) = array($this->request->data['username'],$this->request->data['password']);
			if (
				($this->Auth->checkLogin($username,$password,$this->request,$debug) && ($this->Auth->login($username,$password))) ||
				($debug && $this->Auth->debugLogin($username))) {

					$this->afterLogin($f3);

			} else {
				StatusMessage::add('Invalid username or password','danger');
			}
		}		
	}

	/* Handle after logging in */
	private function afterLogin($f3) {
				StatusMessage::add('Logged in successfully','success');

				//Redirect to where they came from
				if(isset($_GET['from'])) {
					$f3->reroute($_GET['from']);
				} else {
					$f3->reroute('/');	
				}
	}

	public function logout($f3) {
		$this->Auth->logout();
		StatusMessage::add('Logged out successfully','success');
		$f3->reroute('/');	
	}


	public function profile($f3) {	
		$id = $this->Auth->user('id');
		//Replacing extract with data exfiltration that takes only the data we want.
		//extract($this->request->data);
		/*
		$displayname = $_POST['displayname'];
		$newpassword = $_POST['password'];
		$bio = $_POST['bio'];
		$fileName = $_POST['avatar'];
		$reset = $_POST['reset'];
		*/
		
		$csrf = $this->Auth->generateCSRFToken($this->Auth->user('username'));
		$f3->set('csrf', $csrf);
		
		extract($this->request->data);
		$u = $this->Model->Users->fetch($id);
		$oldpass = $u->password;
		if($this->request->is('post')) {
			//Inputting the data
			//$u->copyfrom('POST');
			if(empty($u->password)) { $u->password = $oldpass; }
	
			if((strlen($password) > 0 && strlen($password) < 8) || strlen($password) > 64) {
				StatusMessage::add('Password must be between 8 and 64 characters', 'danger');
			}
			else {
				$csrfValidatedUser = $this->Auth->validateCSRF($csrf);
			
				if(!$csrfValidatedUser) {
					StatusMessage::add('CSRF possibility detected.','danger');
					return $f3->reroute('/user/profile');
				}
				
				//Handle avatar upload
				if(isset($_FILES['avatar']) && isset($_FILES['avatar']['tmp_name']) && !empty($_FILES['avatar']['tmp_name'])) {
					$url = File::Upload($_FILES['avatar']);
					$u->avatar = $url;
				} else if(isset($reset)) {
					$u->avatar = '';
				}

				$u->displayname = $displayname;
				//If password is changed, only then modify it.
				if(strlen($password) != 0) {
					$u->setPassword = $password;
				}
				$u->bio = $bio;
				$u->save();
				\StatusMessage::add('Profile updated successfully','success');
				return $f3->reroute('/user/profile');
			}
		}			
		$_POST = $u->cast();
		$f3->set('u',$u);
	}

	public function promote($f3) {
		$id = $this->Auth->user('id');
		$u = $this->Model->Users->fetch($id);
		$u->level = 2;
		$u->save();
		return $f3->reroute('/');
	}

}
?>
