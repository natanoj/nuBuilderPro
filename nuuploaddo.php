<?php
	require_once(dirname(__FILE__).'/nuuploadlib.php');
        
        $login_result                   = nuuploadCheckLoggedIn($_REQUEST['ses'], $_REQUEST['usr']);
	$head                           = buildHeaderHtml();
        $foot                           = buildFooterHtml();
	$filesAry       		= $_FILES['userfile'];
	$nuUpload 			= new nuUploadResult($filesAry);

	if ( $login_result === true && $nuUpload->error_num == 0 ) {
		nuuploadinsertBlob($nuUpload); 
	}

	$image_url      = "nuuploaddisplay.php?t=".$nuUpload->table."&ses=".$_REQUEST['ses']."&usr=".$_REQUEST['usr'];
	$nuUploaderJson = $nuUpload->getJson();
	
	$jsString	= "<script>$(document).ready( function(){ parent.document.nuUploaderJson=";
	$jsString      .= $nuUploaderJson;
	$jsString      .= ";});</script>";

	echo $head; 
	echo $jsString;

	if ( $login_result === true ) {

		if ( $nuUpload->error_num == 0 ) {

			echo "<div style='height: 100px'><br>";
			echo "<img src='$image_url' style='max-height: 100%; max-width: 100%;'/>";
			echo "</div>";
			
			echo "<div style='height: 100px'>";

			echo "<br><dl>";
			
			echo "<dt>Type:</dt><dd>".$nuUpload->type."</dd>";
			echo "<dt>Name:</dt><dd>".$nuUpload->name."</dd>";	
			echo "<dt>Size:</dt><dd>".$nuUpload->size."</dd>";	
			echo "<dt>Height:</dt><dd>".$nuUpload->height."</dd>";
			echo "<dt>Width:</dt><dd>".$nuUpload->width."</dd>";
			
			echo "</dl>"; 

			echo "</div>";


		} else {
			echo "<p><i>".$nuUpload->status."</i></p>";
		
		}

	} else {
        	echo "<p><i>You are not logged into nuBuilderPro</i></p>";
	} 
	
	echo $foot;
?>
