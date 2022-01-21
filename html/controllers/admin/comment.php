<?php

namespace Admin;

class Comment extends AdminController {

	public function index($f3) {
		//Unmoderated comments
		$un = $this->Model->Comments->fetchAll(array('moderated' => 0));
		$unmoderated = $this->Model->map($un,'user_id','Users');
		$unmoderated = $this->Model->map($un,'blog_id','Posts',true,$unmoderated);

		//Moderated comments
		$mod = $this->Model->Comments->fetchAll(array('moderated' => 1));
		$moderated = $this->Model->map($mod ,'user_id','Users');
		$moderated = $this->Model->map($mod ,'blog_id','Posts',true,$moderated);

		$csrf = $this->Auth->generateCSRFToken($this->Auth->user('username'));
		$f3->set('csrf', $csrf);

		$f3->set('unmoderated',$unmoderated);
		$f3->set('moderated',$moderated);
	}

	public function edit($f3) {
		$id = $f3->get('PARAMS.3');
		$comment = $this->Model->Comments->fetch($id);
		if($this->request->is('get')) {
			$csrf = $this->Auth->generateCSRFToken($this->Auth->user('username'));
			$f3->set('csrf', $csrf);
		}
		if($this->request->is('post')) {
			$csrf = $this->request->data['csrf'];
			$csrfValidatedUser = $this->Auth->validateCSRF($csrf);
			
			if(!$csrfValidatedUser) {
				\StatusMessage::add('CSRF possibility detected.','danger');
				return $f3->reroute('/admin/comment/edit/' .$id);
			}
			$comment->copyfrom('POST');
			$comment->save();
			\StatusMessage::add('Comment updated successfully','success');
			return $f3->reroute('/admin/comment');
		} 
		$_POST = $comment;
		$f3->set('comment',$comment);
	}

}

?>
