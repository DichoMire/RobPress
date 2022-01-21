<?php

class Contact extends Controller {

	public function index($f3) {
		if($this->request->is('post')) {
			extract($this->request->data);
			//Hard coding in the to field so that the user cannot use parameter manipulation.
			$to = "root@localhost";
			$from = "From: $from";

			mail($to,$subject,$message,$from);

			StatusMessage::add('Thank you for contacting us');
			return $f3->reroute('/');
		}	
	}

}

?>
