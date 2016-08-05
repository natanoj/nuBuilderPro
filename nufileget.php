<?php 
	$conf = dirname(__FILE__).'/config.php';
	require($conf);
	
	$code 		= $_GET['i'];
	$sql 		= "SELECT sfi_type, sfi_blob, sfi_name FROM zzzsys_file WHERE sfi_code=:code";
	$conStr 	= "mysql:host=$nuConfigDBHost;dbname=$nuConfigDBName;charset=utf8";
	$this_db        = new PDO($conStr, $nuConfigDBUser, $nuConfigDBPassword);
	$this_db_obj 	= $this_db->prepare($sql);
	
	$this_db_obj->execute(array(":code" => $code));
	$this_db_obj->bindColumn(1, $mime);
	$this_db_obj->bindColumn(2, $data, PDO::PARAM_LOB);
	$this_db_obj->bindColumn(3, $name);
	$this_db_obj->fetch(PDO::FETCH_BOUND);

header("Content-Type:" . $mime);
echo $data;
?>
