<?php

namespace Admin;

class User extends AdminController {

	public function index($f3) {
		$users = $this->Model->Users->fetchAll();
		
		$csrf = $this->Auth->generateCSRFToken($this->Auth->user('username'));
		$f3->set('csrf', $csrf);
		
		$f3->set('users',$users);
	}

	public function export($f3) {
		$users = $this->Model->Users->fetchAll();
		$fp = fopen('export.csv', 'w');
		foreach($users as $user) {			
			$fields = [$user->id,$user->username,$user->displayname,$user->email,$user->password,$user->level,$user->created];
			fputcsv($fp,$fields);
		}
		fclose($fp);
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=file.csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		echo file_get_contents('export.csv');
		exit();
	}

	public function edit($f3) {	
		$id = $f3->get('PARAMS.3');
		if($this->request->is('get')) {
			$csrf = $this->Auth->generateCSRFToken($this->Auth->user('username'));
			$f3->set('csrf', $csrf);
		}
		$u = $this->Model->Users->fetch($id);
		if($this->request->is('post')) {
			//Replacing copy from with an extract and manuel setting of data to avoid parameter manipulation.
			//$u->copyfrom('POST');
			$csrf = $this->request->data['csrf'];
			$csrfValidatedUser = $this->Auth->validateCSRF($csrf);
			
			if(!$csrfValidatedUser) {
				\StatusMessage::add('CSRF possibility detected.','danger');
				return $f3->reroute('/admin/user/edit/' . $id);
			}
			extract($this->request->data);
			$check = $this->Model->Users->fetch(array('username' => $username));
			
			//Adding an extra check to enable a user to keep his old username
			if(!empty($check) && $u->username != $username) {
				\StatusMessage::add('Username already in use','danger');
			} else if((strlen($password > 0) && strlen($password) < 8 )|| strlen($password) > 64 && $valid) {
				\StatusMessage::add('Password must be between 8 and 64 characters', 'danger');
			}	
			else {
				$u->username = $username;
				$u->displayname = $displayname;
				//If password is changed, only then modify it.
				if(strlen($password) != 0) {
					$u->setPassword = $password;
				}
				$u->level = $level;
				$u->bio = $bio;
				//TODO: Implement checks.
				$u->avatar = $avatar;
				//$u->setPassword($this->request->data['password']);
				$u->save();
				\StatusMessage::add('User updated successfully','success');
				return $f3->reroute('/admin/user');
			}
			
		}			
		$_POST = $u->cast();
		$f3->set('u',$u);
	}

	public function delete($f3) {
		list($id, $csrf) = explode("/", $f3->get('PARAMS.3'));
		$u = $this->Model->Users->fetch($id);
		
		$csrfValidatedUser = $this->Auth->validateCSRF($csrf);
			
		if(!$csrfValidatedUser) {
			\StatusMessage::add('CSRF possibility detected.','danger');
			return $f3->reroute('/admin/user');
		}

		if($id == $this->Auth->user('id')) {
			\StatusMessage::add('You cannot remove yourself','danger');
			return $f3->reroute('/admin/user');
		}

		//Remove all posts and comments
		$posts = $this->Model->Posts->fetchAll(array('user_id' => $id));
		foreach($posts as $post) {
			$post_categories = $this->Model->Post_Categories->fetchAll(array('post_id' => $post->id));
			foreach($post_categories as $cat) {
				$cat->erase();
			}
			$post->erase();
		}
		$comments = $this->Model->Comments->fetchAll(array('user_id' => $id));
		foreach($comments as $comment) {
			$comment->erase();
		}
		$u->erase();

		\StatusMessage::add('User has been removed','success');
		return $f3->reroute('/admin/user');
	}


}

?>
