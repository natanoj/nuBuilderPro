<?php

	session_start();
	error_reporting( error_reporting() & ~E_NOTICE );
	set_time_limit(0);
	mb_internal_encoding('UTF-8');

class nuUploadResult {

	public $name 		= ''; 
	public $type 		= '';
	public $size 		= '';
	public $tmp_name	= '';	
	public $width		= 0;
	public $height  	= 0;
	public $table   	= '';
	public $status  	= '';
	public $error_num 	= 0;
	
	public function getJson() {

		$name 		= $this->name;
		$type 		= $this->type;
		$size 		= $this->size;
		$width 		= $this->width;
		$height 	= $this->height;
		$table 		= $this->table;
		$status 	= $this->status;
		$error_num 	= $this->error_num;	

		$J[]	= "{ |name| : |$name|, |type| : |$type|, |size| : |$size|, |width| : |$width|, |height| : |$height|, |table| : |$table|, |status| : |$status|, |error_num| : |$error_num| }";
		$JSON   = implode(', ', str_replace('|', '"', $J));

		return $JSON;
	}

	private $phpFileUploadErrors = array(
                0 => 'There is no error, the file uploaded with success',
                1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                3 => 'The uploaded file was only partially uploaded',
                4 => 'No file was uploaded',
                6 => 'Missing a temporary folder',
                7 => 'Failed to write file to disk.',
                8 => 'A PHP extension stopped the file upload.'
        );

	function __construct($filesAry) { 

		$this->name           	= $filesAry['name'];
		$this->type           	= $filesAry['type'];
		$this->size           	= $filesAry['size'];
		$this->tmp_name      	= $filesAry['tmp_name'];
		$this->error_num 	= intval($filesAry['error']);
		$this->status 		= $this->phpFileUploadErrors[$this->error_num];
		$this->table		= '___nu'.uniqid('1').'___';
	}

}

function buildHeaderHtml() {

	$result = "
	<html>
	<head>
	<meta http-equiv='Content-type' content='text/html;charset=UTF-8'>
	<title>nuBuilder</title>
	<link rel='stylesheet' href='jquery/jquery-ui.css' />
	<script src='jquery/jquery-1.8.3.js' type='text/javascript'></script>
	<script src='jquery/jquery-ui.js' type='text/javascript'></script>
	<style>
	body { margin:30; font-family:'Helvetica Neue, Helvetica, Arial, sans-serif'; font-size:13px; line-height:18px; color:#202020; background-color:#f4f4f4; }
	</style>
	<body>";

	return $result;
}

function buildFooterHtml() {

	$result = "
	</body>
	</html>
	";

	return $result;

}

function nuuploadgetBlob($table) {

	$nuConfigDBHost         = $_SESSION['DBHost'];
        $nuConfigDBName         = $_SESSION['DBName'];
        $nuConfigDBUser         = $_SESSION['DBUser'];
        $nuConfigDBPassword     = $_SESSION['DBPassword'];

	$sql = "SELECT sfi_type, sfi_blob FROM $table ";
	$conStr         = "mysql:host=$nuConfigDBHost;dbname=$nuConfigDBName;charset=utf8";
	$this_db        = new PDO($conStr, $nuConfigDBUser, $nuConfigDBPassword);
	$this_db_obj    = $this_db->prepare($sql);

	$this_db_obj->execute(array());
	$this_db_obj->bindColumn(1, $mime);
	$this_db_obj->bindColumn(2, $data, PDO::PARAM_LOB);
	$this_db_obj->fetch(PDO::FETCH_BOUND);

	$result 	= array();
	$result[0] 	= $mime;
	$result[1]	= $data; 

	return $result;
}

function createImageTempTable($table_name) {

	$nuConfigDBHost         = $_SESSION['DBHost'];
        $nuConfigDBName         = $_SESSION['DBName'];
        $nuConfigDBUser         = $_SESSION['DBUser'];
        $nuConfigDBPassword     = $_SESSION['DBPassword'];

	$sql = "CREATE TABLE $table_name (sfi_type varchar(50) NOT NULL, sfi_size varchar(10) NOT NULL, sfi_width int(11) NOT NULL, sfi_height int(11) NOT NULL,sfi_name varchar(255) NOT NULL,sfi_blob longblob)";

	$db = new PDO("mysql:host=$nuConfigDBHost;dbname=$nuConfigDBName;charset=utf8", $nuConfigDBUser, $nuConfigDBPassword, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $values = array();
        $obj = $db->prepare($sql);
        $obj->execute($values);

}

function nuuploadinsertBlob($nuUpload) {

	$nuConfigDBHost         = $_SESSION['DBHost'];
        $nuConfigDBName         = $_SESSION['DBName'];
        $nuConfigDBUser         = $_SESSION['DBUser'];
        $nuConfigDBPassword     = $_SESSION['DBPassword'];

	$this_db        = new PDO("mysql:host=$nuConfigDBHost;dbname=$nuConfigDBName;charset=utf8",$nuConfigDBUser,$nuConfigDBPassword,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	$blob           = fopen($nuUpload->tmp_name, 'rb');
	$blobStr        = file_get_contents($nuUpload->tmp_name);
	$im             = imagecreatefromstring($blobStr);
    
	if ($im !== false) {
        	$nuUpload->width  = imagesx($im);
        	$nuUpload->height = imagesy($im);
	}

	createImageTempTable($nuUpload->table);

	$sql 		= "INSERT INTO ".$nuUpload->table." (sfi_type, sfi_size, sfi_width, sfi_height, sfi_name, sfi_blob) VALUES (:type, :size, :width, :height, :name, :blob)";
	$this_db_obj    = $this_db->prepare($sql);

	$this_db_obj->bindParam(':blob', $blob, PDO::PARAM_LOB);
	$this_db_obj->bindParam(':name', $nuUpload->name);
	$this_db_obj->bindParam(':type', $nuUpload->type);
	$this_db_obj->bindParam(':size', $nuUpload->size);
	$this_db_obj->bindParam(':width', $nuUpload->width);
	$this_db_obj->bindParam(':height', $nuUpload->height);
	$this_db_obj->execute();

	unlink($nuUpload->tmp_name);

}

function nuuploadCheckLoggedIn($session_id, $user_id) {
	
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
