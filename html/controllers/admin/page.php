<?php

namespace Admin;

class Page extends AdminController {

	public function index($f3) {
		$pages = $this->Model->Pages->fetchAll();
		
		$csrf = $this->Auth->generateCSRFToken($this->Auth->user('username'));
		$f3->set('csrf', $csrf);
		
		$f3->set('pages',$pages);
	}

	public function add($f3) {
		if($this->request->is('post')) {
			$pagename = strtolower(str_replace(" ","_",$this->request->data['title']));
			$csrf = $this->request->data['csrf'];
			$csrfValidatedUser = $this->Auth->validateCSRF($csrf);
			
			if($csrfValidatedUser === false) {
				\StatusMessage::add('CSRF possibility detected.','danger');
				return $f3->reroute('/admin/page');
			}
			$this->Model->Pages->create($pagename);
		
			\StatusMessage::add('Page created successfully','success');
			return $f3->reroute('/admin/page/edit/' . $pagename);
		}
	}

	public function edit($f3) {
		$pagename = $f3->get('PARAMS.3');
		
		
		if ($this->request->is('post')) {
			
			$pages = $this->Model->Pages;
			$pages->title = $pagename;
			$pages->content = $this->request->data['content'];
			$csrf = $this->request->data['csrf'];
			$csrfValidatedUser = $this->Auth->validateCSRF($csrf);
			
			if(!$csrfValidatedUser) {
				\StatusMessage::add('CSRF possibility detected.','danger');
				return $f3->reroute('/admin/page/edit/' . $pagename);
			}
			$pages->save();

			\StatusMessage::add('Page updated successfully','success');
			return $f3->reroute('/admin/page');
		}
		else {
			$csrf = $this->Auth->generateCSRFToken($this->Auth->user('username'));
			$f3->set('csrf', $csrf);
		}
	
		$pagetitle = ucfirst(str_replace("_"," ",str_ireplace(".html","",$pagename)));	
		$page = $this->Model->Pages->fetch($pagename);
		$f3->set('pagetitle',$pagetitle);
		$f3->set('page',$page);
	}

	public function delete($f3) {
		list($pagename, $csrf) = explode("/", $f3->get('PARAMS.3'));
		
		$csrfValidatedUser = $this->Auth->validateCSRF($csrf);
		
		if(!$csrfValidatedUser) {
			\StatusMessage::add('CSRF possibility detected.','danger');
			return $f3->reroute('/admin/page');
		}
		
		$this->Model->Pages->delete($pagename);	
		\StatusMessage::add('Page deleted successfully','success');
		return $f3->reroute('/admin/page');	
	}

}

?>
