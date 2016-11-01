<?php 
	session_start();
        error_reporting( error_reporting() & ~E_NOTICE );
        set_time_limit(0);
        mb_internal_encoding('UTF-8');

	$logged_in = checkDropZoneLoggedIn($_REQUEST['ses'], $_REQUEST['usr']);

	if ( $_REQUEST['go'] == 'go' ) {

		processFileUpload($logged_in);

	} else {

		processFromDisplay($logged_in);

	}


///////////////////////////////////////////////////////////////////////////////////////////////////////////////

function processFromDisplay($logged_in) {

	$head = buildHeaderHtml($logged_in);
	$body = buildBodyHtml($logged_in);
        $foot = buildFooterHtml();

	echo $head;
        echo $body;
        echo $foot;

}

function processFileUpload($logged_in) {

	if ( $logged_in === true ) {

		// create temp table
		$table_name = createDropZoneImageTempTable($_REQUEST['tbl']);

		// get file count
		if ( isset( $_FILES['file']['name'] ) ) {
			$count = count( $_FILES['file']['name'] );
		} else {
			$count = 0;
		}

		// loop files
		for ( $x=0; $x<$count; $x++) {	

			// get file info
			$name 		= $_FILES['file']['name'][$x];
			$type  		= $_FILES['file']['type'][$x];
			$tmp_name 	= $_FILES['file']['tmp_name'][$x];
			$error 		= $_FILES['file']['error'][$x];
			$size 		= $_FILES['file']['size'][$x];

			// insert into database
			insertDropZoneFile($table_name, $name, $type, $tmp_name, $error, $size);
		}
	} else {
		return;
	}

}

function getUploadResult($num) {

	$phpFileUploadErrors = array(
                0 => 'There is no error, the file uploaded with success',
                1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                3 => 'The uploaded file was only partially uploaded',
                4 => 'No file was uploaded',
                6 => 'Missing a temporary folder',
                7 => 'Failed to write file to disk.',
                8 => 'A PHP extension stopped the file upload.'
        );

	return $phpFileUploadErrors[$num];
}

function buildHeaderHtml($logged_in) {

	$max = getFileMax();
	$accepted_files = '".png,.jpg,.gif,.bmp,.jpeg"';

	$result = "
	<!DOCTYPE html>
	<html lang=\"en\">
	<head>
        	<meta charset=\"utf-8\">
        	<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
        	<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
        	<title>nuBuilder Dropzone</title>
        	<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js\"></script>
	";

	if ( $logged_in === true ) {
		$result .= "
        	<script src=\"dropzone.js\"></script>
        	<link rel=\"stylesheet\" href=\"dropzone.css\">
        	<script>
        	$(function(){
                	Dropzone.options.myAwesomeDropzone = {
                        	maxFilesize: $max,
                        	dictResponseError: 'Server not Configured',
                        	acceptedFiles: $accepted_files,
                        	init:function(){
                                	var self = this;
                                	self.options.parallelUploads = 999;
                                	self.options.autoProcessQueue = true;
                                	self.options.uploadMultiple = true;
                                	self.options.addRemoveLinks = false;
                                	self.options.createImageThumbnails = true;
                                	self.on(\"addedfile\", function (file) {
                                	});
                                	self.on(\"sending\", function (file) {
                                	});
                                	self.on(\"totaluploadprogress\", function (progress) {
                                	});
                                	self.on(\"queuecomplete\", function (progress) {
                                        	//self.disable();
                               		});
                                	self.on(\"removedfile\", function (file) {
                                	});
                        	}
                	};
        	})
        	</script>
		";
	}

	$result .= "</head>";

	return $result;
}

function buildBodyHtml($logged_in) {

	if ( $logged_in === true ) {
                $body = buildFormHtml();
        } else {
                $body = buildNotLoggedInHtml();
	}
	
	return $body;
}

function buildNotLoggedInHtml() {
	
	$result = " 
        <body>
	<p>You are not logged into nuBuilder</p>
	";
	return $result;
	
}

function buildFormHtml() {

	$ses 	= $_REQUEST['ses'];
	$usr 	= $_REQUEST['usr'];
	$tbl 	= $_REQUEST['tbl'];
	$action = 'dropzone.php';

	$result = " 
	<body>
	<form action=\"$action\" method=\"post\" class=\"dropzone\" id=\"my-awesome-dropzone\">
	<input type=\"hidden\" name=\"ses\" value=\"$ses\">
	<input type=\"hidden\" name=\"usr\" value=\"$usr\">
	<input type=\"hidden\" name=\"tbl\" value=\"$tbl\">
	<input type=\"hidden\" name=\"go\"   value=\"go\">
	</form>";
	
	return $result;

}

function buildFooterHtml() {

	$result = "
	</body>
	</html>
	";

	return $result;

}

function createDropZoneImageTempTable($table_name = null) {

	$nuConfigDBHost         = $_SESSION['DBHost'];
        $nuConfigDBName         = $_SESSION['DBName'];
        $nuConfigDBUser         = $_SESSION['DBUser'];
        $nuConfigDBPassword     = $_SESSION['DBPassword'];

	if ( null == $table_name ) {
		$table_name 		= '___nu'.uniqid('1').'___';
	}

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (sfi_message varchar(255) NOT NULL, sfi_type varchar(50) NOT NULL, sfi_size varchar(10) NOT NULL, sfi_width int(11) NOT NULL, sfi_height int(11) NOT NULL,sfi_name varchar(255) NOT NULL,sfi_blob longblob)";

	$db = new PDO("mysql:host=$nuConfigDBHost;dbname=$nuConfigDBName;charset=utf8", $nuConfigDBUser, $nuConfigDBPassword, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $values = array();
        $obj = $db->prepare($sql);
        $obj->execute($values);

	return $table_name;

}

function insertDropZoneFile($table_name, $name, $type, $tmp_name, $error, $size) {

	$nuConfigDBHost         = $_SESSION['DBHost'];
        $nuConfigDBName         = $_SESSION['DBName'];
        $nuConfigDBUser         = $_SESSION['DBUser'];
        $nuConfigDBPassword     = $_SESSION['DBPassword'];

	$this_db        = new PDO("mysql:host=$nuConfigDBHost;dbname=$nuConfigDBName;charset=utf8",$nuConfigDBUser,$nuConfigDBPassword,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	$blob           = fopen($tmp_name, 'rb');
	$blobStr        = file_get_contents($tmp_name);
	$im             = imagecreatefromstring($blobStr);
    
	if ($im !== false) {
        	$width  = imagesx($im);
        	$height = imagesy($im);
	}

	$message = getUploadResult($error);

	$sql 		= "INSERT INTO $table_name (sfi_message, sfi_type, sfi_size, sfi_width, sfi_height, sfi_name, sfi_blob) VALUES (:message, :type, :size, :width, :height, :name, :blob)";
	$this_db_obj    = $this_db->prepare($sql);

	$this_db_obj->bindParam(':blob', $blob, PDO::PARAM_LOB);
	$this_db_obj->bindParam(':message', $message);
	$this_db_obj->bindParam(':name', $name);
	$this_db_obj->bindParam(':type', $type);
	$this_db_obj->bindParam(':size', $size);
	$this_db_obj->bindParam(':width', $width);
	$this_db_obj->bindParam(':height', $height);
	$this_db_obj->execute();

	unlink($tmp_name);

}

function nuInsertTestResult($count) {

        $sql            = "INSERT INTO testresults(fileresult) VALUES (:result)";
        //$result         = print_r($_FILES,1);
	//$result 	= print_r($_POST,1);
	$result 	= $count;
	$values		= array(":result" => $result);

	$DBHost         = $_SESSION['DBHost'];
        $DBUser		= $_SESSION['DBUser'];
        $DBPassword     = $_SESSION['DBPassword'];
        $DBName         = $_SESSION['DBName'];
        $conStr         = "mysql:host=$DBHost;dbname=$DBName;charset=utf8";
	$con    	= new PDO($conStr, $DBUser, $DBPassword);
	$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$obj = $con->prepare($sql);
	$obj->execute($values);
}

function checkDropZoneLoggedIn($session_id, $user_id) {

	$nuConfigDBHost         = $_SESSION['DBHost'];
        $nuConfigDBName         = $_SESSION['DBName'];
        $nuConfigDBUser         = $_SESSION['DBUser'];
        $nuConfigDBPassword     = $_SESSION['DBPassword'];

	$db = new PDO("mysql:host=$nuConfigDBHost;dbname=$nuConfigDBName;charset=utf8", $nuConfigDBUser, $nuConfigDBPassword, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$values = array($session_id, $user_id);
	$sql = "SELECT * FROM zzzsys_session WHERE zzzsys_session_id = ? AND sss_zzzsys_user_id = ?";
	$obj = $db->prepare($sql);
	$obj->execute($values);
	$recordObj = $obj->fetch(PDO::FETCH_OBJ);
	$result = $obj->rowCount();
	if ( $result == 1 ) {
		return true;
	} else {
		return false;
	}

}

function getFileMax() {

        $upload_max_filesize            = return_bytes(ini_get('upload_max_filesize'));
        $post_max_size                  = return_bytes(ini_get('post_max_size'));

        if ( $upload_max_filesize > $post_max_size ) {
                $max = ini_get('post_max_size');
        } else {
                $max = ini_get('upload_max_filesize');
        }
	$max = intval($max);
	
        return $max;

}

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

?>
