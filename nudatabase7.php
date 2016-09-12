<?php

	$nu_pdo_messages      = array(
                '1044'      => 'user name or password in config.php does not have access to the database or you typed something incorrectly in config.php',
                '1045'      => 'user name or password in config.php does not have access to the database or you typed something incorrectly in config.php',
                '1049'      => 'mysql database not created or wrong database name in config.php',
                '2002'      => 'mysql database service not running or wrong ip address in config.php',
                'nubuilder' => 'nuBuilder has not yet been installed into your database',
                'default'   => 'Unable to determine your error'
        );

        $nu_pdo_codes      = array(
                '1044'      => 'CANNOT_CONNECT_TO_SERVER',
                '1045'      => 'CANNOT_CONNECT_TO_SERVER',
                '1049'      => 'DATABASE_NOT_CREATED',
                '2002'      => 'CANNOT_CONNECT_TO_SERVER',
                'default'   => 'UNKOWN'
        );

        try {

                $nuDB = new PDO("mysql:host=$DBHost;dbname=$DBName;charset=utf8", $DBUser, $DBPassword, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
                $nuDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	} catch(Throwable $e) {

		$num = $e->getCode();
                
		if ( !array_key_exists($num, $nu_pdo_codes) ) {
		
			$num = 'default';

		} else {

			echo "oops! It looks like your nuBuilderPro site is not installed correctly";
                        echo "<br><br>";
                        echo $nu_pdo_codes[$num];
                        echo "<br>";
                        echo $nu_pdo_messages[$num];
                        echo "<br>";
                        echo "Please review the install instructions";
                        echo "<br>";
                        echo "<a href='https://www.nubuilder.net/downloads.php'>https://www.nubuilder.net/downloads.php</a>";

                }

                die();

        } catch(Exception $e) {

                $num = $e->getCode();
                
		if ( !array_key_exists($num, $nu_pdo_codes) ) {
	
			$num = 'default';

		} else {	

			echo "oops! It looks like your nuBuilderPro site is not installed correctly";
                        echo "<br><br>";
                        echo $nu_pdo_codes[$num];
                        echo "<br>";
                        echo $nu_pdo_messages[$num];
                        echo "<br>";
                        echo "Please review the install instructions";
                        echo "<br>";
                        echo "<a href='https://www.nubuilder.net/downloads.php'>https://www.nubuilder.net/downloads.php</a>";

                }

                die();

        }

?>
