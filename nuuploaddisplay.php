<?php 

	require_once(dirname(__FILE__).'/nuuploadlib.php');

	$login_result	= nuuploadCheckLoggedIn($_REQUEST['ses'], $_REQUEST['usr']);

	if ( $login_result === true ) {

		$blobInfo 	= nuuploadgetBlob($_REQUEST['t']);
		$mine		= $blobInfo[0];
		$data		= $blobInfo[1];

		header("Content-Type:" . $mime);
		echo $data;

	} else {

		$my_img = imagecreate( 180, 75 );
		$background = imagecolorallocate( $my_img, 0, 0, 255 );
		$text_colour = imagecolorallocate( $my_img, 255, 255, 0 );
		imagestring( $my_img, 4, 30, 25, "no permissions", $text_colour );
		imagesetthickness ( $my_img, 5 );

		header( "Content-type: image/png" );
		imagepng( $my_img );
		imagedestroy( $my_img );
		
	}
?>
