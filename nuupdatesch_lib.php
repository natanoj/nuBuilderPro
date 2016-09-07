<?php

class nuupdatesch {

        var $sqlErrors       	= array();
	var $tables_updated	= array();
	var $DBHost		= '';			
        var $DBName		= '';
        var $DBUserID		= '';
        var $DBPassWord		= '';
	var $zzsys_only		= true;

	function update() {

		$db	= $this->DBName;
		$sql 	= "SELECT * FROM TABLES WHERE TABLE_SCHEMA=:table_schema ";
		$values = array(":table_schema"=>$db);
		$rs 	= $this->runQuery($sql, $values, 'information_schema');

		while($obj = $rs->fetch(PDO::FETCH_OBJ) ) {

			$thisTablePrefix = substr($obj->TABLE_NAME, 0, 7);

			if ( $this->zzsys_only ) {
				if ( $thisTablePrefix == 'zzzsys_' ) {
					$this->execute($obj->TABLE_NAME);
				}
			} else {
				$this->execute($obj->TABLE_NAME);
			}
		}
	}

	function execute($table) {

		$sql = "ALTER TABLE $table ENGINE = MYISAM";
                $this->runQuery($sql);

		$sql = "ALTER TABLE $table DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ";
                $this->runQuery($sql);

                $sql = "ALTER TABLE $table CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci ";
                $this->runQuery($sql);

                array_push($this->tables_updated, $table);
	}

	function runQuery($sql, $values = array(), $DBName = null) {

		if ( null == $DBName ) {
                        $DBName = $this->DBName;
                }
		$obj = null;

		try {
			$db = new PDO("mysql:host=".$this->DBHost.";dbname=".$this->DBName.";charset=utf8", $this->DBUserID, $this->DBPassWord, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->exec("USE $DBName");
			$obj = $db->prepare($sql);
			$obj->execute($values);

		} catch(Exception $e) {
                        array_push($this->sqlErrors, $sql);
                }
		return $obj;
	}
	
}
?>
