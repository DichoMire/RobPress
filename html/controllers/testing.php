<?php
class Testing extends Controller {
	public function index($f3)
	{
	$string = $this->Auth->generateCSRFToken('admin');
	var_dump($string);
	die();
	}
}
?>
