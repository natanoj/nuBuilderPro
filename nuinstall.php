<?php
	error_reporting( error_reporting() & ~E_NOTICE );
        require_once("config.php");
	$this_ver = phpversion();
	$this_ver = intval($this_ver[0]);
	if ( $this_ver >= 7 ) {
        	require_once("nuinstall_lib2.php");
	} else {
		require_once("nuinstall_lib.php");
	}

        session_start();

        $_SESSION['DBHost']                 = $nuConfigDBHost;
        $_SESSION['DBName']                 = $nuConfigDBName;
        $_SESSION['DBUser']                 = $nuConfigDBUser;
        $_SESSION['DBPassword']             = $nuConfigDBPassword;
        $_SESSION['DBGlobeadminPassword']   = $nuConfigDBGlobeadminPassword;
        $_SESSION['title']                  = $nuConfigtitle;

        $template = new nuinstall();
        $template->setDB($_SESSION['DBHost'], $_SESSION['DBName'], $_SESSION['DBUser'], $_SESSION['DBPassword']);
	$template->checkInstall();

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv='Content-type' content='text/html;charset=UTF-8'>

<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
<meta name="apple-mobile-web-app-capable" content="yes" />

<title>nuBuilder</title>

<style>
.nuShadeHolder               {border-bottom-left-radius:5px;border-bottom-right-radius:5px;border-top-right-radius:5px;border-top-left-radius:5px;box-shadow: 5px 5px 5px #888888;position:absolute;top:70px;left:0px;margin:auto;

	width:auto; height:auto}
</style>
<body bgcolor="#CCCCCC">

	<div class="nuShadeHolder" style="width: 500px; height: 300px; top: 15px; left: 15px; border: 1px solid grey; position: absolute; background-color: rgb(255, 255, 255);">

			<span style="top: 15px; left: 20px;  text-align: center; font-size: 25px; font-family: sans-serif; position: absolute; background-color: rgb(255, 255, 255);"> 
			<strong><span style="color: #78C13A; text-shadow: 1px 1px #D0D7D1;">nu</span><span style="color: #03AAE9; text-shadow: 1px 1px #D0D7D1;">Builder</span></strong><strong><span style="color: #FB0003; text-shadow: 1px 1px #D0D7D1;"><i>Pro</i></span></strong>
			</span>

			<span style="top: 55px; left: 20px;  text-align: left; font-size: 15px; font-family: sans-serif; position: absolute; background-color: rgb(255, 255, 255);">

			<?php  if ( $template->initResult != 'CANNOT_CONNECT_TO_SERVER' && $template->initResult != 'DATABASE_NOT_CREATED' ) { ?>
				
			<h3>Schema Installer / Upgrader</h3>

			<form method="POST" action="nuinstall.php">
			<table>
        		<tr>
				<td>Globeadmin Password</td> <td><input type="password" name="pwd"></td>
			</tr>
			<?php  if ( $template->initResult != 'SCHEMA_INCOMPLETE' ) { ?>
			<tr>
				<td>Override Setup Table</td>
				<td>
        				<select name="overrideSetup">
                			<option value="n" SELECTED>No</option>
                			<option value="y">Yes</option>
        				</select>
				</td>
        		</tr>
			<tr>
				<td>Drop columns not used by nuBuilder</td>	
				<td>
					<select name="dropColumns">
					<option value="n">No</option>
					<option value="y" SELECTED>Yes</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Drop indexes not used by nuBuilder</td>
        			<td>
					<select name="dropIndexes">
                			<option value="n">No</option>
                			<option value="y" SELECTED>Yes</option>
        				</select>
        			</td>
			</tr>
			<?php } ?>
			<tr>
				<td>Show full output</td>
				<td>
        				<select name="showAll">
                			<option value="n">No</option>
                			<option value="y" SELECTED>Yes</option>
        				</select>
        			</td>
			</tr>
			</table>
		</span>

		<input type="submit" value="Run" style="width: 60px; height: 35px; top: 150px; left: 350px; position: absolute; border-top-left-radius: 5px; border-top-right-radius: 5px; border-bottom-right-radius: 5px; border-bottom-left-radius: 5px; font-size: 22px; line-height: 22px; background-color: rgb(212, 206, 230);">
		</form>

		<?php } else {
			if ( $this_ver >= 7 ) {
				echo $template->getCode();
				echo "<br>";
				echo $template->getMessage();
			} else {
				echo "It appears your database server is not configured, <br>please enter a mysql database name, user name<br> and password into your config.php file.";
			}	

		} ?>
	</div>

<?php
	if ( isset($_POST['pwd']) &&  $_POST['pwd'] != $_SESSION['DBGlobeadminPassword'] ) {

		echo "<div class=\"nuShadeHolder\" style=\"width: 500px; height: 40px; top: 330px; left: 15px; border: 1px solid grey; position: absolute; background-color: rgb(255, 255, 255);\">";
			echo "<span style=\"top: 10px; left: 20px;  text-align: left; font-size: 15px; font-family: sans-serif; position: absolute; background-color: rgb(255, 255, 255);\">";
				echo "Globadmin password incorrect";
			echo "</span>";
		echo "</div>";

	}

	if ( isset($_POST['pwd']) &&  $_POST['pwd'] == $_SESSION['DBGlobeadminPassword'] ) {

		$template       = new nuinstall();
        	$template->setDB($_SESSION['DBHost'], $_SESSION['DBName'], $_SESSION['DBUser'], $_SESSION['DBPassword']);
		$template->checkInstall();

		if ( $_POST['overrideSetup'] == "y" ) {
                        $template->overrideSetup = true;
                }

		if ( $_POST['dropColumns'] == "y" ) {
			$template->removeColumns = true;
		}

		if ( $_POST['dropIndexes'] == "y" ) {
                        $template->removeIndexes = true;
                }

	        $template->run();
		$height = '600px';
		$width  = '500px';
		if ( $_POST['showAll'] == "y" ) {
			$height = '1100px';
                	$width  = '900px';
		}
		echo "<div style=\"width: $width; height: $height; top: 330px; left: 15px; position: absolute; background-color: #CCCCCC;\">";
			echo "<span style=\"top: 10px; left: 20px;  text-align: left; font-size: 15px; font-family: sans-serif; position: absolute; background-color: #CCCCCC;\">";
				
				echo "Done! <br>";
				$template->showChangeSummary();
		                $template->showSQLerrors();
                		$template->showWarnings();
				if ( $_POST['showAll'] == "y" ) {
					echo "<h4>Full Output:</h4>";
					echo "<pre>";
					$template->showContent();
					echo "</pre>";	
				}
			 echo "</span>";
		echo "</div>";

	}
?>

</body>
</html>
