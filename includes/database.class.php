<?php
define('DB_VERSION', 1); // Version of the database that the code expects

class db{
	protected $db_link			= NULL;
	protected $special_values	= array("NOW()", "NULL", "UNIX_TIMESTAMP()");
	protected $query_id			= -1;
	protected $prefix			= '';
	protected $db_username		= '';
	protected $db_password		= '';
	public $show_errors		= true;
		
	function __construct($server, $username, $password, $database='', $prefix=''){
		$this->db_username = $username;
		$this->db_password = $password;
		$this->db_link = @mysql_pconnect($server, $username, $password) or $this->error(mysql_error(), __FILE__, __LINE__, debug_backtrace());
		$this->prefix = $prefix;
		if($database) $this->setdb($database);
	}
	
	function show_errors($bool) {
		$this->show_errors = $bool;
	}
		
	function setdb($database, $prefix=NULL){
		mysql_select_db($database, $this->db_link);
		if($prefix!==NULL) $this->prefix = $prefix;
	}
	
	function setprefix($prefix){
		$this->prefix = $prefix;
	}

	function escape($string){
		return mysql_real_escape_string($string, $this->db_link);
	}

	function query_replace($matches){
		
	}

	function db_exec(/* ... */){
		global $_db_args, $_db_link;
		$_db_link = $this->db_link;
		$_db_args = func_get_args();
		$query = $_db_args[0];
		
		/*
		foreach($args as $num=>$arg){
			if($num==0) continue;
			if($num==(count($args)-1) && $arg===true) continue;

			$arg = str_replace("%", "\\%", $arg);
			$query = preg_replace('/(?<!\\\\)%@('.$num.')/', $arg, $query);
			$e_arg = $this->escape($arg);
			if(!is_numeric($e_arg)) $e_arg = '"' . $e_arg . '"';
			$query = preg_replace('/(?<!\\\\)%('.$num.')/', $e_arg, $query);
			echo $query . " ( " . $e_arg . " ) " . "<br />";
		}
		*/
		$query = preg_replace_callback('/(\\\\?)%([0-9]+)/', 
		create_function('$matches', '
			global $_db_args, $_db_link;
			$backslash = $matches[1];
			$num = $matches[2];
			if($backslash) return "\\%".$num;
			$val = $_db_args[$num];
			$val = mysql_real_escape_string($val, $_db_link);
			if(!is_numeric($val)) $val = \'"\' . $val . \'"\';
			return $val;
		'), $query);
						
		$query = str_replace("\\%", "%", $query);
		
		if($_db_args[count($_db_args)-1]===true)
			return $query;
		
		$queryId = mysql_query($query, $this->db_link) or $this->error(mysql_error() . " <br/>Query: ".$query, __FILE__, __LINE__, debug_backtrace());
		if(is_resource($queryId)) $this->query_id = $queryId;
		return $queryId;
		
	}
	
	function num_rows($sql_num=-1){
		if($sql_num==-1) $sql_num = $this->query_id;
		return mysql_num_rows($sql_num);
	}
	
	function affected_rows(){
		return mysql_affected_rows($this->db_link);
	}
	
	function fetch_assoc($sql_num=-1){
		if($sql_num==-1) $sql_num = $this->query_id;
		return mysql_fetch_assoc($sql_num);
	}
	
	function free_result($sql_num=-1){
		if($sql_num==-1) $sql_num = $this->query_id;
		if(is_resource($sql_num))
			return mysql_free_result($sql_num);
		return false;
	}
	
	function fetch_all_rows($sql_num=-1, $sortKey=''){
		if($sql_num==-1) $sql_num = $this->query_id;
		$ret = array();
		while($line = $this->fetch_assoc($sql_num)){
			if(!$sortKey){
				$ret[] = $line;
			}else{
				if(!isset($ret[$line[$sortKey]])){
					$ret[$line[$sortKey]] = $line;
				}else{
					if(isset($ret[$line[$sortKey]][$sortKey])){
						$temp = $ret[$line[$sortKey]];
						unset($ret[$line[$sortKey]]);
						$ret[$line[$sortKey]][] = $temp;
					}
					$ret[$line[$sortKey]][] = $line;
				}
			}
		}
		return $ret;
	}
	
	function fetch_row($sql_num=-1){
		if($sql_num==-1) $sql_num = $this->query_id;
		if(!is_resource($sql_num)){
			$this->error("Weird error", __FILE__, __LINE__, debug_backtrace());
		}
		return mysql_fetch_row($sql_num);
	}
	
	function fetch_array($sql_num=-1, $result_type=MYSQL_BOTH){
		if($sql_num==-1) $sql_num = $this->query_id;
		return mysql_fetch_array($sql_num, $result_type);
	}
	
	
	function insert_id($sql_num=-1){
		if(is_resource($sql_num) && $sql_num!=-1){
			return mysql_insert_id($sql_num);
		}else{
			return mysql_insert_id();
		}
	}
	
	function free($sql_num=-1){
		if($sql_num==-1) $sql_num = $this->query_id;
		if(!is_resource($sql_num)) return;
		mysql_free_result($sql_num);
	}

	function insert($table, $data=array(), $debug=false){
		$fields = '';
		foreach($data as $field=>$value){
			$field = $this->escape($field);
			$field = str_replace("`", '``', $field);
			$fields[] = "`".$field."`";
			if(!in_array($value, $this->special_values)){
				$value = "'" . $this->escape($value) . "'";
			}
			$values[] = $value;
		} 
		$fields = implode(", ", $fields);
		$values = implode(", ", $values);
		$table = $this->prefix . $table;
		$table = mysql_real_escape_string($table);
		$table = str_replace("`", "``", $table);
		$SQL = "INSERT INTO `{$table}` ({$fields}) VALUES ({$values})";
		$SQL = str_replace("%", "\\%", $SQL);
		return $this->db_exec($SQL, $debug);
	}
	
	function insertorupdate($table, $data=array(), $debug=false){
		$fields = array();
		$values = array();
		foreach($data as $field=>$value){
			$field = $this->escape($field);
			$field = str_replace("`", "``", $field);
			$fields[] =  "`".$field."`";
			
			if(!in_array($value, $this->special_values)) $value = "'" . $this->escape($value) . "'";	
			$values[] = $value;

			$set[] = "`{$field}` = {$value}";
		}
		$set = implode(", ", $set);
		$fieldsInsert = implode(", ", $fields);
		$valuesInsert = implode(", ", $values);
		$table = $this->prefix . $table;
		$table = mysql_real_escape_string($table);
		$table = str_replace("`", "``", $table);
		$SQL = "INSERT INTO `{$table}` ({$fieldsInsert}) VALUES ({$valuesInsert}) ON DUPLICATE KEY UPDATE {$set}";
		$SQL = str_replace("%", "\\%", $SQL);
		return $this->db_exec($SQL, $debug);
	}
	
	function update($table, $data=array(), $where, $debug=false){
		$set = '';
		foreach($data as $field=>$value){
			$field = $this->escape($field);
			$field = str_replace("`", '``', $field);
			
			if(!in_array($value, $this->special_values)) $value = "'" . $this->escape($value) . "'";
			
			$set[] = "`{$field}` = {$value}";
		}
		$table = $this->prefix . $table;
		$table = mysql_real_escape_string($table);
		$table = str_replace("`", "``", $table);
		$set = implode(", ", $set);
		$SQL = "UPDATE {$table} SET {$set} WHERE {$where}";
		$SQL = str_replace("%", "\\%", $SQL);
		return $this->db_exec($SQL, $debug);
	}
	
	function error($err, $file, $line, $backtrace){
		if(!$this->show_errors) return;
		
		if(defined("SITE_ROOT")){
			$file = str_replace(SITE_ROOT, "", $file);
			for($k = 0; $k < count($backtrace); $k++) {
				$backtrace[$k]["file"] = str_replace(SITE_ROOT, "", $backtrace[$k]["file"]);
				foreach($backtrace[$k]["args"] as $argk=>$v){
					$backtrace[$k]["args"][$argk] = str_replace(SITE_ROOT, "", $v);
				}
			}
		}
		$backtrace = print_r($backtrace, true);
		$backtrace = str_replace($this->db_username, "(hidden)", $backtrace);
		$backtrace = str_replace($this->db_password, "(hidden)", $backtrace);
		$err = str_replace($this->db_username, "(hidden)", $err);
		$err = str_replace($this->db_password, "(hidden)", $err);
		
		//echo "<h1>Database Error!</h1>";
		echo "<!--";
		echo "Error: $err\n";
		echo "File: $file\n";
		echo "Line: $line\n";
		echo "Backtrace:\n";
		//echo "<textarea style='width:800px; height:400px' readonly='readonly'>";
		echo $backtrace;
		echo "-->";
		//echo "</textarea>";
		
		echo "<b>We're having some database issues. These should be resolved soon, and we apologize for the inconvience.</b>";
		die();
	}
	
	function getLink(){
		return $this->db_link;
	}
	
	function getLastQuery(){
		return $this->query_id;
	}
	
	function getVersion() {
		$this->db_exec("SELECT value FROM flood_control WHERE setting = 'db_version'");
		list($db_version) = $this->fetch_row();
		if($db_version == NULL) $db_version = 0;
		return (int)$db_version;
	}
	
	function setVersion($version) {
		$this->db_exec("INSERT INTO flood_control (setting, value) VALUES ('db_version', %1) ON DUPLICATE KEY UPDATE value=%1", $version);
	}
}
