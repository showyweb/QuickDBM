<?
/**
 * Name:    SHOWYWeb QuickDBM
 * Version: 1.0.0
 * Author:  Novojilov Pavel Andreevich
 * Support: http://SHOWYWEB.ru
 * License: MIT license. http://www.opensource.org/licenses/mit-license.php
 * Copyright (c) 2017 Pavel Novojilov
 */

abstract class qdbm_order
{
    const asc = 1;
    const desc = 2;
    const rand = 3;
}

abstract class qdbm_type_column
{
    const small_string = 'SMALL_STRING'; //255 len
    const string = "string";
    const int = "integer";
    const unsigned_int = 'UNSIGNED_INT';
    const big_int = 'BIG_INT';
    const unsigned_big_int = 'UNSIGNED_BIG_INT';
    const bool = "boolean";
    const datetime = 'DATETIME';
    const decimal_auto = 'DECIMAL_AUTO';
    const auto = "auto";
}

abstract class qdbm_group_type
{
    const standard = "STANDARD";
    const expand = 'EXPAND';
    const filter = 'FILTER';
    const all = 'ALL';
}

abstract class qdbm_filter_type
{
    const string_filter = "STRING_FILTER";
    const int_filter = "INT_FILTER";
    const bool_filter = "BOOL_FILTER";
    const int_band_filter = "INT_BAND_FILTER";
    const all = 'ALL';
}

abstract class qdbm_column_names
{
    const id = "id";
    const order = "order_";
    const group_type = "group_type";
    const filter_type = "group_type";
    const filter_column_name = "column_name";
    const parent_id = "parent_id";
    const title = "title";
    const description = "description";
}

class qdbm_ext_tools
{
    static function get_column_name($column_name_or_arr_inf)
    {
        return is_array($column_name_or_arr_inf) ? $column_name_or_arr_inf['name'] : $column_name_or_arr_inf;
    }

    static function remove_nbsp($str)
    {
        return str_replace(array("&nbsp;", chr(194) . chr(160)), array(" ", " "), $str);
    }

    static function utf8_str_split($str)
    {
        $split = 1;
        $array = array();
        for ($i = 0; $i < strlen($str);) {
            $value = ord($str[$i]);
            if($value > 127) {
                if($value >= 192 && $value <= 223)
                    $split = 2;
                elseif($value >= 224 && $value <= 239)
                    $split = 3;
                elseif($value >= 240 && $value <= 247)
                    $split = 4;
            } else {
                $split = 1;
            }
            $key = NULL;
            for ($j = 0; $j < $split; $j++, $i++) {
                $key .= $str[$i];
            }
            array_push($array, $key);
        }
        return $array;
    }

    static $chr_to_escape = "()*°%:+";

    static function characters_escape($variable)
    {
        global $chr_to_escape;

        $chr_to_escape_arr = self::utf8_str_split($chr_to_escape);
        $patterns_chr_to_escape = [];
        $code_escape_arr = [];
        foreach ($chr_to_escape_arr as $chr)
            $code_escape_arr[] = "&#" . ord($chr) . ";";

        $chr_to_escape_arr = preg_replace('/(\/|\.|\*|\?|\=|\(|\)|\[|\]|\'|"|\+)/Uui', '\\\$1', $chr_to_escape_arr);
        foreach ($chr_to_escape_arr as $chr) {
            $patterns_chr_to_escape[] = "/$chr/uim";
        }


        $variable = self::remove_nbsp(htmlspecialchars($variable, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $variable = preg_replace($patterns_chr_to_escape, $code_escape_arr, $variable);
        return $variable;
    }

    static function characters_unescape($variable)
    {
        global $chr_to_escape;
        $chr_to_escape_arr = self::utf8_str_split($chr_to_escape);
        $patterns_chr_to_escape = [];
        $code_escape_arr = [];
        foreach ($chr_to_escape_arr as $chr)
            $code_escape_arr[] = "&#" . ord($chr) . ";";

        $code_escape_arr = preg_replace('/(\/|\.|\*|\?|\=|\(|\)|\[|\]|\'|"|\+)/Uui', '\\\$1', $code_escape_arr);
        foreach ($code_escape_arr as $chr) {
            $patterns_chr_to_escape[] = "/$chr/uim";
        }

        $variable = preg_replace($patterns_chr_to_escape, $chr_to_escape_arr, $variable);
        $variable = htmlspecialchars_decode($variable, ENT_QUOTES | ENT_HTML5);
        return $variable;
    }

    static $xss_filtered_arr = [];

    /**
     * Не фильтрует атаки в css
     * @param string $variable
     * @param bool $max_level
     * @return array|null|string
     */
    static function xss_filter($variable, $max_level = false)
    {
        if(is_int($variable))
            return intval($variable);
        if(is_float($variable))
            return floatval($variable);

        if($variable === "*")
            return $variable;

        if(in_array($variable, self::$xss_filtered_arr))
            return $variable;

        $new_variable_for_sql = null;
        if(is_null($variable))
            return null;
        if(is_array($variable)) {
            foreach ($variable as $key => $val) {
                $variable[$key] = self::xss_filter($val);
            }

            return $variable;
        }
        if(!$max_level)
            $variable = self::characters_escape($variable);
        $characters_allowed = "йцукеёнгшщзхъфывапролджэячсмитьбюqwertyuiopasdfghjklzxcvbnm";
        $characters_allowed .= mb_strtoupper($characters_allowed, 'UTF-8') . "1234567890-_" . ($max_level ? "" : ".,&#;@/=") . " ";
        $characters_allowed_arr = self::utf8_str_split($characters_allowed);
        $variable_for_sql_arr = self::utf8_str_split($variable);
        unset($characters_allowed, $variable_for_sql);
        $variable_for_sql_length = count($variable_for_sql_arr);
        $characters_allowed_length = count($characters_allowed_arr);
        for ($i = 0; $i < $variable_for_sql_length; $i++)
            for ($i2 = 0; $i2 < $characters_allowed_length; $i2++)
                if($variable_for_sql_arr[$i] == $characters_allowed_arr[$i2])
                    $new_variable_for_sql .= $characters_allowed_arr[$i2];
        $new_variable_for_sql = preg_replace('/http(s)?\/\//ui', 'http$1://', $new_variable_for_sql);
        $xss_filtered_arr[] = $new_variable_for_sql;
        return $new_variable_for_sql;
    }

    static function error($mes)
    {
        throw new exception($mes);
    }

    static function get_constants_in_class($class_name)
    {
        $refl = new ReflectionClass($class_name);
        return $refl->getConstants();
    }

    static function utf8_strlen($str)
    {
        return mb_strlen($str, 'UTF-8');
    }

    static function open_txt_file($path, $extn = 'txt')
    {
        $text = "";
        if($extn !== null)
            $path .= '.' . $extn;
        if(!file_exists($path))
            return null;
        $lines = file($path);
        foreach ($lines as $line) {
            if(isset($text))
                $text .= $line;
            else
                $text = $line;
        }
        unset($lines);
        return $text;
    }

    static function save_to_text_file($path, $text, $extn = 'txt')
    {
        if($extn == null)
            $extn = '';
        else
            $extn = '.' . $extn;
        $file = fopen($path . ".tmp", "w");
        if(!$file) {
            return false;
        } else {
            fputs($file, $text);
        }
        fclose($file);
        if(!file_exists($path . ".tmp")) {
            unset($text);
            return false;
        }
        if(sha1($text) == sha1_file($path . ".tmp")) {
            if(file_exists($path . $extn))
                unlink($path . $extn);
            if(!file_exists($path . ".tmp")) {
                unset($text);
                return false;
            }
            rename($path . ".tmp", $path . $extn);
        } else {
            if(!file_exists($path . ".tmp")) {
                unset($text);
                return false;
            }
            unlink($path . ".tmp");
            unset($text);
            return false;
        }
        unset($text);
        return true;
    }

    static function get_current_datetime()
    {
        return date("Y-m-d H:i:s");
    }

    static function timestamp_to_datetime($timestamp)
    {
        return date("Y-m-d H:i:s", $timestamp);
    }

    static function datetime_to_timestamp($datetime)
    {
        return strtotime($datetime);
    }
}


class qdbm_where
{
    private $where = null;

    function __construct()
    {
        return $this;
    }

    function get()
    {
        return $this->where;
    }

    private function push($text, $before_where_conjunction)
    {
        if(is_null($this->where))
            $this->where = $text;
        else
            $this->where .= ($before_where_conjunction ? ' AND ' : ' OR ') . $text;
    }


    private function gen_column($column_name_or_arr_inf, $sql_function_name_for_column, $magic_quotes = true)
    {
        $column = qdbm_ext_tools::get_column_name($column_name_or_arr_inf);
        $magic_quotes = $magic_quotes ? '`' : '';
        $sql_function_name_for_column = qdbm_ext_tools::xss_filter($sql_function_name_for_column);
        return is_null($sql_function_name_for_column) ? $magic_quotes . $column . $magic_quotes : $sql_function_name_for_column . '(' . $magic_quotes . $column . $magic_quotes . ')';
    }


    function push_where(qdbm_where $object, $before_where_conjunction = true)
    {
        $where_text = $object->get();
        if($where_text == "")
            return $this;
        $where_text = '(' . $where_text . ')';
        $this->push($where_text, $before_where_conjunction);
        return $this;
    }

    function equally($column_name_or_arr_inf, $value, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true, $value_quotes = true, $magic_quotes = true)
    {
        $column = qdbm_ext_tools::get_column_name($column_name_or_arr_inf);
        if(gettype($value) == qdbm_type_column::bool)
            $value = $value ? 1 : 0;
        if($xss_filter) {
            $column = qdbm_ext_tools::xss_filter($column);
            $value = qdbm_ext_tools::xss_filter($value);
        }
        $value_quotes = $value_quotes ? "'" : "";
        $this->push($this->gen_column($column, $sql_function_name_for_column, $magic_quotes) . "=$value_quotes$value$value_quotes", $before_where_conjunction);
        return $this;
    }

    function not_equally($column_name_or_arr_inf, $value, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true, $value_quotes = true, $magic_quotes = true)
    {
        $column = qdbm_ext_tools::get_column_name($column_name_or_arr_inf);
        if(gettype($value) == qdbm_type_column::bool)
            $value = $value ? 1 : 0;
        if($xss_filter) {
            $column = qdbm_ext_tools::xss_filter($column);
            $value = qdbm_ext_tools::xss_filter($value);
        }
        $value_quotes = $value_quotes ? "'" : "";
        $this->push($this->gen_column($column, $sql_function_name_for_column, $magic_quotes) . "!=$value_quotes$value$value_quotes", $before_where_conjunction);
        return $this;
    }

    function more($column_name_or_arr_inf, $value, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true, $value_quotes = true, $magic_quotes = true)
    {
        $column = qdbm_ext_tools::get_column_name($column_name_or_arr_inf);
        if($xss_filter) {
            $column = qdbm_ext_tools::xss_filter($column);
            $value = qdbm_ext_tools::xss_filter($value);
        }
        $value_quotes = $value_quotes ? "'" : "";
        $this->push($this->gen_column($column, $sql_function_name_for_column, $magic_quotes) . ">$value_quotes$value$value_quotes", $before_where_conjunction);
        return $this;
    }

    function more_or_equally($column_name_or_arr_inf, $value, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true, $value_quotes = true, $magic_quotes = true)
    {
        $column = qdbm_ext_tools::get_column_name($column_name_or_arr_inf);
        if($xss_filter) {
            $column = qdbm_ext_tools::xss_filter($column);
            $value = qdbm_ext_tools::xss_filter($value);
        }
        $value_quotes = $value_quotes ? "'" : "";
        $this->push($this->gen_column($column, $sql_function_name_for_column, $magic_quotes) . ">=$value_quotes$value$value_quotes", $before_where_conjunction);
        return $this;
    }

    function less($column_name_or_arr_inf, $value, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true, $value_quotes = true, $magic_quotes = true)
    {
        $column = qdbm_ext_tools::get_column_name($column_name_or_arr_inf);
        if($xss_filter) {
            $column = qdbm_ext_tools::xss_filter($column);
            $value = qdbm_ext_tools::xss_filter($value);
        }
        $value_quotes = $value_quotes ? "'" : "";
        $this->push($this->gen_column($column, $sql_function_name_for_column, $magic_quotes) . "<$value_quotes$value$value_quotes", $before_where_conjunction);
        return $this;
    }

    function less_or_equally($column_name_or_arr_inf, $value, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true, $value_quotes = true, $magic_quotes = true)
    {
        $column = qdbm_ext_tools::get_column_name($column_name_or_arr_inf);
        if($xss_filter) {
            $column = qdbm_ext_tools::xss_filter($column);
            $value = qdbm_ext_tools::xss_filter($value);
        }
        $value_quotes = $value_quotes ? "'" : "";
        $this->push($this->gen_column($column, $sql_function_name_for_column, $magic_quotes) . "<=$value_quotes$value$value_quotes", $before_where_conjunction);
        return $this;
    }

    function is_null($column_name_or_arr_inf, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true)
    {
        $column = qdbm_ext_tools::get_column_name($column_name_or_arr_inf);
        if($xss_filter)
            $column = qdbm_ext_tools::xss_filter($column);
        $this->push($this->gen_column($column, $sql_function_name_for_column) . " IS NULL", $before_where_conjunction);
        return $this;
    }

    function is_not_null($column_name_or_arr_inf, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true)
    {
        $column = qdbm_ext_tools::get_column_name($column_name_or_arr_inf);
        if($xss_filter)
            $column = qdbm_ext_tools::xss_filter($column);
        $this->push($this->gen_column($column, $sql_function_name_for_column) . " IS NOT NULL", $before_where_conjunction);
        return $this;
    }

    function partial_like($column_name_or_arr_inf, $value, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true, $value_quotes = true, $magic_quotes = true)
    {
        $column = qdbm_ext_tools::get_column_name($column_name_or_arr_inf);
        $value_quotes = $value_quotes ? "'" : "";
        if($xss_filter) {
            $column = qdbm_ext_tools::xss_filter($column);
            $value = qdbm_ext_tools::xss_filter($value);
        }
        $this->push($this->gen_column($column, $sql_function_name_for_column, $magic_quotes) . " LIKE $value_quotes%$value%$value_quotes", $before_where_conjunction);
        return $this;
    }

}

class qdbm_select_conjunction
{
    private $select = "";

    function __construct()
    {
        return $this;
    }

    function add_column($column_name_or_arr_inf, $as_column_name_or_arr_inf = null, $sql_function_name_for_column = null, $column_in_custom_table_name_or_arr_inf = null, $column_name_xss_filter = true, $column_name_magic_quotes = true)
    {
        $column_name = qdbm_ext_tools::get_column_name($column_name_or_arr_inf);
        $as_column_name = qdbm_ext_tools::get_column_name($as_column_name_or_arr_inf);
        $column_in_custom_table_name = qdbm_ext_tools::get_column_name($column_in_custom_table_name_or_arr_inf);
        $column_name_magic_quotes = $column_name_magic_quotes ? '`' : '';
        if($column_name == "*" or !is_null($sql_function_name_for_column)) $column_name_magic_quotes = "";
        if($column_name_xss_filter)
            $column_name = qdbm_ext_tools::xss_filter($column_name);
        $as_column_name = qdbm_ext_tools::xss_filter($as_column_name);
        $sql_function_name_for_column = qdbm_ext_tools::xss_filter($sql_function_name_for_column);
        $column_in_custom_table_name = qdbm_ext_tools::xss_filter($column_in_custom_table_name);
        $column_name = (is_null($column_in_custom_table_name) ? '' : $column_in_custom_table_name . '.') . $column_name_magic_quotes . $column_name . $column_name_magic_quotes;
        if(!is_null($column_in_custom_table_name)) $column_name_magic_quotes = "";
        $column_name = (is_null($sql_function_name_for_column) ? $column_name_magic_quotes . $column_name . $column_name_magic_quotes : $sql_function_name_for_column . '(' . $column_name_magic_quotes . $column_name . $column_name_magic_quotes . ')') . (is_null($as_column_name) ? '' : ' AS `' . $as_column_name . '`');
        if($this->select != "")
            $this->select .= ", ";
        $this->select .= $column_name;
        return $this;
    }

    function get()
    {
        return $this->select;
    }
}

class qdbm_left_join_on
{
    private $join = "";

    function __construct($join_table, $column_name_in_current_table_or_arr_inf, $column_name_in_join_table_or_arr_inf)
    {
        $column_name_in_current_table = qdbm_ext_tools::get_column_name($column_name_in_current_table_or_arr_inf);
        $column_name_in_join_table = qdbm_ext_tools::get_column_name($column_name_in_join_table_or_arr_inf);
        $cur_table = qdbm::get_table();
        $join_table = qdbm_ext_tools::xss_filter($join_table);
        $column_name_in_current_table = qdbm_ext_tools::xss_filter($column_name_in_current_table);
        $column_name_in_join_table = qdbm_ext_tools::xss_filter($column_name_in_join_table);
        $this->join .= "LEFT JOIN $join_table ON ($cur_table.$column_name_in_current_table=$join_table.$column_name_in_join_table) ";
        return $this;
    }

    function push_join($join_table, $column_name_in_current_table_or_arr_inf, $column_name_in_join_table_or_arr_inf)
    {
        return $this->__construct($join_table, $column_name_in_current_table_or_arr_inf, $column_name_in_join_table_or_arr_inf);
    }

    function get()
    {
        return $this->join;
    }

}

class qdbm
{
    private static $table = null;
    private static $check_column_table_cache = null;
    private static $write_locked = false;
    private static $last_id = null;
    private static $mysqli_link = null;
    private static $mysqli_auth = [];

    /**
     * @param array $config = [
     * 'db_name' => $db_name,
     * 'host' => $host,
     * 'user' => $user,
     * 'password' => $password
     * ]
     */
    static function set_mysqli_auth(array $config)
    {
        self::$mysqli_auth = $config;
    }

    private static function get_mysqli_link()
    {
        $mysqli_link = &self::$mysqli_link;
        if(!is_null($mysqli_link))
            return $mysqli_link;
        $mysqli = &self::$mysqli_auth;
        if(!isset($mysqli["host"]))
            qdbm_ext_tools::error("Не указаны даннае авторизации mysql");
        $mysqli_link = new mysqli($mysqli["host"], $mysqli["user"], $mysqli["password"]);
        if(!$mysqli_link)
            qdbm_ext_tools::error("В настоящее время сервер не может подключиться к базе данных...");
        if(!$mysqli_link) exit(mysqli_error($mysqli_link));
        /* check connection */
        if(mysqli_connect_errno()) {
            qdbm_ext_tools::error("Ошибка подключения: %s\n", mysqli_connect_error());
        }
        mysqli_query($mysqli_link, "set character_set_client	='utf8'");
        mysqli_query($mysqli_link, "set character_set_results='utf8'");
        mysqli_query($mysqli_link, "set collation_connection	='utf8_general_ci'");
        mysqli_query($mysqli_link, "SET lc_time_names='ru_UA'");
        if($stmt = mysqli_prepare($mysqli_link, "set character_set_client=?")) {
            $utf8 = 'utf8';
            mysqli_stmt_bind_param($stmt, "s", $utf8);
            $result = mysqli_stmt_execute($stmt);
        }
        if($stmt = mysqli_prepare($mysqli_link, "set character_set_results=?")) {
            $utf8 = 'utf8';
            mysqli_stmt_bind_param($stmt, "s", $utf8);
            $result = mysqli_stmt_execute($stmt);
        }
        if($stmt = mysqli_prepare($mysqli_link, "set collation_connection=?")) {
            $utf8_general_ci = 'utf8_general_ci';
            mysqli_stmt_bind_param($stmt, "s", $utf8_general_ci);
            $result = mysqli_stmt_execute($stmt);
        }
        if($stmt = mysqli_prepare($mysqli_link, "SET lc_time_names=?")) {
            $utf8_general_ci = 'ru_UA';
            mysqli_stmt_bind_param($stmt, "s", $utf8_general_ci);
            $result = mysqli_stmt_execute($stmt);
        }
        if(!$mysqli_link->set_charset("utf8"))
            qdbm_ext_tools::error("Ошибка при загрузке набора символов utf8: %s\n", $mysqli->error);
        $select_status = $mysqli_link->select_db($mysqli["db_name"]);
        if(!$select_status)
            self::set_db_name($mysqli["db_name"], $mysqli_link);
        if(function_exists('mysqlnd_ms_set_qos'))
            mysqlnd_ms_set_qos($mysqli_link, MYSQLND_MS_QOS_CONSISTENCY_EVENTUAL, MYSQLND_MS_QOS_OPTION_AGE, 0);
        return $mysqli_link;
    }

    static function sql_query($sql, $return_array = false)
    {
        $link = self::get_mysqli_link();
        if(isset(self::$check_column_table_cache[self::$table]))
            unset(self::$check_column_table_cache[self::$table]);
        $result = $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error);
        if(!$return_array)
            return $result;
        $itog_ = $result->fetch_all(MYSQLI_ASSOC);
        return $itog_;
    }

    static function check_table($check_table)
    {
        $link = self::get_mysqli_link();
        $check_table = qdbm_ext_tools::xss_filter($check_table);
        if(($check_table_res = mysqli_query($link, 'SHOW COLUMNS FROM ' . $check_table)) and isset($check_table_res) and mysqli_fetch_assoc($check_table_res))
            return true;
        else
            return false;
    }

    static function check_db_name($db_name)
    {
        $link = self::get_mysqli_link();
        $db_name = qdbm_ext_tools::xss_filter($db_name);
        $db_name_res = mysqli_query($link, 'SHOW DATABASES LIKE \'' . $db_name . "'");
        if(mysqli_fetch_assoc($db_name_res))
            return true;
        else
            return false;
    }

    static function set_db_name($db_name, $_link = null)
    {
        $link = is_null($_link) ? self::get_mysqli_link() : $_link;
        self::$check_column_table_cache = null;
        $db_name = qdbm_ext_tools::xss_filter($db_name);
        if(!self::check_db_name($db_name)) {
            $sql = "CREATE DATABASE `" . $db_name . "` CHARACTER SET utf8 COLLATE utf8_general_ci";
            $link->query($sql);
            if($link->errno !== 0)
                qdbm_ext_tools::error($link->error);

        }
        if(!$link->select_db($db_name)) {
            if($link->errno !== 0)
                qdbm_ext_tools::error($link->error);
        }
    }

    static function dynamic_feth_bind_result($stmt)
    {
        $stmt->store_result();
        $meta = $stmt->result_metadata();
        //print_r($meta);
        $bindResult = '$stmt->bind_result(';
        while ($columnName = $meta->fetch_field()) {
            $columnNames[] = $columnName->name;
            $bindResult .= '$results["' . $columnName->name . '"],';
        }
        $bindResult = rtrim($bindResult, ',') . ');';
        eval($bindResult);
        $i = 0;
        while ($stmt->fetch()) {
            $i2 = 0;
            while (isset($columnNames[$i2]) and isset($results[$columnNames[$i2]])) {
                $itog[$i][$columnNames[$i2]] = $results[$columnNames[$i2]];
                $i2++;
            }
            $i++;
        }
        mysqli_stmt_free_result($stmt);
        mysqli_stmt_reset($stmt);
        mysqli_stmt_close($stmt);
        unset($columnNames, $results, $stmt, $meta, $bindResult);
        if(isset($itog)) {
            return $itog;
            unset($itog);
        } else {
            return false;
        }
    }

    static function set_table($table, $lock_write_in_table = false)
    {
        $link = self::get_mysqli_link();
        $table = qdbm_ext_tools::xss_filter($table);
        if(!self::check_table($table)) {
            $tmp_w_l = $lock_write_in_table or self::$write_locked;
            if($tmp_w_l)
                self::unlock_tables();
            $sql = "CREATE TABLE IF NOT EXISTS `" . $table . "` (`id` bigint(255) unsigned  NOT NULL,`order_` bigint(255) unsigned NOT NULL, UNIQUE `id` (`id`), INDEX `order_` (`order_`))
            ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            $link->query($sql);
            if($link->errno !== 0)
                qdbm_ext_tools::error($link->error);
            if($tmp_w_l)
                self::lock_write_in_table();
        }
        self::$table = $table;
        if($lock_write_in_table or self::$write_locked) {

            self::lock_write_in_table();
        }
        $sql = "SELECT `id` FROM " . $table . " ORDER BY `id` DESC LIMIT 0 , 1";
        $result = $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error);
        $itog = $result->fetch_assoc();
        if($itog !== null)
            $new_id = $itog["id"] + 1;
        else
            $new_id = 1;
        self::$last_id = $new_id;
        return $new_id;
    }

    static function get_table()
    {
        return self::$table;
    }

    static function remove_table($table)
    {
        $link = self::get_mysqli_link();
        $table = qdbm_ext_tools::xss_filter($table);
        $sql = "DROP TABLE `$table`";
        $result = $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error);
    }

    static function check_column($name_or_arr_inf)
    {
        $name = qdbm_ext_tools::get_column_name($name_or_arr_inf);
        $link = self::get_mysqli_link();
        $name = qdbm_ext_tools::xss_filter($name);
        if(!is_null(self::$check_column_table_cache) and isset(self::$check_column_table_cache[self::$table]) and isset(self::$check_column_table_cache[self::$table][$name]))
            return self::$check_column_table_cache[self::$table][$name];
        $sql = "SHOW COLUMNS FROM `" . self::$table . "` LIKE '" . $name . "'";
        $result = $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error);
        $itog = $result->fetch_assoc();

        if($itog == null)
            self::$check_column_table_cache[self::$table][$name] = false;
        else
            self::$check_column_table_cache[self::$table][$name] = true;
        return self::$check_column_table_cache[self::$table][$name];
    }

    static function get_raw_type_column($name_or_arr_inf)
    {

        $name = qdbm_ext_tools::get_column_name($name_or_arr_inf);
        $link = self::get_mysqli_link();
        $name = qdbm_ext_tools::xss_filter($name);
        $sql = "SHOW COLUMNS FROM `" . self::$table . "` LIKE '" . $name . "'";
        $result = $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error);
        $itog = $result->fetch_assoc();
        if(is_null($itog))
            return null;
        return $itog['type'];

    }

    static function remove_column($name_or_arr_inf)
    {
        $name = qdbm_ext_tools::get_column_name($name_or_arr_inf);
        $link = self::get_mysqli_link();
        if(isset(self::$check_column_table_cache[self::$table]) and isset(self::$check_column_table_cache[self::$table][$name]))
            unset(self::$check_column_table_cache[self::$table][$name]);
        $name = qdbm_ext_tools::xss_filter($name);
        $sql = "ALTER TABLE `" . self::$table . "` DROP `" . $name . "`";
        $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error);
        return true;
    }

    static function set_insert_id($id)
    {
        $id = qdbm_ext_tools::xss_filter($id, true);
        self::$last_id = $id;
    }

    /**
     * @param array|string $name_or_arr_inf Имя столбца, если столбца нет, то он будет автоматически создан. Или массив переопределяющий другие параметры со структурой  array('name'=>'', 'type'=>TYPE_DB_COLUMN::AUTO, 'xss_filter_in_value'=>true,'new_column_add_index'=>true)
     * @param array|string $value Вставляемое значение
     * @param integer $id Идентификатор строки в таблице. Если null, то используется последнее используемое значение $id, это значение также устанавливается через set_insert_id($id) и set_db_table($table, $lock_write_in_table = false); Если параметр $where, не null, то $id игнорируется
     * @param qdbm_type_column $type Тип столбца, устанавливается только при автосоздании столбца, если столбец существует, то значение $value только фильтруется согласно типу. Если qdbm_type_column::auto, то string $value = qdbm_type_column::small_string, integer $value = qdbm_type_column::int, boolean $value = qdbm_type_column::bool.
     * @param qdbm_where|null $where [optional] Альтернативное условие в запросе, по умолчанию используется `id`=$id
     * @param bool|true $xss_filter_in_value [optional] Если true, то фильтр sql/xss включен
     * @param bool|true $new_column_add_index [optional] Если true, то при автосоздании столбца, автоматически добавляется индекс sql типа INDEX. Для типа qdbm_type_column::string, sql тип индекса будет FULLTEXT
     * @return bool
     * @throws exception
     */
    static function insert($name_or_arr_inf, $value, $id = null, $type = qdbm_type_column::auto, qdbm_where $where = null, $xss_filter_in_value = true, $new_column_add_index = true)
    {
        $link = self::get_mysqli_link();
        if(is_null($id) and is_null($where)) {
            $id = self::$last_id;
            if(is_null($id))
                qdbm_ext_tools::error('last_id null');
        }
        $id = qdbm_ext_tools::xss_filter($id);
        self::$last_id = $id;
        $name = "";
        if(is_array($name_or_arr_inf)) {
            if(isset($name_or_arr_inf['name'])) $name = $name_or_arr_inf['name'];
            if(isset($name_or_arr_inf['type'])) $type = $name_or_arr_inf['type'];
            if(isset($name_or_arr_inf['xss_filter_in_value'])) $xss_filter_in_value = $name_or_arr_inf['xss_filter_in_value'];
            if(isset($name_or_arr_inf['new_column_add_index'])) $new_column_add_index = $name_or_arr_inf['new_column_add_index'];
        } else
            $name = $name_or_arr_inf;
        $name = qdbm_ext_tools::xss_filter($name);
        $type = ($type == null) ? qdbm_type_column::auto : $type;
        $type = ($type == qdbm_type_column::auto) ? ((gettype($value) == qdbm_type_column::string) ? qdbm_type_column::small_string : gettype($value)) : $type;
        switch ($type) {
            case qdbm_type_column::small_string:
            case qdbm_type_column::string:
            case qdbm_type_column::datetime:
            case qdbm_type_column::decimal_auto:
                if($xss_filter_in_value)
                    $value = qdbm_ext_tools::xss_filter($value);
                break;
            case qdbm_type_column::big_int:
            case qdbm_type_column::int:
            case qdbm_type_column::unsigned_int:
            case qdbm_type_column::unsigned_big_int:
                $value = intval($value, 10);
                break;
            case qdbm_type_column::bool:
                $value = ($value === "1" or $value === "0") ? $value : $value ? "1" : "0";
                break;
            default:
                if($xss_filter_in_value)
                    $value = qdbm_ext_tools::xss_filter($value);
                break;
        }

        $tmp_int_size = 0;
        $tmp_scale_size = 0;
        if($type == qdbm_type_column::decimal_auto) {
            $tmp_arr = explode('.', $value);
            $tmp_int_size = qdbm_ext_tools::utf8_strlen($tmp_arr[0]);
            $tmp_scale_size = (count($tmp_arr) == 2) ? qdbm_ext_tools::utf8_strlen($tmp_arr[1]) : 0;
            $tmp_int_size += $tmp_scale_size;
        }

        if(!self::check_column($name)) {
            self::$check_column_table_cache = null;
            $sql = '';
            switch ($type) {
                case qdbm_type_column::small_string:
                    $sql = "ALTER TABLE `" . self::$table . "`  ADD `" . $name . "` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
                    break;
                case qdbm_type_column::string:
                    $sql = "ALTER TABLE `" . self::$table . "`  ADD `" . $name . "` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
                    break;
                case qdbm_type_column::decimal_auto:
                    $sql = "ALTER TABLE `" . self::$table . "` ADD `" . $name . "` DECIMAL($tmp_int_size,$tmp_scale_size) NULL DEFAULT NULL;";
                    break;
                case qdbm_type_column::int:
                    $sql = "ALTER TABLE `" . self::$table . "` ADD `" . $name . "` INT(255) NULL DEFAULT NULL";
                    break;
                case qdbm_type_column::big_int:
                    $sql = "ALTER TABLE `" . self::$table . "` ADD `" . $name . "` BIGINT(255) NULL DEFAULT NULL";
                    break;
                case qdbm_type_column::unsigned_int:
                    $sql = "ALTER TABLE `" . self::$table . "` ADD `" . $name . "` INT(255) unsigned NULL DEFAULT NULL";
                    break;
                case qdbm_type_column::unsigned_big_int:
                    $sql = "ALTER TABLE `" . self::$table . "` ADD `" . $name . "` BIGINT(255) unsigned NULL DEFAULT NULL";
                    break;
                case qdbm_type_column::bool:
                    $sql = "ALTER TABLE `" . self::$table . "` ADD `" . $name . "` BOOLEAN NULL DEFAULT NULL";
                    break;
                case qdbm_type_column::datetime:
                    $sql = "ALTER TABLE `" . self::$table . "` ADD `" . $name . "` DATETIME NULL DEFAULT NULL";
                    break;
            }
            if($new_column_add_index)
                switch ($type) {
                    case qdbm_type_column::string:
                        $sql .= ' , ADD FULLTEXT `' . $name . '` (`' . $name . '`)';
                        break;
                    default:
                        $sql .= ' , ADD INDEX `' . $name . '` (`' . $name . '`)';
                        break;
                }

            $link->query($sql);
            if($link->errno !== 0)
                qdbm_ext_tools::error($link->error);
        } elseif($type == qdbm_type_column::decimal_auto) {
            $raw_type = qdbm::get_raw_type_column($name);
            if(!preg_match('/decimal\((\d+).(\d+)\)/ui', $type, $matches))
                qdbm_ext_tools::error("$name not decimal type");

            $raw_type_int_size = $matches[1];
            $raw_type_scale_size = $matches[2];

            if($tmp_int_size > $raw_type_int_size || $tmp_scale_size > $raw_type_scale_size) {
                if($raw_type_int_size > $tmp_int_size)
                    $tmp_int_size = $raw_type_int_size;
                if($raw_type_scale_size > $tmp_scale_size)
                    $tmp_scale_size = $raw_type_scale_size;
                $sql = "ALTER TABLE `" . self::$table . "` CHANGE `" . $name . "` `" . $name . "` DECIMAL($tmp_int_size,$tmp_scale_size) NULL DEFAULT NULL;";
                $link->query($sql);
                if($link->errno !== 0)
                    qdbm_ext_tools::error($link->error);
            }
        }
        if($where == null) {
            $sql = "SELECT `id` FROM `" . self::$table . "` WHERE `id` = '" . $id . "'";
            $result = $link->query($sql);
            if($link->errno !== 0)
                qdbm_ext_tools::error($link->error);
            $itog = $result->fetch_assoc();
            if($itog == null) {
                $tmp_w_l = self::$write_locked;
                if(!$tmp_w_l)
                    self::lock_write_in_table();
                $order_ = 0;
                if($id == 1)
                    $order_ = 1;
                else {
                    $res = self::get_rows(null, qdbm_column_names::order, qdbm_order::desc, null, null, (new qdbm_select_conjunction())->add_column(qdbm_column_names::order, 'order_max', 'MAX'), qdbm_column_names::order);
                    $order_ = $res[0]['order_max'] + 1;
                }
                $sql = "INSERT INTO `" . self::$table . "` SET `id`='" . $id . "', `order_`='" . $order_ . "'";
                $link->query($sql);
                if($link->errno !== 0)
                    qdbm_ext_tools::error($link->error);
                if(!$tmp_w_l)
                    self::unlock_tables();
            }

            $sql = "UPDATE `" . self::$table . "` SET `" . $name . "`=? WHERE `id` = '" . $id . "'";
        } else
            $sql = "UPDATE `" . self::$table . "` SET `" . $name . "`=? WHERE " . $where->get();

        $link->stmt_init();
        $stmt = $link->prepare($sql);
        if($stmt->errno !== 0)
            qdbm_ext_tools::error($stmt->error);
        if($value === 0)
            $value = "0";
        if($value == "")
            $value = null;

        $stmt->bind_param("s", $value);
        $stmt->execute();
        if($stmt->errno !== 0)
            qdbm_ext_tools::error($stmt->error);
        return true;
    }

    static function remove_rows(qdbm_where $where)
    {
        $link = self::get_mysqli_link();
        $sql = "DELETE FROM `" . self::$table . "` WHERE " . $where->get();
        $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error);
        return true;
    }

    static function get_rows(qdbm_where $where = null, $order_by = qdbm_column_names::order, $order_method = qdbm_order::asc, $offset = 0, $limit = 0, qdbm_select_conjunction $custom_select_conjunction = null, $group_by = null, qdbm_left_join_on $join = null, $group_id_for_join_filters = null)
    {
        $link = self::get_mysqli_link();
        $order_by = qdbm_ext_tools::xss_filter($order_by);
        $group_by = qdbm_ext_tools::xss_filter($group_by);
        $group_id_for_join_filters = qdbm_ext_tools::xss_filter($group_id_for_join_filters);

        if(!is_null($group_id_for_join_filters)) {
            $filters_table = self::$table . "_" . $group_id_for_join_filters . "_filters";
            if(self::check_table($filters_table)) {
                if(is_null($join))
                    $join = new qdbm_left_join_on($filters_table, qdbm_column_names::id, qdbm_column_names::id);
                else
                    $join->push_join($filters_table, qdbm_column_names::id, qdbm_column_names::id);
            }
        }
        $order_by = ($order_by == null) ? "order_" : $order_by;
        $order_method = ($order_method == null) ? qdbm_order::asc : $order_method;
        $sql = "SELECT " . (is_null($custom_select_conjunction) ? '*' : $custom_select_conjunction->get()) . " FROM `" . self::$table . "` " . (is_null($join) ? '' : $join->get() . ' ') . ((is_null($where) || is_null($where->get())) ? "" : "WHERE " . $where->get()) . " " . (is_null($group_by) ? '' : "GROUP BY `$group_by` ");
        if(!is_array($order_by))
            $order_by = [$order_by];
        $i = 0;
        foreach ($order_by as $value) {
            $o_prefix = "ORDER BY ";
            if($i !== 0)
                $o_prefix = ", ";

            switch ($order_method) {
                case qdbm_order::asc:
                    $sql .= $o_prefix . self::$table . ".`$value`";
                    break;
                case qdbm_order::desc:
                    $sql .= $o_prefix . self::$table . ".`$value` DESC";
                    break;
                case qdbm_order::rand:
                    $sql .= $o_prefix . "rand()";
                    break;
            }
            $i++;
        }

        if($limit != 0) {
            $offset = intval($offset);
            $limit = intval($limit);
            $sql .= " LIMIT " . $offset . "," . $limit;
        } elseif($offset != 0)
            qdbm_ext_tools::error("offset не может быть без limit");
        $result = $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error);
        $itog_ = $result->fetch_all(MYSQLI_ASSOC);
        return count($itog_) ? $itog_ : null;
    }

    public static function get_unique_vals_in_column($column_name_or_arr_inf, qdbm_where $where = null, $magic_quotes = true)
    {
        $column_name = qdbm_ext_tools::get_column_name($column_name_or_arr_inf);
        $column_name = qdbm_ext_tools::xss_filter($column_name);
        $magic_quotes = $magic_quotes ? '`' : '';
        $sql = "SELECT DISTINCT $magic_quotes$column_name$magic_quotes FROM `" . self::$table . "`";
        if(!is_null($where))
            $sql .= "WHERE " . $where->get();
        $result = self::sql_query($sql, true);
        return is_null($result) ? null : (isset($result[$column_name]) ? $result[$column_name] : $result[0]);
    }

    static function get_min_and_max_in_column($column_name_or_arr_inf, qdbm_where $where = null, $magic_quotes = true)
    {
        $column_name = qdbm_ext_tools::get_column_name($column_name_or_arr_inf);
        $column_name = qdbm_ext_tools::xss_filter($column_name);
        $magic_quotes = $magic_quotes ? '`' : '';
        $select = new qdbm_select_conjunction();
        $select->add_column('IFNULL(MIN(' . $magic_quotes . $column_name . $magic_quotes . '),0)', "min", null, null, false, false);
        $select->add_column('IFNULL(MAX(' . $magic_quotes . $column_name . $magic_quotes . '),0)', "max", null, null, false, false);
        $res = self::get_rows($where, null, null, 0, 0, $select);
        $min = $res[0]['min'];
        $max = $res[0]['max'];
        if(is_null($max) or is_null($min))
            return null;
        return array(intval($min), intval($max));
    }

    static function get_count(qdbm_where $where = null)
    {
        $link = self::get_mysqli_link();
        if(self::set_table(self::$table) == 1)
            return 0;
        $sql = "SELECT COUNT(*) FROM `" . self::$table . "`";
        if(!is_null($where) and !is_null($where->get()))
            $sql .= "WHERE " . $where->get();
        $result = $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error);

        $val = $result->fetch_assoc();
        return intval($val["COUNT(*)"]);
    }

    static function format_ids_in_table($id = "id")
    {
        $link = self::get_mysqli_link();
        $id = qdbm_ext_tools::xss_filter($id);
        $sql = "UPDATE `" . self::$table . "` SET `$id`=(SELECT @a:=@a+1 FROM (SELECT @a:=0) i)";
        $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error);
        return true;
    }

    static function lock_write_in_table()
    {
        $link = self::get_mysqli_link();
        self::$write_locked = true;
        $sql = "LOCK TABLES " . self::$table . " WRITE";
        $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error);
        $sql = "SELECT `id` FROM " . self::$table . " ORDER BY `id` DESC LIMIT 0 , 1";
        $result = $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error);
        $itog = $result->fetch_assoc();
        if($itog !== null)
            $new_id = $itog["id"] + 1;
        else
            $new_id = 1;
        return $new_id;
    }

    static function unlock_tables()
    {
        $link = self::get_mysqli_link();
        $sql = "UNLOCK TABLES";
        $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error);
        self::$write_locked = false;
        return true;
    }

    public static function move_order($from, $to)
    {
        $link = self::get_mysqli_link();
        self::lock_write_in_table();
        $from = intval($from, 10);
        $to = intval($to, 10);
        if($from == $to)
            return true;
        $where = new qdbm_where();
        $where->equally('order_', $from);
        $where->equally('order_', $to, false);
        $result = self::get_rows($where);
        if(count($result) != 2)
            return false;
        $ids = array();
        $ids[$result[0]['order_']] = $result[0]['id'];
        $ids[$result[1]['order_']] = $result[1]['id'];
        $sql = "UPDATE `" . self::$table . "` SET ";
        if($to > $from)
            $sql .= "`order_`=`order_`-1 WHERE `order_`>$from AND `order_`<=$to ORDER BY `order_`";
        else
            $sql .= "`order_`=`order_`+1 WHERE `order_`<$from AND `order_`>=$to ORDER BY `order_`";
        $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error);
        self::insert('order_', $to, $ids[$from], qdbm_type_column::big_int);
        self::unlock_tables();
        return true;
    }

    public static function move_orders(array $ids, array $from, array $to)
    {
        self::lock_write_in_table();
        $len = count($ids);

        for ($i = 0; $i < $len; $i++) {
            if($from[$i] != $to[$i]) {
                $where = new qdbm_where();
                $where->equally('id', $ids[$i]);
                $where->equally('order_', $from[$i]);
                if(is_null(self::get_rows($where))) {
                    self::unlock_tables();
                    return false;
                }

            }
        }
        for ($i = 0; $i < $len; $i++) {
            if($from[$i] != $to[$i]) {
                $where = new qdbm_where();
                $where->equally('id', $ids[$i]);
                $where->equally('order_', $from[$i]);
                self::insert('order_', $to[$i], null, qdbm_type_column::unsigned_big_int, $where);
            }
        }

        self::unlock_tables();
        return true;
    }

    static function import_sql_file($file_name)
    {
        $link = self::get_mysqli_link();
        self::$check_column_table_cache = null;
        $sql_text = qdbm_ext_tools::open_txt_file($file_name, null);
        if(is_null($sql_text))
            qdbm_ext_tools::error('error import sql file ' . $file_name);
        $link->multi_query($sql_text);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error);

        do {
            $link->use_result();
        } while ($link->more_results() && $link->next_result());
    }

    //GROUPS ZONE START ------------------------------------------------------

    static function type_is_group($group_type)
    {

        $group_constants = qdbm_ext_tools::get_constants_in_class('qdbm_group_type');
        foreach ($group_constants as $type) {
            if($type == $group_type and $group_type != qdbm_group_type::all)
                return true;
        }
        return false;
    }

    static function type_is_filter($filter_type)
    {
        $filter_constants = qdbm_ext_tools::get_constants_in_class('qdbm_filter_type');
        foreach ($filter_constants as $type) {
            if($type == $filter_type and $filter_type != qdbm_filter_type::all)
                return true;
        }
        return false;
    }

    static function add_group($title, $description, $parent_id = 0, $group_type = qdbm_group_type::standard)
    {
        if(!self::type_is_group($group_type)) {
            qdbm_ext_tools::error('Недопустимый тип группы');
            return false;
        }
        if($parent_id != 0) {
            $res = self::get_group($parent_id);
            if($res[0][qdbm_column_names::group_type] == qdbm_group_type::standard and $group_type != qdbm_group_type::filter) {
                qdbm_ext_tools::error('Нельзя добавить подгруппу в стандартную группу');
                return false;
            }
            if($res[0][qdbm_column_names::group_type] == qdbm_group_type::expand and $group_type == qdbm_group_type::filter) {
                qdbm_ext_tools::error('Нельзя добавить группу фильтров в разворачиваемую группу');
                return false;
            }
        }
        return self::group(null, $title, $description, $parent_id, $group_type);
    }

    /** Добавить фильтр
     * @param string $title Заголовок
     * @param string $description Описание
     * @param int $group_id ID группы. Если ID 0, то фильтр будет глобальный
     * @param qdbm_filter_type $filter_type Тип Фильтра
     * @return bool|int|null
     * @throws exception
     */
    static function add_filter($title, $description, $group_id = 0, $filter_type, $column_name = null)
    {
        if(!self::type_is_filter($filter_type)) {
            qdbm_ext_tools::error('Недопустимый тип фильтра');
            return false;
        }
        if($group_id) {
            $res = self::get_group($group_id);
            if($res[0][qdbm_column_names::group_type] == qdbm_group_type::expand) {
                qdbm_ext_tools::error('Нельзя добавить фильтр в разворачиваемую группу');
                return false;
            }
        }
        return self::group(null, $title, $description, $group_id, $filter_type, $column_name);
    }

    /**
     * @param int $obj_id Общий идентификатор
     * @param int $group_id Идентификатор группы типа GROUP_TYPE::STANDARD
     * @param array $filers_vals Ассоциативный массив: Имя столбца (column_name) фильтра => Значение
     */
    static function save_values_for_filters($obj_id, $group_id = 0, $filers_vals)
    {
        $f_result = self::get_recursive_filters($group_id);
        $table = self::$table;
        foreach ($f_result as $val) {
            $column_name = $val[qdbm_column_names::filter_column_name];
            if(isset($filers_vals[$column_name])) {
                $filter_table = $val[qdbm_column_names::parent_id] ? $table . "_" . $group_id . "_filters" : $table;
                if(self::get_table() != $filter_table)
                    qdbm::set_table($filter_table);
                qdbm::insert($column_name, $filers_vals[$column_name], $obj_id);
            }
        }
        self::$table = $table;
    }

    static function edit_group($id, $title, $description, $parent_id = 0, $force_edit = false)
    {
        $res = self::get_group($id);
        if(!is_null($res) and !self::type_is_group($res[0][qdbm_column_names::group_type]))
            qdbm_ext_tools::error("Группы не существует");
        if($res == null and !$force_edit)
            qdbm_ext_tools::error("Группы не существует");
        return self::group($id, $title, $description, $parent_id, $force_edit ? qdbm_group_type::standard : $res[0]['qdbm_group_type']);
    }

    private static function group($id = null, $title, $description, $parent_id, $group_type, $column_name_or_arr_inf = null)
    {
        $column_name = qdbm_ext_tools::get_column_name($column_name_or_arr_inf);
        $table = self::$table;
        $new_id = self::set_table($table . "_groups");
        if($id != null)
            $new_id = $id;
        $new_id = intval($new_id);
        self::insert("title", $title, $new_id);
        self::insert("description", $description, $new_id);
        self::insert("parent_id", $parent_id, $new_id, qdbm_type_column::unsigned_big_int);
        self::insert("column_name", "", $new_id, qdbm_type_column::small_string);
        $column_type = null;
        if(self::type_is_group($group_type)) {
            self::insert("group_type", $group_type, $new_id, qdbm_type_column::small_string);
        } else
            switch ($group_type) {
                case qdbm_filter_type::bool_filter:
                    $column_type = qdbm_type_column::bool;
                    break;
                case qdbm_filter_type::int_band_filter:
                case qdbm_filter_type::int_filter:
                    $column_type = qdbm_type_column::int;
                    break;
                case qdbm_filter_type::string_filter:
                    $column_type = qdbm_type_column::small_string;
                    break;
            }
        if(!is_null($column_type)) {
            if(is_null($column_name))
                $column_name = "filter_" . $new_id;
            self::$table = $table;
            $stp_group = $parent_id ? self::get_stp_group_for_filter($parent_id) : null;
            self::$table = $table . "_groups";
            self::insert("group_type", $group_type, $new_id, qdbm_type_column::small_string);
            self::insert("column_name", $column_name, $new_id, qdbm_type_column::small_string);
            $filter_table = null;
            if($parent_id) {
                if(is_null($stp_group))
                    qdbm_ext_tools::error('$stp_group==null');
                $fg_id = $stp_group[0][qdbm_column_names::id];
                $filter_table = $table . "_" . $fg_id . "_filters";
            } else
                $filter_table = $table;
            $new_id_filter = self::set_table($filter_table);
            if(!self::check_column($column_name)) {
                self::insert($column_name, null, $new_id_filter, $column_type);
                $where = new qdbm_where();
                $where->equally('id', $new_id_filter);
                self::remove_rows($where);
            }
        }

        self::$table = $table;
        return $new_id;
    }

    public static function remove_group($id)
    {

        $id = qdbm_ext_tools::xss_filter($id);
        $group_inf = self::get_group($id);
        if($group_inf == null)
            qdbm_ext_tools::error("Такой группы не существует");
        self::remove_group_or_filter($id, $group_inf);
        return true;
    }

    public static function remove_filter($id)
    {
        $id = qdbm_ext_tools::xss_filter($id);
        $group_inf = self::get_filter($id);
        if($group_inf == null)
            qdbm_ext_tools::error("Такого фильтра не существует");
        self::remove_group_or_filter($id, $group_inf);
        return true;
    }

    private static function remove_group_or_filter($id, $group_inf = null)
    {
        $id = intval($id);
        if(is_null($group_inf))
            $group_inf = self::get_group_any_type($id);
        $stp_group_id = 0;
        $filters_table = null;
        if(self::type_is_filter($group_inf[0]['qdbm_group_type'])) {
            $table = self::$table;
            if($group_inf[0][qdbm_column_names::parent_id]) {
                $stp_group_id = self::get_stp_group_for_filter($id)[0][qdbm_column_names::id];
                $filters_table = self::$table . "_" . $stp_group_id . "_filters";
                self::set_table($filters_table);
                if(self::check_column($group_inf[0]['column_name']))
                    self::remove_column($group_inf[0]['column_name']);
            }
            self::$table = $table;
        }

        $childrens = self::get_groups(qdbm_order::asc, 0, 0, $id);
        if(is_null($childrens))
            $childrens = array();
        $filters = self::get_filters(qdbm_order::asc, $id);
        if(is_null($filters))
            $filters = array();
        $childrens = array_merge($childrens, $filters);
        foreach ($childrens as $val) {
            if(!(self::type_is_filter($val[qdbm_column_names::group_type]) and $val[qdbm_column_names::parent_id] == "0")) {
                self::remove_group_or_filter($val['id']);
            }

        }

        $table = self::$table;
        self::set_table($table . "_groups");
        $where = new qdbm_where();
        $where->equally('id', $id);
        self::remove_rows($where);
        self::$table = $table;
        if($group_inf[0][qdbm_column_names::parent_id] and self::type_is_filter($group_inf[0]['qdbm_group_type']) and is_null(self::get_recursive_filters($stp_group_id)))
            self::remove_table($filters_table);
    }

    /**
     * Получить родительскую группу типа GROUP_TYPE::STANDARD для фильтра
     * @param int $id Идентификатор фильтра
     * @throws exception
     */
    static function get_stp_group_for_filter($id)
    {
        $g_r = self::get_group_any_type($id);
        if(is_null($g_r))
            qdbm_ext_tools::error('$g_r==null');
        $p_id = $g_r[0][qdbm_column_names::parent_id];
        if($g_r[0][qdbm_column_names::group_type] != qdbm_group_type::standard)
            return self::get_stp_group_for_filter($p_id);
        return $g_r;
    }

    private static function get_group_any_type($id)
    {
        $table = self::$table;
        $new_id = self::set_table($table . "_groups");
        if($new_id == 1) {
            self::$table = $table;
            return null;
        }
        $id = qdbm_ext_tools::xss_filter($id);
        $where = new qdbm_where();
        $where->equally('id', $id);
        $result = self::get_rows($where);
        self::$table = $table;
        return $result;
    }

    public static function get_group($id)
    {
        $result = self::get_group_any_type($id);
        if(!is_null($result) and !self::type_is_group($result[0][qdbm_column_names::group_type]))
            return null;
        return $result;
    }

    public static function get_filter($id)
    {
        $result = self::get_group_any_type($id);
        if(!is_null($result) and !self::type_is_filter($result[0][qdbm_column_names::filter_type]))
            return null;
        return $result;
    }

    public static function get_groups($order = qdbm_order::asc, $offset = 0, $limit = 0, $parent_id = 0, $group_type = qdbm_group_type::all)
    {
        $parent_id = intval($parent_id);
        $table = self::$table;
        $new_id = self::set_table($table . "_groups");
        if($new_id == 1) {
            self::$table = $table;
            return null;
        }
        $where_main = new qdbm_where();
        $where_main->equally('parent_id', $parent_id);

        if(self::type_is_group($group_type)) {
            $where_main->equally(qdbm_column_names::filter_type, $group_type);
        } elseif($group_type == qdbm_group_type::all) {
            $ext_where = new qdbm_where();
            $group_constants = qdbm_ext_tools::get_constants_in_class('qdbm_group_type');
            $group_constants_len = count($group_constants);
            $i = 0;
            foreach ($group_constants as $type) {
                if($i == $group_constants_len - 1)
                    break;
                $ext_where->equally(qdbm_column_names::group_type, $type, false);
                $i++;
            }
            $where_main->push_where($ext_where);
        }
        $result = self::get_rows($where_main, null, $order, $offset, $limit);
        self::$table = $table;
        return $result;
    }

    public static function get_filters($order = qdbm_order::asc, $group_id, $filter_type = qdbm_filter_type::all, $offset = 0, $limit = 0)
    {
        $group_id = intval($group_id);
        $table = self::$table;
        $new_id = self::set_table($table . "_groups");
        if($new_id == 1) {
            self::$table = $table;
            return null;
        }

        $where_main = new qdbm_where();
        $where_main->equally('parent_id', $group_id);
        $where_main->equally('parent_id', 0, false);
        if(self::type_is_filter($filter_type)) {
            $where_main->equally(qdbm_column_names::filter_type, $filter_type);
        } elseif($filter_type == qdbm_filter_type::all) {
            $ext_where = new qdbm_where();
            $filter_constants = qdbm_ext_tools::get_constants_in_class('qdbm_filter_type');
            $filter_constants_len = count($filter_constants);
            $i = 0;
            foreach ($filter_constants as $type) {
                if($i == $filter_constants_len - 1)
                    break;
                $ext_where->equally(qdbm_column_names::filter_type, $type, false);
                $i++;
            }
            $where_main->push_where($ext_where);
        }
        $result = self::get_rows($where_main, null, $order, $offset, $limit);
        self::$table = $table;
        return $result;
    }

    public static function get_recursive_filters($group_id)
    {
        $group_id_arr = array($group_id);
        $fg_result = self::get_all_recursive_children_group($group_id, qdbm_group_type::filter);
        if(!is_null($fg_result)) {
            foreach ($fg_result as $fg) {
                array_push($group_id_arr, $fg[qdbm_column_names::id]);
            }
        }

        $f_result = array();
        foreach ($group_id_arr as $g_id) {
            $tmp_f_result = self::get_filters(qdbm_order::asc, $g_id);
            if(!is_null($tmp_f_result))
                $f_result = array_merge($f_result, $tmp_f_result);
        }
        return count($f_result) ? $f_result : null;
    }

    static function get_unique_vals_in_filter($filter_id, qdbm_where $where = null, $magic_quotes = true)
    {
        $filter = self::get_filter($filter_id);
        $table = self::$table;
        if($filter[0][qdbm_column_names::parent_id]) {
            $stp_group_id = self::get_stp_group_for_filter($filter_id)[0][qdbm_column_names::id];
            $filters_table = self::$table . "_" . $stp_group_id . "_filters";
            self::set_table($filters_table);
        }
        $res = self::get_unique_vals_in_column($filter[0][qdbm_column_names::filter_column_name], $where, $magic_quotes);
        self::$table = $table;
        return $res;
    }

    static function get_min_and_max_in_filter($filter_id, qdbm_where $where = null, $magic_quotes = true)
    {
        $filter = self::get_filter($filter_id);
        $table = self::$table;
        if($filter[0][qdbm_column_names::parent_id]) {
            $stp_group_id = self::get_stp_group_for_filter($filter_id)[0][qdbm_column_names::id];
            $filters_table = self::$table . "_" . $stp_group_id . "_filters";
            self::set_table($filters_table);
        }
        $res = self::get_min_and_max_in_column($filter[0][qdbm_column_names::filter_column_name], $where, $magic_quotes);
        self::$table = $table;
        return $res;
    }

    public static function group_move_order($from, $to)
    {
        $table = self::$table;
        $new_id = self::set_table($table . "_groups");
        if($new_id == 1) {
            self::$table = $table;
            return null;
        }
        self::move_order($from, $to);
        self::$table = $table;
        return true;
    }

    public static function group_move_orders(array $ids, array $from, array $to)
    {
        $table = self::$table;
        $new_id = self::set_table($table . "_groups");
        if($new_id == 1) {
            self::$table = $table;
            return null;
        }
        self::move_orders($ids, $from, $to);
        self::$table = $table;
        return true;
    }

    public static function filter_move_order($from, $to)
    {
        self::group_move_order($from, $to);
    }

    public static function filter_move_orders(array $ids, array $from, array $to)
    {
        self::group_move_orders($ids, $from, $to);
    }

    public static function get_all_parents_group($parent_id)
    {
        return self::get_all_parents_r($parent_id, array());
    }

    private static function get_all_parents_r($parent_id, $out_arr)
    {
        $res = self::get_group($parent_id);
        array_push($out_arr, $res[0]);
        $parent_id = $res[0][qdbm_column_names::parent_id];
        if($parent_id == 0)
            return $out_arr;
        return self::get_all_parents_r($parent_id, $out_arr);
    }

    public static function get_all_recursive_children_group($id, $group_type = qdbm_group_type::all)
    {
        return self::get_all_recursive_children_group_r($id, array(), $group_type);
    }

    private static function get_all_recursive_children_group_r($id, $out_arr, $group_type)
    {
        $res = self::get_groups(qdbm_order::asc, 0, 0, $id, $group_type);
        if(!is_null($res)) {
            foreach ($res as $val) {
                array_push($out_arr, $val);
                $out_arr = self::get_all_recursive_children_group_r($val[qdbm_column_names::id], $out_arr, $group_type);
            }
        }
        return $out_arr;
    }
}