<?php

	namespace Admin;

	class Blog extends AdminController {

		public function index($f3) {
			$posts = $this->Model->Posts->fetchAll();
			$blogs = $this->Model->map($posts,'user_id','Users');
			$blogs = $this->Model->map($posts,array('post_id','Post_Categories','category_id'),'Categories',false,$blogs);
			
			$csrf = $this->Auth->generateCSRFToken($this->Auth->user('username'));
			$f3->set('csrf', $csrf);
			
			$f3->set('blogs',$blogs);
		}

		public function delete($f3) {
			list($postid, $csrf) = explode("/",$f3->get('PARAMS.3'));
			
			$csrfValidatedUser = $this->Auth->validateCSRF($csrf);
			
			if(!$csrfValidatedUser) {
				\StatusMessage::add('CSRF possibility detected.','danger');
				return $f3->reroute('/admin/blog');
			}
			
			$post = $this->Model->Posts->fetchById($postid);
			$post->erase();

			//Remove from categories
			$cats = $this->Model->Post_Categories->fetchAll(array('post_id' => $postid));
			foreach($cats as $cat) {
				$cat->erase();
			}	

			\StatusMessage::add('Post deleted successfully','success');
			return $f3->reroute('/admin/blog');
		}

		public function add($f3) {
		
			$csrf = $this->Auth->generateCSRFToken($this->Auth->user('username'));
			$f3->set('csrf', $csrf);
			
			if($this->request->is('post')) {
				$post = $this->Model->Posts;
				extract($this->request->data);
				$post->title = $title;
				$post->content = $content;
				$post->summary = $summary;
				$post->user_id = $this->Auth->user('id');	
				$post->created = $post->modified = mydate();

				//Check for errors
				$errors = false;
				
				$csrfValidatedUser = $this->Auth->validateCSRF($csrf);
			
				if(!$csrfValidatedUser) {
					\StatusMessage::add('CSRF possibility detected.','danger');
					return $f3->reroute('/admin/blog/add');
				}
				
				if(empty($post->title)) {
					$errors = 'You did not specify a title';
				}

				if($errors) {
					\StatusMessage::add($errors,'danger');
				} else {
					//Determine whether to publish or draft
					if(!isset($Publish)) {
						$post->published = null;
					} else {
						$post->published = mydate($this->request->data['published']);
					}

					//Save post
					$post->save();
					$postid = $post->id;

					//Now assign categories
					$link = $this->Model->Post_Categories;
					if(!isset($categories)) { $categories = array(); }
					foreach($categories as $category) {
						$link->reset();
						$link->category_id = $category;
						$link->post_id = $postid;
						$link->save();
					}

					\StatusMessage::add('Post added successfully','success');
					return $f3->reroute('/admin/blog');
				}
			}
			$categories = $this->Model->Categories->fetchList();
			$f3->set('categories',$categories);
		}

		public function edit($f3) {
			$postid = $f3->get('PARAMS.3');
			$post = $this->Model->Posts->fetchById($postid);
			$blog = $this->Model->map($post,array('post_id','Post_Categories','category_id'),'Categories',false);
			
			if($this->request->is('get')) {
				$csrf = $this->Auth->generateCSRFToken($this->Auth->user('username'));
				$f3->set('csrf', $csrf);
			}
			
			if ($this->request->is('post')) {
				extract($this->request->data);
				//Replacing copyfrom with a better data extraction method to avoid parameter manipulation
				//$post->copyfrom('POST');
				$post->title = $title;
				$post->summary = $summary;
				$post->content = $content;
				$post->published = $published;
				
				$post->modified = mydate();
				$post->user_id = $this->Auth->user('id');
				
				$csrfValidatedUser = $this->Auth->validateCSRF($csrf);
			
				if(!$csrfValidatedUser) {
					\StatusMessage::add('CSRF possibility detected.','danger');
					return $f3->reroute('/admin/blog/edit/' . $postid);
				}
				
				//Determine whether to publish or draft
				if(!isset($Publish)) {
					$post->published = null;
				} else {
					$post->published = mydate($published);
				} 

				//Save changes
				$post->save();

				$link = $this->Model->Post_Categories;
				//Remove previous categories
				$old = $link->fetchAll(array('post_id' => $postid));
				foreach($old as $oldcategory) {
					$oldcategory->erase();
				}
				
				//Now assign new categories				
				if(!isset($categories)) { $categories = array(); }
				foreach($categories as $category) {
					$link->reset();
					$link->category_id = $category;
					$link->post_id = $postid;
					$link->save();
				}

				\StatusMessage::add('Post updated successfully','success');
				return $f3->reroute('/admin/blog');
			} 
			$_POST = $post->cast();		
			foreach($blog['Categories'] as $cat) {
				if(!$cat) continue;
				$_POST['categories'][] = $cat->id;
			}
	
			$categories = $this->Model->Categories->fetchList();
			$f3->set('categories',$categories);
			$f3->set('post',$post);
		}


	}

?>
