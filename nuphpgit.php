<?php
	error_reporting( error_reporting() & ~E_NOTICE );
	
	require_once("config.php");

	define('NU_CACHE_TIME_STAMP', 'Y_m_d_H');

	define('GITERROR',	'GITERROR');

	define('GIT','http://gitcache.nubuilder.net/contents/');
        define('RAW','http://gitcache.nubuilder.net/master/');

	define('NUAGENT', 'nuSoftware/nuBuilderPro/AutoUpdater');	

	$tmp_folder	= dirname(__FILE__).DIRECTORY_SEPARATOR.'tmp';
	$download_dest 	= dirname(__FILE__).DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'nuGit';
	$copy_dest     	= dirname(__FILE__).DIRECTORY_SEPARATOR;

	define('DOWNLOAD_DEST', $download_dest);
	define('COPY_DEST',     $copy_dest);

$exclude_files 	= array('ReadMe.md','ajax-loader.gif','apple-touch-icon.png','config.php','nuBuilder-Logo-medium.png','numove_black.png','numove_red.png','nurefresh_black.png');
	$folders	= array('','nusafephp');
	$files_list	= array();
	$errors		= array();
	$success	= array();
	$dbupdate	= array();
	$finalResult 	= array( 'message'=>'', 'errors'=>array(), 'success'=>array(), 'dbupdate'=>array() );
	$login 		= checkGlobeadmin($nuConfigDBHost, $nuConfigDBName, $nuConfigDBUser, $nuConfigDBPassword);

	if ( 0 == errorCount() ) {	
		try {
    			$writeable = checkIsWriteable($folders, $tmp_folder, $copy_dest);
		} catch (Exception $e) {
			setError("Exception trying to test file permissions");
		}
	}

	if ( 0 == errorCount() ) {
		setupTmpFolder($folders);
	}

	if ( 0 == errorCount() ) {
		for ( $x=0; $x < count($folders); $x++ ) {
			buildFilesListFromGit($files_list, $exclude_files, $folders[$x]);
		}
	}

	if ( 0 == errorCount() ) {
		downloadFiles($files_list);
	}	

	if ( 0 == errorCount() ) {
                copyFiles($files_list);
        }

	if ( 0 == errorCount() ) {
		$dbupdate = updateDB($nuConfigDBHost, $nuConfigDBName, $nuConfigDBUser, $nuConfigDBPassword);
        }

	if ( errorCount() > 0 ) {
		$finalResult['message'] = 'ERRORS';
	} else {
		$finalResult['message'] = 'SUCCESS';
	}

	$successCount 			= count($success);
	$finalResult['errors'] 		= $errors;
	$finalResult['success']		= array("$successCount File(s) updated");
	$finalResult['dbupdate']	= $dbupdate;

	$json = json_encode($finalResult);

	//flush();
	header('Content-Type: application/json');
	echo $json;

function checkGlobeadmin($nuConfigDBHost, $nuConfigDBName, $nuConfigDBUser, $nuConfigDBPassword) {

	$login = false;
	$session_id = $_REQUEST['sessid'];

	$db = new PDO("mysql:host=$nuConfigDBHost;dbname=$nuConfigDBName;charset=utf8", $nuConfigDBUser, $nuConfigDBPassword, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$values = array($session_id, 'globeadmin');
	$sql = "SELECT * FROM zzzsys_session WHERE zzzsys_session_id = ? AND sss_zzzsys_user_id = ?";
	$obj = $db->prepare($sql);
	$obj->execute($values);
	$recordObj = $obj->fetch(PDO::FETCH_OBJ);
	$result = $obj->rowCount();

	if ( $result == 1 ) {
        	$then = $recordObj->sss_timeout;
        	$now  = time();
        	$diff = bcsub($now, $then, 0);
        	if ($diff < 1800) {
			$login = true;
        	}
	}

	if (!$login) {
		setError("Not Logged in as globeadmin");
	}

	unset($obj);
	unset($db);
	return $login;
}

function updateDB($nuConfigDBHost, $nuConfigDBName, $nuConfigDBUser, $nuConfigDBPassword) {

	$result = array();

	$this_ver = phpversion();
        $this_ver = intval($this_ver[0]);

        if ( $this_ver >= 7 ) {
                require_once("nuinstall_lib2.php");
		$template = new nuinstall($nuConfigDBHost, $nuConfigDBName, $nuConfigDBUser, $nuConfigDBPassword, false);
		$template->checkInstall();
		$template->run();
		$result         = $template->returnArrayResults();
		$result['info'] = bin2hex($template->display);
        } else {
                require_once("nuinstall_lib.php");
        	$template = new nuinstall();
        	$template->setDB($nuConfigDBHost, $nuConfigDBName, $nuConfigDBUser, $nuConfigDBPassword);
       		$template->removeColumns = true;
        	$template->removeIndexes = true;
		$template->checkInstall();
        	$template->run();
		$result         = $template->returnArrayResults();
		$result['info'] = bin2hex('');
	}
	return $result;	
}

function checkIsWriteable($folders, $download_dest, $copy_dest) {

	$errors = 0;
        if ( !is_writable($download_dest) ) {
		setError("Download destination is not writable: $download_dest");
		$errors++;
	}

	if ( !is_writable($copy_dest) ) {
                setError("Copy destination is not writable: $download_dest");
		$errors++;
	}

	for ( $x=0; $x < count($folders); $x++ ) {

		$folder = $folders[$x];
		if ( $folder != '' ) {
			$folder .= DIRECTORY_SEPARATOR;
		}
		$search_folder = $copy_dest.$folder;

		if ($handle = opendir($search_folder)) {
                	while (false !== ($entry = readdir($handle))) {
                        	if ($entry != "." && $entry != "..") {
					$search_file = "$search_folder$entry";
                                	if ( is_file($search_file) ) {
						if ( $entry[0] != '.' ) {
							if ( !is_writable($search_file) ) {
                						setError("File is not writable: $search_file");
								$errors++;
							}
						}
                                	}	
                        	}
                	}
                closedir($handle);
        	}
	}

	if ( $errors > 0 ) {
		return false;
	} else {
		return true;
	}
}

function checkSubFolders($folders, $folder) {

        for ($x=0; $x < count($folders); $x++) {
                $this_folder = $folder.DIRECTORY_SEPARATOR.$folders[$x];
                if ( !is_dir($this_folder) ) {
                        setError("Error checking sub folder: $this_folder");
                }
        }
}

function setupSubFolders($folders, $folder) {

	for ($x=0; $x < count($folders); $x++) {
		$this_folder = $folder.DIRECTORY_SEPARATOR.$folders[$x];
		@mkdir($this_folder, 0755);
		if ( !is_dir($this_folder) ) {
			setError("Error creating sub folder: $this_folder");
		}
	}	
}

function setupTmpFolder($folders) {

	@rmdir(DOWNLOAD_DEST);
	@mkdir(DOWNLOAD_DEST, 0755);	

	if ( is_dir(DOWNLOAD_DEST) ) {
		setupSubFolders($folders, DOWNLOAD_DEST);
	} else {
		setError("Error creating tmp folder: ".DOWNLOAD_DEST);
	}
}

function downloadFiles($files) {

	for ( $x=0; $x < count($files); $x++) {

		$file = $files[$x];

		@unlink($file->download_dest);
		@file_put_contents($file->download_dest, file_get_contents($file->raw_url), LOCK_EX);
		@$file->downloaded_size = filesize($file->download_dest);
				
		if ( $file->downloaded_size != $file->git_size ) {
			setError("Downloading $file->raw_url files sizes do not match $file->download_dest");
		}
	}
}

function copyFiles($files) {

        for ( $x=0; $x < count($files); $x++) {

                $file  		= $files[$x];
		$this_error 	= 0;

                @unlink($file->copy_dest);

                $copy   = copy($file->download_dest, $file->copy_dest);

		if (!$copy) {
                        setError("Copy error: $file->copy_dest");
			$this_error++;	
                }

		$size = filesize($file->copy_dest);
                if ( $size != $file->git_size ) {
                        setError("Copy file size check error $file->copy_dest");
			$this_error++;
                }
	
		if ( $this_error == 0 ) {	
			setSuccess("Success: $file->name");
		}
        }
} 

function setError($msg) {
	global $errors;
	array_push($errors, $msg);
}

function setSuccess($msg) {
        global $success;
        array_push($success, $msg);
}

function errorCount() {
	global $errors;
	return count($errors);
}

function buildFilesListFromGit(&$files_list, $exclude_files, $folder = '') {

	$git_url = GIT;	
	if ( $folder != '') {
		$git_url = $git_url.$folder.'/';
	}

	$git = doCurl($git_url);

        if ($git[1] != '200') {
        	setError("Calling $git_url, status $git[1], error: $git[2] ");
	}

	$jsonGit = json_decode($git[0], true);

	for ( $x=0; $x < count($jsonGit); $x++) {
        	if ( $jsonGit[$x]['type'] != 'dir' ) {
                	if ( !in_array($jsonGit[$x]['name'],$exclude_files) ) {
                        	$file = new nuFile($jsonGit[$x], $folder);
                                array_push($files_list, $file);
                        }
                }
        }
	return $files_list;
}

function doCurl($url){

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_USERAGENT, NUAGENT);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	$result[0] 	= curl_exec($ch);
	$result[1] 	= curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$result[2]	= curl_error($ch);
	curl_close($ch);

	return $result;
}

class nuFile {

	public $folder;
	public $name;
	public $git_size;
	public $raw_url;
	public $download_dest;
        public $copy_dest;
	public $downloaded_size;
        public $copied_size;
	
	function __construct($gitObj, $folder = '') {

		if ( $folder == '' ) {
			$seperator = '';
			$seperator2 = '';
		} else {
			$seperator = '/';
			$seperator2 = DIRECTORY_SEPARATOR;
		}

		$this->folder   	= $folder;
		$this->name 		= $gitObj['name'];
		$this->git_size 	= $gitObj['size'];
		$this->raw_url  	= RAW.$folder.$seperator.$gitObj['name'];
		$this->download_dest	= DOWNLOAD_DEST.DIRECTORY_SEPARATOR.$folder.$seperator2.$gitObj['name'];
		$this->copy_dest	= COPY_DEST.$folder.$seperator2.$gitObj['name'];
	}
}

function logger($msg) {
	$log = dirname(__FILE__).DIRECTORY_SEPARATOR.'nuphpgit-errors.log';
	error_log($msg, 3, $log);
}

?>
