<?php
class nuinstallException extends Exception {}
class nuinstall {

        var $DB              = array();
	var $display         = "";
	var $removeColumns   = true;
	var $removeIndexes   = true;
	var $overrideSetup   = false;
	var $initResult	     = 'UNKNOWN';
	var $con	     = null;
	var $lastDB	     = null;

	// Begin Legacy properties
	var $summary1        = array();//records added information
        var $summary2        = array();//records changed information
        var $sqlErrors       = array();
        var $warnings        = array();
	// End Legacy properties

	var $messages      = array(
                '1045'      => 'user name or password in config.php does not have access to the database or you typed something incorrectly in config.php',
                '1049'      => 'mysql database not created or wrong database name in config.php',
                '2002'      => 'mysql database service not running or wrong ip address in config.php',
                'nubuilder' => 'nuBuilder has not yet been installed into your database',
                'default'   => 'Unable to determine your error',
		'ok'        => 'OK'
        );
        var $codes      = array(
                '1045'      => 'CANNOT_CONNECT_TO_SERVER',
                '1049'      => 'DATABASE_NOT_CREATED',
                '2002'      => 'CANNOT_CONNECT_TO_SERVER',
                'nubuilder' => 'SCHEMA_INCOMPLETE',
                'default'   => 'UNKOWN',
		'ok'	    => 'OK'
        );
        var $message  = 'UNKOWNN';
        var $code     = 'UNKNOWN';

	function __construct($DBHost = null, $DBName = null, $DBUserID = null, $DBPassWord = null, $overrideSetup = false) { 
		$this->addDisplay("construct nuinstall");
		$this->setDB($DBHost, $DBName, $DBUserID, $DBPassWord);
		$this->overrideSetup = $overrideSetup;
	}

	function getCode(){
		return $this->code;
	}

	function getMessage(){
		return $this->message;
	}

	function setCodeAndMessage($num) {
		if ( array_key_exists($num, $this->codes) ) {
			$this->message    = $this->messages[$num];
			$this->code       = $this->codes[$num];
			$this->initResult = $this->codes[$num];
		} else {
			$this->message    = $this->messages['default'];
                        $this->code       = $this->codes['default'];
			$this->initResult = $this->codes['UNKNOWN'];
		}
	}

	function nuecho($content) {
		echo $content;
	}
	
	function checkInstall() {

		$this->addDisplay("starting checkInstall()");
		if ( $this->checkDatabaseConnection() ) {
			$this->checkTableExists();
		}
		$this->addDisplay("completed checkInstall()");
		return $this->initResult;
	}

        function setDB($DBHost, $DBName, $DBUserID, $DBPassWord) {

		$this->addDisplay("starting setDB() :: $DBName");
                $this->DB['DBHost']       = $DBHost;
                $this->DB['DBName']       = $DBName;
                $this->DB['DBUserID']     = $DBUserID;
                $this->DB['DBPassWord']   = $DBPassWord;
		$this->addDisplay("completed setDB()");

        }

	function checkDatabaseConnection() {
	
		$this->addDisplay("starting checkDatabaseConnection()");	
                $DBHost         = $this->DB['DBHost'];
                $DBUserID       = $this->DB['DBUserID'];
                $DBPassWord     = $this->DB['DBPassWord'];
                $DBName         = $this->DB['DBName'];
        	$conStr         = "mysql:host=$DBHost;dbname=$DBName;charset=utf8";

        	try {
                	$con    = new PDO($conStr, $DBUserID, $DBPassWord);
                	$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->con = $con;
			$result = true;
			$this->setCodeAndMessage('ok');	

                } catch(Throwable $e) {
                        $result           = false;
                        $message          = "->checkDatabaseConnection ".$e->getMessage();
			$this->addDisplay("\t :: $message");
			$this->setCodeAndMessage($e->getCode());

		} catch(Exception $e) {
                        $result           = false;
                        $message          = "->checkDatabaseConnection ".$e->getMessage();
                        $this->addDisplay("\t :: $message");
			$this->setCodeAndMessage($e->getCode());
                }	
			
		$this->addDisplay("completed checkDatabaseConnection()");
		return $result;
        }

	function selectDatabase($name) {

		if ( $this->lastDB != $name ) {
			try {

				$this->lastDB = $name;	
				$db = $this->con;	
				$db->exec("USE $name");
				$this->setCodeAndMessage('ok');  

			} catch(Throwable $e) {

				$message          = "->selectDatabase ".$e->getMessage();
				$this->setCodeAndMessage($e->getCode());
                        	throw new nuinstallException($message, null, $e);
                	
			} catch(Exception $e) {

                                $message          = "->selectDatabase ".$e->getMessage();
				$this->setCodeAndMessage($e->getCode());
                                throw new nuinstallException($message, null, $e);
                        }
	
		}
	}

	function checkTableExists($table = 'zzzsys_setup') {

		$this->addDisplay("starting checkTableExists($table)");

		$db		= $this->con;
                $DBName         = $this->DB['DBName'];

		$sql 		= "SELECT TABLE_NAME FROM TABLES WHERE TABLE_SCHEMA=:tbl_sch AND TABLE_NAME=:tbl_nme";
		$values 	= array(":tbl_sch" => $DBName, ":tbl_nme" => $table);
		$num		= 0;

		try { 
			$obj 		= $this->runQuery($sql, $values, 'information_schema');
			$recordObj 	= $obj->fetch(PDO::FETCH_OBJ);
			$num 		= $obj->rowCount();

                } catch (Throwable $e) {
	
			$this->con 	  = null;
			$this->setCodeAndMessage($e->getCode());
			throw new nuinstallException($e->getMessage(), null, $e);	
		
		} catch (Exception $e) {

                        $this->con        = null;
			$this->setCodeAndMessage($e->getCode());
                        throw new nuinstallException($e->getMessage(), null, $e);

                }

		if ($num != 1 ) {
			$result = false;
			$this->setCodeAndMessage('nubuilder');
			$this->forceSetupOverride();
		} else {
			$this->setCodeAndMessage('ok');
			$result = true;	
		}
		$this->addDisplay("completed checkTableExists($table)");
		return $result;
	}

	function forceSetupOverride() {

		if ( $this->initResult == 'SCHEMA_INCOMPLETE' ) {
			$this->overrideSetup = true;
			$this->addDisplay("Forced override of zzzsys_setup table :: ".$this->initResult);
		}		

	}

	function importTemplate() {

		$this->addDisplay("starting importTemplate()");

		try { 
			$file   = realpath(dirname(__FILE__))."/nu_template.sql";
			@$handle = fopen($file, "r");
			$temp 	= "";
			if ($handle) {
				while (($line = fgets($handle)) !== false) {
					if ($line[0] != "-" AND $line[0] != "/"  AND $line[0] != "\n") {
						$line = trim($line);
						$temp .= $line;
						if ( substr($line, -1) == ";" ) {
                        				$temp   = rtrim($temp,';');
							$this->runQuery($temp);
                        				$temp = "";

                				}
        				}
    				}
			} else {
				throw new nuinstallException("error opening the file: $file");
			}
		} catch (Throwable $e) {
                        throw new nuinstallException("error opening the file :: nu_template.sql ");

		} catch (Exception $e) {
			throw new nuinstallException("error opening the file :: nu_template.sql ");
		} 
		$this->addDisplay("completed importTemplate()");
        }

	function addDisplay($content) {
		$this->display .= $content.PHP_EOL;
	}

	function run() {

		$this->addDisplay("starting run()");

		if ( ! in_array($this->initResult, array('OK','SCHEMA_INCOMPLETE')) ) {
			$this->addDisplay("nuinstall unable to complete :: ".$this->getCode()." :: ".$this->getMessage());	
		} else {
			try {
				$this->importTemplate();
				$this->compareTables();

			} catch (nuinstallException $e) {
				$this->addDisplay("failed during run(), nuinstallExcepion:: ".$e->getMessage());
				$this->addDisplay("nuinstall unable to complete :: ".$this->getCode()." :: ".$this->getMessage());
				return; 
			}
		}
		$this->addDisplay("completed run()");
	}

	function showContent() {
		$this->nuecho($this->display);
	}
	
	function makeTable($template_table, $real_table) {

		$create_rs      = $this->runQuery("SHOW CREATE TABLE $template_table");
		$create_arry    = $create_rs->fetch(PDO::FETCH_BOTH); 

                $create_sql     = str_replace("CREATE TABLE `$template_table`",  "CREATE TABLE IF NOT EXISTS `$real_table`", $create_arry[1]);
                $this->runQuery($create_sql);

		$this->addDisplay("\t :: makeTable :: $real_table ");
	
	}

	function deleteNubuilderInfo($template_table, $real_table) {

		if ( $this->overrideSetup === false && $real_table == 'zzzsys_setup' ) {

			$this->addDisplay("\t :: Skipping changes to zzzsys_table");
		
		} else {

                	$id        = $real_table."_id";
	                $clean_sql = "DELETE FROM $real_table WHERE $id IN (SELECT $id FROM $template_table) ";
                	$this->runQuery($clean_sql);
			$this->addDisplay("\t :: deleteNubuilderInfo :: $real_table ");
				
		}
	}

	function loopColumns($template_table, $real_table) {

		$this->addDisplay("\t :: loopColumns :: $template_table ");

		$DBName = $this->DB['DBName'];

		// loop thru all columns in the template table
                $template_sql  = "SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, COLUMN_DEFAULT, IS_NULLABLE, COLUMN_TYPE, COLUMN_KEY ";
                $template_sql .= "FROM `COLUMNS` ";
                $template_sql .= "WHERE `TABLE_SCHEMA`=:table_schema ";
                $template_sql .= "AND `TABLE_NAME`=:table_name ";
		$values        = array(":table_schema" => $DBName, ":table_name" => $template_table);		
             	$template_rs   = $this->runQuery($template_sql, $values, 'information_schema');

		while ( $template_obj = $template_rs->fetch(PDO::FETCH_OBJ) ) {
  
                	$column_name      = $template_obj->COLUMN_NAME;
                        $column_type      = $template_obj->COLUMN_TYPE;

                        if ( $template_obj->IS_NULLABLE == "YES" ) {
                        	$is_null = '';
                        } else {
                        	$is_null = 'NOT ';
                        }

                        // get column from real table matching the template column
                        $real_column_sql  = "SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, COLUMN_DEFAULT, IS_NULLABLE, COLUMN_TYPE, COLUMN_KEY ";
                        $real_column_sql .= "FROM `COLUMNS` ";
                        $real_column_sql .= "WHERE `TABLE_SCHEMA`=:table_schema ";
                        $real_column_sql .= "AND `TABLE_NAME`=:table_name ";
                        $real_column_sql .= "AND `COLUMN_NAME`=:column_name ";
			$values           = array(":table_schema" => $DBName, ":table_name" => $real_table, ":column_name" => $column_name);
                        $real_column_rs   = $this->runQuery($real_column_sql, $values, 'information_schema');
			$real_column_obj  = $real_column_rs->fetch(PDO::FETCH_OBJ);
 
                        // check that the column exits
                        $num              = $real_column_rs->rowCount();
			if ($num != 1 ) {
                        	// ADD
                                $this->addColumn($real_table, $column_name, $column_type, $is_null);
                        } else {
                        	// Compare
				$compare = $this->compareColumns($template_obj, $real_column_obj);
                                if ( false == $compare[0] ) {
                                	// CHANGE
                                        $this->changeColumn($real_table, $column_name, $column_type, $is_null, $compare);
                                }
                        }
		} //end while
	}

	function addColumn($real_table, $column_name, $column_type, $is_null) {

		// ADD
                $alter_sql = "ALTER TABLE `$real_table` ADD `$column_name` $column_type $is_null NULL";
                $this->runQuery($alter_sql);
		$this->addDisplay("\t :: Adding new/missing column :: $column_name");
		
		//Legacy actions
		array_push($this->summary1,$alter_sql); 

	}

	function changeColumn($real_table, $column_name, $column_type, $is_null, $compare) {

		// CHANGE
                $alter_sql  = "ALTER TABLE `$real_table` CHANGE `$column_name` `$column_name` $column_type ";
                $alter_sql .= " $is_null NULL ".$compare[1];
                $this->runQuery($alter_sql);
		
		//Legacy actions
		array_push($this->summary2,$alter_sql);

		if ( $compare['2'] == "ADD INDEX" ) {
			$alter_sql  = "ALTER TABLE `$real_table` ADD INDEX (`$column_name`) ";
			$this->runQuery($alter_sql);

			//Legacy actions
			array_push($this->summary2,$alter_sql);
		}
		$this->addDisplay("\t :: Changing column :: $column_name");

	}

	function dropColumn($real_table, $column_name) {

                // DROP
                $alter_sql  = "ALTER TABLE `$real_table` DROP `$column_name` ";
                $this->runQuery($alter_sql);
		$this->addDisplay("\t :: Drop column :: $column_name");

		//Legacy actions
                array_push($this->summary2,$alter_sql);
	
        }

	function dropIndex($real_table, $type) {

                // DROP INDEX
                $alter_sql  = "ALTER TABLE `$real_table` DROP $type ";
                $this->runQuery($alter_sql);
		$this->addDisplay("\t :: Drop Index :: $real_table");

		//Legacy actions
                array_push($this->summary2,$alter_sql);

        }

	function _getColumns($_table) {

		$DBName       = $this->DB['DBName'];
		$columns      = array();

		$_column_sql  = "SELECT COLUMN_NAME ";
                $_column_sql .= "FROM `COLUMNS` ";
                $_column_sql .= "WHERE `TABLE_SCHEMA`=:table_schema ";
                $_column_sql .= "AND `TABLE_NAME`=:table_name ";
                $_column_sql .= "ORDER BY `ORDINAL_POSITION` ";
		$values	= array(":table_schema" => $DBName, ":table_name" => $_table);

                $_column_rs   = $this->runQuery($_column_sql, $values, 'information_schema');

                while ( $_column_obj = $_column_rs->fetch(PDO::FETCH_OBJ) ) {
                        array_push($columns, $_column_obj->COLUMN_NAME);
                }
		return $columns;
	}

	function insertNubuilderInfo($real_table, $template_table) {

		$colum_order = array();

		// get columns in correct order so the insert will work
		$real_table_columns 	= $this->_getColumns($real_table);
		$template_table_columns = $this->_getColumns($template_table);

		// remove columns not in template table, so that insert will work
		for ($x = 0; $x < count($real_table_columns); $x++) {
			if ( in_array($real_table_columns[$x], $template_table_columns) ) {	
				array_push($colum_order, $real_table_columns[$x]); 		
			} else {
				
				if ($this->removeColumns == true) {
					$this->dropColumn($real_table, $real_table_columns[$x]);
			
				} else {

					array_push($colum_order, " '' AS ".$real_table_columns[$x]);
					$col  = $real_table_columns[$x];
					$warn = "\t :: Found $col in $real_table that is not used by nuBuilder";
					$this->addDisplay($warn);

					//Legacy actions
					array_push($this->warnings, $warn);
				}
				
			}
		}
                $colum_order2 = implode(", ", $colum_order);

		if ( $this->overrideSetup === false && $real_table == 'zzzsys_setup' ) {
			$this->addDisplay("\t :: Skipping Insert into zzzsys_table");
		} else {

			// do insert
                	$this->addDisplay("\t :: Inserting nuBuilder info into table :: $real_table ");
                	$insert = "INSERT INTO `$real_table` SELECT $colum_order2 FROM `$template_table` ";
                	$this->runQuery($insert);

		}		
	}

	function dropTemplateTable($template_table) {
		
		//Drop template table
                $drop_sql = "DROP TABLE `$template_table`";
                $this->runQuery($drop_sql);
	
	}

	function compareTables() {

		$this->addDisplay("starting compareTables()");

		$DBName       = $this->DB['DBName'];
		$sql          = "SELECT TABLE_NAME FROM TABLES WHERE TABLE_SCHEMA=:table_schema";
		$values	      = array(":table_schema" => $DBName);
                $rs           = $this->runQuery($sql, $values, 'information_schema');

		// loop thru all tables in database
		while ( $obj = $rs->fetch(PDO::FETCH_OBJ) ) {

			$thisTablePrefix                = substr($obj->TABLE_NAME, 0, 15);
                        $this_table_name                = $obj->TABLE_NAME;
			
			// only look at tables with the template_zzzsys_ prefix
			if ($thisTablePrefix == "template_zzzsys") {

				// get both template and real table names
				$template_table = $this_table_name;
				$real_table	= str_replace("template_", "", $this_table_name); 
		
				$this->addDisplay("\n\t $real_table");
	
				// make sure that the real table exists
				$this->makeTable($template_table, $real_table);

				// delete existing nuBuilder info in zzsys tables
				$this->deleteNubuilderInfo($template_table, $real_table);

				// loop thru all columns in the template table
				$this->loopColumns($template_table, $real_table);

				// insert nubuilder data
				$this->insertNubuilderInfo($real_table, $template_table);

				//Drop template table
				$this->dropTemplateTable($template_table);

			}
		}

		$this->addDisplay("completed compareTables()");
	}

	function compareColumns($t_obj, $r_obj) {
		$compare1    = $t_obj->COLUMN_DEFAULT.$t_obj->IS_NULLABLE.$t_obj->COLUMN_TYPE.$t_obj->COLUMN_KEY;
                $compare2    = $r_obj->COLUMN_DEFAULT.$r_obj->IS_NULLABLE.$r_obj->COLUMN_TYPE.$r_obj->COLUMN_KEY;

		$compare1a    = $t_obj->COLUMN_DEFAULT.$t_obj->IS_NULLABLE.$t_obj->COLUMN_TYPE;
                $compare2b    = $r_obj->COLUMN_DEFAULT.$r_obj->IS_NULLABLE.$r_obj->COLUMN_TYPE;

		$result      = array();
		$result[0]   = true;
                $result[1]   = "";
		$result[2]   = "";	
	
		if ($compare1 != $compare2) {
			$this->addDisplay($compare1);
			$this->addDisplay($compare2);
		        if ( $t_obj->COLUMN_KEY == 'PRI' AND $r_obj->COLUMN_KEY != 'PRI'  ) {
				$result[0] = false;
				$result[1] = "PRIMARY KEY"; 
			}
			if ( $t_obj->COLUMN_KEY == 'UNI' AND $r_obj->COLUMN_KEY != 'UNI'  ) {
				$result[0] = false;
                                $result[1] = "UNIQUE";
                        }
			if ( $t_obj->COLUMN_KEY == 'MUL' AND $r_obj->COLUMN_KEY != 'MUL'  ) {
				$result[0] = false;		
                                $result[2] = "ADD INDEX";
                        }
			if ($compare1a != $compare2b) {
				$result[0] = false;
			}
			$this->compareColumnsIndexes($t_obj, $r_obj);
		}
		return $result;
	}

	function compareColumnsIndexes($t_obj, $r_obj) {

		$col = $r_obj->COLUMN_NAME;
		$real_table = $r_obj->TABLE_NAME;

		if ( $this->removeIndexes == true ) {

			if ( $r_obj->COLUMN_KEY == 'PRI' AND $t_obj->COLUMN_KEY != 'PRI'  ) {
				$type = " PRIMARY KEY ";
				$this->dropIndex($real_table, $type);
                        }
                        if ( $r_obj->COLUMN_KEY == 'UNI' AND $t_obj->COLUMN_KEY != 'UNI'  ) {
				$type = " INDEX $col ";
				$this->dropIndex($real_table, $type);
                        }
                        if ( $r_obj->COLUMN_KEY == 'MUL' AND $t_obj->COLUMN_KEY != 'MUL'  ) {
				$type = " INDEX $col ";
				$this->dropIndex($real_table, $type);
                        }

		} else {

			if ( $r_obj->COLUMN_KEY == 'PRI' AND $t_obj->COLUMN_KEY != 'PRI'  ) {
				$warn = "Found $col has Primary Key that is not used by nuBuilder";
                	}
                	if ( $r_obj->COLUMN_KEY == 'UNI' AND $t_obj->COLUMN_KEY != 'UNI'  ) {
				$warn = "Found $col has Unique Index that is not used by nuBuilder";
                	}
                	if ( $r_obj->COLUMN_KEY == 'MUL' AND $t_obj->COLUMN_KEY != 'MUL'  ) {
				$warn = "Found $col has Unique Index that is not used by nuBuilder";
                	}
		}
	}

	function runQuery($sql, $values = array(), $DBName = null) {

		$db  = $this->con;
		$obj = null;

		if ( null == $DBName ) {
			$DBName = $this->DB['DBName'];
                }
                
		try {
			$this->selectDatabase($DBName); //throws Exception	
                        $obj = $db->prepare($sql);
                        $obj->execute($values);


                } catch(Throwable $e) {

			$message          = "\n ->runQuery ".$e->getMessage();
			$message	 .= "\n ->calling function :: ";
			$message	 .= debug_backtrace()[1]['function'];
			$message         .= "\n ->sql :: $sql ";
			
			// Legacy actions
			array_push($this->sqlErrors, array($sql));

			$this->setCodeAndMessage($e->getCode());	
			throw new nuinstallException($message, null, $e);

                } catch(Exception $e) {
		
			$message          = "\n ->runQuery ".$e->getMessage();
			$message         .= "\n ->calling function :: "; 
			$message         .= debug_backtrace()[1]['function'];
			$message         .= "\n ->sql :: $sql ";

			// Legacy actions
                        array_push($this->sqlErrors, array($sql));

			$this->setCodeAndMessage($e->getCode());
			throw new nuinstallException($message, null, $e);

		}		

		return $obj; 
	}

	// Begin Legacy functions
	function showChangeSummary() {
                if ( (count($this->summary1) + count($this->summary2)) > 0 ) {
                        echo "<b>Schema Changes:</b><br>";

                        if ( count($this->summary1) > 0 ) {
                                echo "Column(s) Added<br>";
                                echo "<pre>";
                                print_r($this->summary1);
                                echo "</pre>";
                        }

                        if ( count($this->summary2) > 0 ) {
                                echo "Column(s) Changed<br>";
                                echo "<pre>";
                                print_r($this->summary2);
                                echo "</pre>";
                        }

                } else {
                        echo "<b>No Schema Changes!</b><br>";
                }
        }
        function showSQLerrors() {
                if ( count($this->sqlErrors) > 0 ) {
                        echo "<b>SQL Errors:</b><br>";
                        echo "<pre>";
                        print_r($this->sqlErrors);
                        echo "</pre>";
                } else {
                        echo "<b>No SQL Errors!</b><br>";
                }
        }
        function showWarnings() {

                 if ( count($this->warnings) > 0 ) {
                        echo "<b>Warnings:</b><br>";
                        echo "<pre>";
                        print_r($this->warnings);
                        echo "</pre>";
                } else {
                        echo "<b>No Warnings!</b><br>";
                }
        }
	function returnArrayResults() {
                $result              = array('summary1'=>array(), 'summary2'=>array(), 'sqlErrors'=>array(), 'warnings'=>array() );
                $this->summary1      = $this->addDefaultToArray($this->summary1, 'No Column(s) Added');
                $this->summary2      = $this->addDefaultToArray($this->summary2, 'No Column(s) Changed');
                $this->sqlErrors     = $this->addDefaultToArray($this->sqlErrors, 'No SQL errors');
                $this->warnings      = $this->addDefaultToArray($this->warnings, 'No Warnings');
                $result['summary1']  = $this->summary1;
                $result['summary2']  = $this->summary2;
                $result['sqlErrors'] = $this->sqlErrors;
                $result['warnings']  = $this->warnings;
                return $result;
        }
	function addDefaultToArray($arrayToCheck, $defaultMsg) {
                if ( count($arrayToCheck) == 0 ) {
                        $arrayToCheck[0] = $defaultMsg;
                }
                return $arrayToCheck;
        }
	// End Legacy functions
}
?>
