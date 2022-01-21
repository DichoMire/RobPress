<?php

class File {

	public static function Upload($array,$local=false) {
		$f3 = Base::instance();
		extract($array);
		$directory = getcwd() . '/uploads';
		$destination = $directory . '/' . $name;
		$webdest = '/uploads/' . $name;
		
		$fileType = pathinfo($name, PATHINFO_EXTENSION);
		$fileMime = mime_content_type($tmp_name);

		//Local files get moved
		if($local) {
			if (copy($tmp_name,$destination)) {
				chmod($destination,0666);
				return $webdest;
			} else {
				return false;
			}
		//POSTed files are done with move_uploaded_file
		} else {
		
			$whitelistType = array('jpg' ,'jpeg', 'png', 'gif');
			$whitelistMime = array('image/jpeg', 'image/png', 'image/gif');
			
			
			if(in_array($fileType,  $whitelistType)) {
				if(in_array($fileMime, $whitelistMime)) {
					if (move_uploaded_file($tmp_name,$destination)) {
						chmod($destination,0666);
						return $webdest;
					} else {
						StatusMessage::add('Failure uploading the avatar. Please contact 911.', 'danger');
						return false;
					}
				}
				else {
					StatusMessage::add('Accepted files: .jpg, .jpeg, .png, .gif', 'danger');
					return false;
				}
			}
			else {
				StatusMessage::add('Accepted files: .jpg, .jpeg, .png, .gif', 'danger');
				return false;
			}
			
		}
	}

}

?>
