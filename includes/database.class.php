<?php

define('DB_VERSION', 5); // Version of the database that the code expects

class db
{
    protected $db_link = null;
    protected $special_values = array('NOW()', 'NULL', 'UNIX_TIMESTAMP()');
    protected $query_id = -1;
    protected $prefix = '';
    protected $db_username = '';
    protected $db_password = '';
    public $show_errors = true;

    public function __construct($server, $username, $password, $database = '', $prefix = '')
    {
        $this->db_username = $username;
        $this->db_password = $password;
        $this->db_link = @mysql_pconnect($server, $username, $password) or $this->error(mysql_error(), __FILE__, __LINE__, debug_backtrace());
        $this->prefix = $prefix;
        if ($database) {
            $this->setdb($database);
        }
        $this->db_exec('SET NAMES utf8mb4');
    }

    public function show_errors($bool)
    {
        $this->show_errors = $bool;
    }

    public function setdb($database, $prefix = null)
    {
        mysql_select_db($database, $this->db_link);
        if ($prefix !== null) {
            $this->prefix = $prefix;
        }
    }

    public function setprefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function escape($string)
    {
        return mysql_real_escape_string($string, $this->db_link);
    }

    public function query_replace($matches)
    {
    }

    public function db_exec(/* ... */)
    {
        global $_db_args, $_db_link;
        $_db_link = $this->db_link;
        $_db_args = func_get_args();
        $query = $_db_args[0];

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

        $query = str_replace('\\%', '%', $query);

        $queryId = mysql_query($query, $this->db_link) or $this->error(mysql_error().' <br/>Query: '.$query, __FILE__, __LINE__, debug_backtrace());
        if (is_resource($queryId)) {
            $this->query_id = $queryId;
        }

        return $queryId;
    }

    public function num_rows($sql_num = -1)
    {
        if ($sql_num == -1) {
            $sql_num = $this->query_id;
        }

        return mysql_num_rows($sql_num);
    }

    public function affected_rows()
    {
        return mysql_affected_rows($this->db_link);
    }

    public function fetch_assoc($sql_num = -1)
    {
        if ($sql_num == -1) {
            $sql_num = $this->query_id;
        }

        return mysql_fetch_assoc($sql_num);
    }

    public function free_result($sql_num = -1)
    {
        if ($sql_num == -1) {
            $sql_num = $this->query_id;
        }
        if (is_resource($sql_num)) {
            return mysql_free_result($sql_num);
        }

        return false;
    }

    public function fetch_all_rows($sql_num = -1, $sortKey = '')
    {
        if ($sql_num == -1) {
            $sql_num = $this->query_id;
        }
        $ret = array();
        while ($line = $this->fetch_assoc($sql_num)) {
            if (!$sortKey) {
                $ret[] = $line;
            } else {
                if (!isset($ret[$line[$sortKey]])) {
                    $ret[$line[$sortKey]] = $line;
                } else {
                    if (isset($ret[$line[$sortKey]][$sortKey])) {
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

    public function fetch_row($sql_num = -1)
    {
        if ($sql_num == -1) {
            $sql_num = $this->query_id;
        }
        if (!is_resource($sql_num)) {
            $this->error('Weird error', __FILE__, __LINE__, debug_backtrace());
        }

        return mysql_fetch_row($sql_num);
    }

    public function fetch_array($sql_num = -1, $result_type = MYSQL_BOTH)
    {
        if ($sql_num == -1) {
            $sql_num = $this->query_id;
        }

        return mysql_fetch_array($sql_num, $result_type);
    }

    public function insert_id($sql_num = -1)
    {
        if (is_resource($sql_num) && $sql_num != -1) {
            return mysql_insert_id($sql_num);
        } else {
            return mysql_insert_id();
        }
    }

    public function free($sql_num = -1)
    {
        if ($sql_num == -1) {
            $sql_num = $this->query_id;
        }
        if (!is_resource($sql_num)) {
            return;
        }
        mysql_free_result($sql_num);
    }

    public function insert($table, $data = array(), $debug = false)
    {
        $fields = '';
        foreach ($data as $field => $value) {
            $field = $this->escape($field);
            $field = str_replace('`', '``', $field);
            $fields[] = '`'.$field.'`';
            if (!in_array($value, $this->special_values)) {
                $value = "'".$this->escape($value)."'";
            }
            $values[] = $value;
        }
        $fields = implode(', ', $fields);
        $values = implode(', ', $values);
        $table = $this->prefix.$table;
        $table = mysql_real_escape_string($table);
        $table = str_replace('`', '``', $table);
        $SQL = "INSERT INTO `{$table}` ({$fields}) VALUES ({$values})";
        $SQL = str_replace('%', '\\%', $SQL);

        return $this->db_exec($SQL, $debug);
    }

    public function insertorupdate($table, $data = array(), $debug = false)
    {
        $fields = array();
        $values = array();
        foreach ($data as $field => $value) {
            $field = $this->escape($field);
            $field = str_replace('`', '``', $field);
            $fields[] = '`'.$field.'`';

            if (!in_array($value, $this->special_values)) {
                $value = "'".$this->escape($value)."'";
            }
            $values[] = $value;

            $set[] = "`{$field}` = {$value}";
        }
        $set = implode(', ', $set);
        $fieldsInsert = implode(', ', $fields);
        $valuesInsert = implode(', ', $values);
        $table = $this->prefix.$table;
        $table = mysql_real_escape_string($table);
        $table = str_replace('`', '``', $table);
        $SQL = "INSERT INTO `{$table}` ({$fieldsInsert}) VALUES ({$valuesInsert}) ON DUPLICATE KEY UPDATE {$set}";
        $SQL = str_replace('%', '\\%', $SQL);

        return $this->db_exec($SQL, $debug);
    }

    public function update($table, $data = array(), $where, $debug = false)
    {
        $set = '';
        foreach ($data as $field => $value) {
            $field = $this->escape($field);
            $field = str_replace('`', '``', $field);

            if (!in_array($value, $this->special_values)) {
                $value = "'".$this->escape($value)."'";
            }

            $set[] = "`{$field}` = {$value}";
        }
        $table = $this->prefix.$table;
        $table = mysql_real_escape_string($table);
        $table = str_replace('`', '``', $table);
        $set = implode(', ', $set);
        $SQL = "UPDATE {$table} SET {$set} WHERE {$where}";
        $SQL = str_replace('%', '\\%', $SQL);

        return $this->db_exec($SQL, $debug);
    }

    public function error($err, $file, $line, $backtrace)
    {
        if (!$this->show_errors) {
            return;
        }

        if (defined('SITE_ROOT')) {
            $file = str_replace(SITE_ROOT, '', $file);
            for ($k = 0; $k < count($backtrace); ++$k) {
                $backtrace[$k]['file'] = str_replace(SITE_ROOT, '', $backtrace[$k]['file']);
                foreach ($backtrace[$k]['args'] as $argk => $v) {
                    $backtrace[$k]['args'][$argk] = str_replace(SITE_ROOT, '', $v);
                }
            }
        }
        $backtrace = print_r($backtrace, true);
        $backtrace = str_replace($this->db_username, '(hidden)', $backtrace);
        $backtrace = str_replace($this->db_password, '(hidden)', $backtrace);
        $err = str_replace($this->db_username, '(hidden)', $err);
        $err = str_replace($this->db_password, '(hidden)', $err);

        $data = 'Session: '.json_encode($_SESSION)."\n";
        $data .= 'Server: '.json_encode($_SERVER)."\n";
        $data .= 'Request: '.json_encode($_REQUEST)."\n";
        $data .= 'Cookie: '.json_encode($_COOKIE)."\n";
        $data .= "Error: $err\n";
        $data .= "File: $file\n";
        $data .= "Line: $line\n";
        $data .= $backtrace;

        $data = date("r\n").preg_replace('/^/m', "\t", $data);

        file_put_contents(LOG_DIR.'db.log', $data, FILE_APPEND);

        echo "<b>We're having some database issues. These should be resolved soon, and we apologize for the inconvenience. The details have been logged for administrative review.</b>";

        die();
    }

    public function getLink()
    {
        return $this->db_link;
    }

    public function getLastQuery()
    {
        return $this->query_id;
    }

    public function getVersion()
    {
        $this->db_exec("SELECT value FROM flood_control WHERE setting = 'db_version'");
        list($db_version) = $this->fetch_row();
        if ($db_version == null) {
            $db_version = 0;
        }

        return (int) $db_version;
    }

    public function setVersion($version)
    {
        $this->db_exec("INSERT INTO flood_control (setting, value) VALUES ('db_version', %1) ON DUPLICATE KEY UPDATE value=%1", $version);
    }
}
