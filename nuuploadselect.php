<?php

	require_once(dirname(__FILE__).'/nuuploadlib.php');
	$login_result 			= nuuploadCheckLoggedIn($_REQUEST['ses'], $_REQUEST['usr']);
	$head				= buildHeaderHtml();	
	$foot				= buildFooterHtml();
	
	echo $head;

?>

<script>
function switchToProgress() {

        $('#nuFileFormWrapper').css('visibility', 'hidden');
	$('#mainArea').css('background', 'url(nuuploader.gif) no-repeat center');

}
</script>

<?php if ( $login_result === true ) { ?>

<div id='mainArea'>

	<div id='nuFileFormWrapper'>
	
	<br><h3>Please select a file to upload.</h3><p><i>The size limit of your server is <?php echo getFileMax(); ?> </i></p>
	
	<form id='nuFileForm' enctype='multipart/form-data' action='nuuploaddo.php' method='POST'>
	
	<input type='hidden' name='ses' value='<?php echo $_REQUEST['ses']; ?>' />
	<input type='hidden' name='usr' value='<?php echo $_REQUEST['usr']; ?>' />
	
	<input name='userfile' type='file' onchange='this.form.submit(); switchToProgress();' />
	
	</form>
	</div>

</div>

<?php } else { ?>

	<p><i>You are not logged into nuBuilderPro</i></p>

<?php } 

echo $foot;

?>
