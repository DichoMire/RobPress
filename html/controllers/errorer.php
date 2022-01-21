<?php

class Errorer extends Controller {

	public function __construct() {
		parent::__construct();
	}

	public function errorer($f3) {
		$debug = $this->Model->Settings->getSetting('debug');
    		$userAdm = $this->Auth->user('level') - 1;
    		if($debug || $userAdm)
    		{
    			$this->template = 500;	
    		}
    		else
    		{
    			$this->template = 'wrong';
    		}
	}	

}

?>
