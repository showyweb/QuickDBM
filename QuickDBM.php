<?
/**
 * Name:    SHOWYWeb QuickDBM
 * Version: 2.4.0
 * Author:  Novojilov Pavel Andreevich
 * Support: http://SHOWYWEB.ru
 * License: MIT license. http://www.opensource.org/licenses/mit-license.php
 * Copyright (c) 2017 Pavel Novojilov
 */

/**
 *
 */
abstract class qdbm_order
{
    const asc = 1;
    const desc = 2;
    const rand = 3;
}

abstract class qdbm_type_column
{
    const small_string = 'small_string'; //255 len
    const string = "string";
    const int = "integer";
    const unsigned_int = 'unsigned_int';
    const big_int = 'big_int';
    const unsigned_big_int = 'unsigned_big_int';
    const bool = "boolean";
    const datetime = 'datetime';
    const decimal_auto = 'decimal_auto';
}

abstract class qdbm_group_type
{
    const standard = "standard";
    const expand = 'expand';
    const filter = 'filter';
    const all = 'all';
}

abstract class qdbm_filter_type
{
    const string_filter = "string_filter";
    const int_filter = "int_filter";
    const bool_filter = "bool_filter";
    const int_band_filter = "int_band_filter";
    const all = 'all';
}


class qdbm_ext_tools
{
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

        $chr_to_escape_arr = static::utf8_str_split($chr_to_escape);
        $patterns_chr_to_escape = [];
        $code_escape_arr = [];
        foreach ($chr_to_escape_arr as $chr)
            $code_escape_arr[] = "&#" . ord($chr) . ";";

        $chr_to_escape_arr = preg_replace('/(\/|\.|\*|\?|\=|\(|\)|\[|\]|\'|"|\+)/Uui', '\\\$1', $chr_to_escape_arr);
        foreach ($chr_to_escape_arr as $chr) {
            $patterns_chr_to_escape[] = "/$chr/uim";
        }


        $variable = static::remove_nbsp(htmlspecialchars($variable, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $variable = preg_replace($patterns_chr_to_escape, $code_escape_arr, $variable);
        return $variable;
    }

    static function characters_unescape($variable)
    {
        global $chr_to_escape;
        $chr_to_escape_arr = static::utf8_str_split($chr_to_escape);
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

        if(in_array($variable, static::$xss_filtered_arr))
            return $variable;

        $new_variable_for_sql = null;
        if(is_null($variable))
            return null;
        if(is_array($variable)) {
            foreach ($variable as $key => $val) {
                $variable[$key] = static::xss_filter($val);
            }

            return $variable;
        }
        if(!$max_level)
            $variable = static::characters_escape($variable);
        $characters_allowed = "йцукеёнгшщзхъфывапролджэячсмитьбюqwertyuiopasdfghjklzxcvbnm";
        $characters_allowed .= mb_strtoupper($characters_allowed, 'UTF-8') . "1234567890-_" . ($max_level ? "" : ".,&#;@/=") . " ";
        $characters_allowed_arr = static::utf8_str_split($characters_allowed);
        $variable_for_sql_arr = static::utf8_str_split($variable);
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

    static function get_constants_in_class($class_name_or_object)
    {
        $refl = new ReflectionClass($class_name_or_object);
        return $refl->getConstants();

    }

    static function get_static_properties_in_class($class_name_or_object)
    {
        $refl = new ReflectionClass($class_name_or_object);
        return $refl->getStaticProperties();
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

    static function to_datetime($timestamp)
    {
        return date("Y-m-d H:i:s", $timestamp);
    }

    static function to_timestamp($datetime)
    {
        return strtotime($datetime);
    }

    static function decimal_size($value)
    {
        $tmp_int_size = 0;
        $tmp_scale_size = 0;
        $tmp_arr = explode('.', $value);
        $tmp_int_size = qdbm_ext_tools::utf8_strlen($tmp_arr[0]);
        $tmp_scale_size = (count($tmp_arr) == 2) ? qdbm_ext_tools::utf8_strlen($tmp_arr[1]) : 0;
        $tmp_int_size += $tmp_scale_size;
        return [$tmp_int_size, $tmp_scale_size];
    }

    /**
     * @param array|null $res
     * @return mixed|null
     */
    static function first($res)
    {
        return is_null($res) ? $res : $res[0];
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


    private function gen_column($column_name, $sql_function_name_for_column, $magic_quotes = true)
    {
        $column = $column_name;
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

    function equally($column_name, $value, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true, $value_quotes = true, $magic_quotes = true)
    {
        $column = $column_name;
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

    function not_equally($column_name, $value, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true, $value_quotes = true, $magic_quotes = true)
    {
        $column = $column_name;
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

    function more($column_name, $value, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true, $value_quotes = true, $magic_quotes = true)
    {
        $column = $column_name;
        if($xss_filter) {
            $column = qdbm_ext_tools::xss_filter($column);
            $value = qdbm_ext_tools::xss_filter($value);
        }
        $value_quotes = $value_quotes ? "'" : "";
        $this->push($this->gen_column($column, $sql_function_name_for_column, $magic_quotes) . ">$value_quotes$value$value_quotes", $before_where_conjunction);
        return $this;
    }

    function more_or_equally($column_name, $value, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true, $value_quotes = true, $magic_quotes = true)
    {
        $column = $column_name;
        if($xss_filter) {
            $column = qdbm_ext_tools::xss_filter($column);
            $value = qdbm_ext_tools::xss_filter($value);
        }
        $value_quotes = $value_quotes ? "'" : "";
        $this->push($this->gen_column($column, $sql_function_name_for_column, $magic_quotes) . ">=$value_quotes$value$value_quotes", $before_where_conjunction);
        return $this;
    }

    function less($column_name, $value, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true, $value_quotes = true, $magic_quotes = true)
    {
        $column = $column_name;
        if($xss_filter) {
            $column = qdbm_ext_tools::xss_filter($column);
            $value = qdbm_ext_tools::xss_filter($value);
        }
        $value_quotes = $value_quotes ? "'" : "";
        $this->push($this->gen_column($column, $sql_function_name_for_column, $magic_quotes) . "<$value_quotes$value$value_quotes", $before_where_conjunction);
        return $this;
    }

    function less_or_equally($column_name, $value, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true, $value_quotes = true, $magic_quotes = true)
    {
        $column = $column_name;
        if($xss_filter) {
            $column = qdbm_ext_tools::xss_filter($column);
            $value = qdbm_ext_tools::xss_filter($value);
        }
        $value_quotes = $value_quotes ? "'" : "";
        $this->push($this->gen_column($column, $sql_function_name_for_column, $magic_quotes) . "<=$value_quotes$value$value_quotes", $before_where_conjunction);
        return $this;
    }

    function is_null($column_name, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true)
    {
        $column = $column_name;
        if($xss_filter)
            $column = qdbm_ext_tools::xss_filter($column);
        $this->push($this->gen_column($column, $sql_function_name_for_column) . " IS NULL", $before_where_conjunction);
        return $this;
    }

    function is_not_null($column_name, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true)
    {
        $column = $column_name;
        if($xss_filter)
            $column = qdbm_ext_tools::xss_filter($column);
        $this->push($this->gen_column($column, $sql_function_name_for_column) . " IS NOT NULL", $before_where_conjunction);
        return $this;
    }

    function partial_like($column_name, $value, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter = true, $value_quotes = true, $magic_quotes = true)
    {
        $column = $column_name;
        $value_quotes = $value_quotes ? "'" : "";
        if($xss_filter) {
            $column = qdbm_ext_tools::xss_filter($column);
            $value = qdbm_ext_tools::xss_filter($value);
        }
        $this->push($this->gen_column($column, $sql_function_name_for_column, $magic_quotes) . " LIKE $value_quotes%$value%$value_quotes", $before_where_conjunction);
        return $this;
    }

    /**
     * @link https://dev.mysql.com/doc/refman/5.5/en/fulltext-boolean.html
     * @param string $column_name
     * @param $value string Не фильтрует это значение на атаки XSS и SQL инъекции
     * @param bool $before_where_conjunction
     * @param null $sql_function_name_for_column
     * @param bool $xss_filter_column
     * @param bool $value_quotes
     * @param bool $magic_quotes
     * @return $this
     */
    function full_text_search_bm_not_safe($column_name, $value, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter_column = true, $value_quotes = true, $magic_quotes = true)
    {
        $column = $column_name;
        $value_quotes = $value_quotes ? "'" : "";
        if($xss_filter_column)
            $column = qdbm_ext_tools::xss_filter($column);
        $this->push("MATCH (" . $this->gen_column($column, $sql_function_name_for_column, $magic_quotes) . ") AGAINST ( $value_quotes$value$value_quotes  IN BOOLEAN MODE )", $before_where_conjunction);
        return $this;
    }

    /**
     * @param $column_name
     * @param $value string Не фильтрует это значение на атаки XSS и SQL инъекции
     * @param bool $before_where_conjunction
     * @param null $sql_function_name_for_column
     * @param bool $xss_filter_column
     * @param bool $value_quotes
     * @param bool $magic_quotes
     * @return $this
     */
    function regexp_not_safe($column_name, $value, $before_where_conjunction = true, $sql_function_name_for_column = null, $xss_filter_column = true, $value_quotes = true, $magic_quotes = true)
    {
        $column = $column_name;
        $value_quotes = $value_quotes ? "'" : "";
        if($xss_filter_column)
            $column = qdbm_ext_tools::xss_filter($column);
        $this->push($this->gen_column($column, $sql_function_name_for_column, $magic_quotes) . " REGEXP $value_quotes$value$value_quotes", $before_where_conjunction);
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

    function add_column($column_name, $as_column_name = null, $sql_function_name_for_column = null, $column_in_custom_table_name = null, $column_name_xss_filter = true, $column_name_magic_quotes = true)
    {
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

    function __construct($cur_table, $join_table, $column_name_in_current_table, $column_name_in_join_table)
    {
        $join_table = qdbm_ext_tools::xss_filter($join_table);
        $column_name_in_current_table = qdbm_ext_tools::xss_filter($column_name_in_current_table);
        $column_name_in_join_table = qdbm_ext_tools::xss_filter($column_name_in_join_table);
        $this->join .= "LEFT JOIN $join_table ON ($cur_table.$column_name_in_current_table=$join_table.$column_name_in_join_table) ";
        return $this;
    }

    function push_join($cur_table, $join_table, $column_name_in_current_table_or_arr_inf, $column_name_in_join_table_or_arr_inf)
    {
        return $this->__construct($cur_table, $join_table, $column_name_in_current_table_or_arr_inf, $column_name_in_join_table_or_arr_inf);
    }

    function get()
    {
        return $this->join;
    }

}


/**
 * С помощью этого класса описывается структура таблицы.
 *
 * const tab_name задает имя таблицы.
 *
 * Каждая пользовательская константа определяет имя, тип столбца и его индексирование.
 *
 * Имя константы будет равно имени столбца, а значение константы описывает сам столбец и имеет структуру:
 *
 * array('type'=>qdbm_type_column, 'is_xss_filter'=>bool,'is_add_index'=>bool)
 *
 * Ключ type - Тип столбца, устанавливается только при автосоздании столбца, если столбец существует, то значение $value только фильтруется согласно типу.
 *
 * Ключ is_xss_filter - Если true, то фильтр sql/xss включен.
 *
 * Ключ is_add_index - Если true, то при автосоздании столбца, автоматически добавляется индекс sql типа INDEX. Для типа qdbm_type_column::string, sql тип индекса будет FULLTEXT
 *
 * Например:
 *
 * class test_db_c extends qdbm_schema
 * {
 *
 * public $tab_name = "test";
 *
 * const chat_id = array('type' => qdbm_type_column::unsigned_big_int, 'is_xss_filter' => true, 'is_add_index' => true);
 *
 * const key = array('type' => qdbm_type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
 *
 * }
 *
 * Если в имени столбца присутствует префикс v_, то этот столбец будет расцениваться как виртуальный, qdbm его обрабатывать не будет.
 *
 */
abstract class qdbm_schema
{
    /**
     * Имя таблицы
     */
    public $tab_name = "";

    /**
     * id строки
     */
    const id = array('type' => qdbm_type_column::unsigned_big_int, 'is_xss_filter' => true, 'is_add_index' => true);

    /**
     * Индекс порядка сортировки
     */
    const _order = array('type' => qdbm_type_column::unsigned_big_int, 'is_xss_filter' => true, 'is_add_index' => true);

    /**
     * qdbm_schema constructor.
     * @param string $tab_name Если не null, то переопределяет свойство $tab_name
     * @throws exception
     */
    public function __construct($tab_name = null)
    {
        if(!is_null($tab_name))
            $this->tab_name = $tab_name;

        if(empty($this->tab_name))
            qdbm_ext_tools::error("tab_name empty");
        return $this;
    }

    function get_columns()
    {
        $constants = qdbm_ext_tools::get_constants_in_class($this);
        foreach ($constants as $key => $constant) {
            $this->{$key} = $key;
        }
        return $constants;
    }
}


/**
 * @see qdbm_schema для групп и фильтров
 *
 * */
class qdbm_gf_schema extends qdbm_schema
{
    const group_type = array('type' => qdbm_type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
    const filter_type = array('type' => qdbm_type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
    const column_name = array('type' => qdbm_type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
    const parent_id = array('type' => qdbm_type_column::unsigned_int, 'is_xss_filter' => true, 'is_add_index' => true);
    const title = array('type' => qdbm_type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
    const description = array('type' => qdbm_type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
}

class qdbm
{
    private $table = null;
    private $columns = [];
    private static $check_column_table_cache = null;
    private static $active_tables = [];
    private static $write_locked = false;
    private static $write_locked_arr = [];
    private static $mysqli_link = null;
    private static $mysqli_auth = [];
    private static $path_cache = null;
    private static $cache_is_modified = false;

    /**
     * @param array $config = [
     *
     * 'db_name' => $db_name,
     *
     * 'host' => $host,
     *
     * 'user' => $user,
     *
     * 'password' => $password,
     *
     * 'table_prefix' => $table_prefix
     *
     * ]
     */
    static function set_mysqli_auth(array $config)
    {
        static::$mysqli_auth = $config;
    }


    public function __construct(qdbm_schema $qdbm_schema)
    {
        if(is_null(static::$path_cache)) {
            static::$path_cache = $_SERVER['DOCUMENT_ROOT'] . "/.QuickDBM_cache";
            if(!is_dir(static::$path_cache))
                mkdir(static::$path_cache);
            static::$path_cache .= "/cache";
            //            echo static::$path_cache . "\n";
        }
        $this->set_table($qdbm_schema->tab_name);
        if(!in_array($this->table, static::$active_tables))
            static::$active_tables[] = $this->table;
        $columns = $qdbm_schema->get_columns();
        $this->columns = $columns;
        foreach ($columns as $name => $column_inf) {
            if(substr($name, 0, 2) === "v_")
                continue;
            $this->columns[$name]['name'] = $name;
            $type = $column_inf['type'];
            $is_xss_filter = $column_inf['is_xss_filter'];
            $is_add_index = $column_inf['is_add_index'];
            if(!$this->check_column($name)) {
                $this->add_column($name, $type, $is_add_index);
            }
        }
        return $this;
    }


    function __destruct()
    {
        if(static::$cache_is_modified && !is_null(static::$check_column_table_cache)) {
            $str = serialize(static::$check_column_table_cache);
            qdbm_ext_tools::save_to_text_file(static::$path_cache, $str, null);
        }
        static::$check_column_table_cache = null;
    }

    function check_column($column_name)
    {
        if(is_null(static::$check_column_table_cache)) {
            $str = qdbm_ext_tools::open_txt_file(static::$path_cache, null);
            static::$check_column_table_cache = is_null($str) ? [] : unserialize($str);
        }
        if(!isset(static::$check_column_table_cache[$this->table])) {
            static::$check_column_table_cache[$this->table] = [];
            static::$cache_is_modified = true;
        }
        $name = $column_name;
        $link = static::get_mysqli_link();
        $name = qdbm_ext_tools::xss_filter($name);
        if(isset(static::$check_column_table_cache[$this->table][$name]))
            return static::$check_column_table_cache[$this->table][$name];
        $sql = "SHOW COLUMNS FROM `" . $this->table . "` LIKE '" . $name . "'";
        $result = $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error . " sql:" . $sql);
        $itog = $result->fetch_assoc();

        if(is_null($itog))
            return false;
        else
            static::$check_column_table_cache[$this->table][$name] = true;
        static::$cache_is_modified = true;
        return static::$check_column_table_cache[$this->table][$name];
    }

    static function get_mysqli_link()
    {
        $mysqli_link = &static::$mysqli_link;
        if(!is_null($mysqli_link))
            return $mysqli_link;
        $mysqli = &static::$mysqli_auth;
        if(!isset($mysqli["host"]))
            qdbm_ext_tools::error("Не указаны даннае авторизации mysql");
        $mysqli_link = new mysqli($mysqli["host"], $mysqli["user"], $mysqli["password"]);
        if(!$mysqli_link)
            qdbm_ext_tools::error("В настоящее время сервер не может подключиться к базе данных...");
        if(!$mysqli_link) exit(mysqli_error($mysqli_link));
        /* check connection */
        if(mysqli_connect_errno()) {
            qdbm_ext_tools::error("Ошибка подключения: " . mysqli_connect_error());
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
            qdbm_ext_tools::error("Ошибка при загрузке набора символов utf8: " . $mysqli->error);
        $select_status = $mysqli_link->select_db($mysqli["db_name"]);
        if(!$select_status)
            static::set_db_name($mysqli["db_name"], $mysqli_link);
        if(function_exists('mysqlnd_ms_set_qos'))
            mysqlnd_ms_set_qos($mysqli_link, MYSQLND_MS_QOS_CONSISTENCY_EVENTUAL, MYSQLND_MS_QOS_OPTION_AGE, 0);
        return $mysqli_link;
    }

    static function sql_query($sql, $return_array = false)
    {
        $link = static::get_mysqli_link();
        static::$check_column_table_cache = [];
        $result = $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error . " sql:" . $sql);
        if(!$return_array)
            return $result;
        $itog_ = $result->fetch_all(MYSQLI_ASSOC);
        return $itog_;
    }

    static function check_table($check_table)
    {
        if(!is_null(static::$check_column_table_cache) && isset(static::$check_column_table_cache[$check_table]))
            return true;
        $link = static::get_mysqli_link();
        $check_table = qdbm_ext_tools::xss_filter($check_table);
        if(($check_table_res = mysqli_query($link, 'SHOW COLUMNS FROM ' . $check_table)) and isset($check_table_res) and mysqli_fetch_assoc($check_table_res))
            return true;
        else
            return false;
    }

    static function check_db_name($db_name)
    {
        $link = static::get_mysqli_link();
        $db_name = qdbm_ext_tools::xss_filter($db_name);
        $db_name_res = mysqli_query($link, 'SHOW DATABASES LIKE \'' . $db_name . "'");
        if(mysqli_fetch_assoc($db_name_res))
            return true;
        else
            return false;
    }

    static function set_db_name($db_name, $_link = null)
    {
        $link = is_null($_link) ? static::get_mysqli_link() : $_link;
        static::$check_column_table_cache = null;
        $db_name = qdbm_ext_tools::xss_filter($db_name);
        if(!static::check_db_name($db_name)) {
            $sql = "CREATE DATABASE `" . $db_name . "` CHARACTER SET utf8 COLLATE utf8_general_ci";
            $link->query($sql);
            if($link->errno !== 0)
                qdbm_ext_tools::error($link->error . " sql:" . $sql);

        }
        if(!$link->select_db($db_name)) {
            if($link->errno !== 0)
                qdbm_ext_tools::error($link->error);
        }
    }

    private function set_table($table)
    {
        $link = static::get_mysqli_link();
        $table = qdbm_ext_tools::xss_filter($table);
        $table_prefix = static::$mysqli_auth['table_prefix'];
        if(!empty($table_prefix))
            $table = $table_prefix . $table;
        if(!static::check_table($table)) {
            $sql = "CREATE TABLE IF NOT EXISTS `" . $table . "` (`id` bigint(255) unsigned  NOT NULL,`_order` bigint(255) unsigned NOT NULL, UNIQUE `id` (`id`), INDEX `_order` (`_order`))
            ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            $link->query($sql);
            if($link->errno !== 0)
                qdbm_ext_tools::error($link->error . " sql:" . $sql);
        }
        $this->table = $table;
    }

    function get_table_name()
    {
        return $this->table;
    }

    static function remove_table($table)
    {
        $link = static::get_mysqli_link();
        $table = qdbm_ext_tools::xss_filter($table);
        $sql = "DROP TABLE `$table`";
        $result = $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error . " sql:" . $sql);
    }


    function get_raw_type_column($column_name)
    {
        $name = $column_name;
        $link = static::get_mysqli_link();
        $name = qdbm_ext_tools::xss_filter($name);
        $sql = "SHOW COLUMNS FROM `" . $this->table . "` LIKE '" . $name . "'";
        $result = $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error . " sql:" . $sql);
        $itog = $result->fetch_assoc();
        if(is_null($itog))
            return null;
        return $itog['type'];

    }

    function add_column($column_name, $type, $is_add_index)
    {
        $name = $column_name;
        static::$check_column_table_cache = null;
        $sql = '';
        switch ($type) {
            case qdbm_type_column::small_string:
                $sql = "ALTER TABLE `" . $this->table . "`  ADD `" . $name . "` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
                break;
            case qdbm_type_column::string:
                $sql = "ALTER TABLE `" . $this->table . "`  ADD `" . $name . "` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
                break;
            case qdbm_type_column::decimal_auto:
                $tmp_decimal_size = qdbm_ext_tools::decimal_size("1");
                $tmp_int_size = $tmp_decimal_size[0];
                $tmp_scale_size = $tmp_decimal_size[1];
                $sql = "ALTER TABLE `" . $this->table . "` ADD `" . $name . "` DECIMAL($tmp_int_size,$tmp_scale_size) NULL DEFAULT NULL;";
                break;
            case qdbm_type_column::int:
                $sql = "ALTER TABLE `" . $this->table . "` ADD `" . $name . "` INT(255) NULL DEFAULT NULL";
                break;
            case qdbm_type_column::big_int:
                $sql = "ALTER TABLE `" . $this->table . "` ADD `" . $name . "` BIGINT(255) NULL DEFAULT NULL";
                break;
            case qdbm_type_column::unsigned_int:
                $sql = "ALTER TABLE `" . $this->table . "` ADD `" . $name . "` INT(255) unsigned NULL DEFAULT NULL";
                break;
            case qdbm_type_column::unsigned_big_int:
                $sql = "ALTER TABLE `" . $this->table . "` ADD `" . $name . "` BIGINT(255) unsigned NULL DEFAULT NULL";
                break;
            case qdbm_type_column::bool:
                $sql = "ALTER TABLE `" . $this->table . "` ADD `" . $name . "` BOOLEAN NULL DEFAULT NULL";
                break;
            case qdbm_type_column::datetime:
                $sql = "ALTER TABLE `" . $this->table . "` ADD `" . $name . "` DATETIME NULL DEFAULT NULL";
                break;
        }
        if($is_add_index)
            switch ($type) {
                case qdbm_type_column::string:
                    $sql .= ' , ADD FULLTEXT `' . $name . '` (`' . $name . '`)';
                    break;
                default:
                    $sql .= ' , ADD INDEX `' . $name . '` (`' . $name . '`)';
                    break;
            }
        $link = static::get_mysqli_link();
        $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error . " sql:" . $sql);
    }

    function remove_column($column_name)
    {
        $name = $column_name;
        $link = static::get_mysqli_link();
        if(isset(static::$check_column_table_cache[$this->table]) and isset(static::$check_column_table_cache[$this->table][$name]))
            unset(static::$check_column_table_cache[$this->table][$name]);
        $name = qdbm_ext_tools::xss_filter($name);
        $sql = "ALTER TABLE `" . $this->table . "` DROP `" . $name . "`";
        $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error . " sql:" . $sql);
        return true;
    }

    /**
     * @see get_new_insert_id
     */
    function get_nii($is_auto_write_lock = true)
    {
        return $this->get_new_insert_id($is_auto_write_lock);
    }

    /**
     * Возвращает новый id для вставки новой записи
     * @param bool $is_auto_write_lock По умолчанию блокирует другие потоки в базе, чтобы не возник конфликт вставки с одинаковым id
     * @see smart_write_lock
     * @return int
     * @throws exception
     */
    function get_new_insert_id($is_auto_write_lock = true)
    {
        if($is_auto_write_lock)
            $this->smart_write_lock();
        $sql = "SELECT `id` FROM " . $this->table . " ORDER BY `id` DESC LIMIT 0 , 1";
        $link = static::get_mysqli_link();
        $result = $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error . " sql:" . $sql);
        $itog = $result->fetch_assoc();
        if($itog !== null)
            $new_id = $itog["id"] + 1;
        else
            $new_id = 1;
        return $new_id;
    }


    /**
     * @param array $records Список записей в виде ["column_name"=>'value', "column_name2"=>'value2']
     * @param integer $insert_id Идентификатор строки в таблице. Если строка не найдена, то она вставляется как новая. Если параметр $where, не null, то $insert_id игнорируется
     * @param qdbm_where|null $where [optional] Альтернативное условие в запросе, по умолчанию при обновлении записи используется `id`=$insert_id
     * @throws exception
     */
    function insert($records, $insert_id, qdbm_where $where = null)
    {

        $tmp_w_l = static::$write_locked;
        if(!$tmp_w_l)
            $this->smart_write_lock();
        foreach ($records as $key => $value) {
            $column_inf = $this->columns[$key];
            $this->_insert($column_inf, $value, $insert_id, $where);
        }
        if(!$tmp_w_l)
            $this->unlock_tables();
    }

    private function _insert(array $column_inf, $value, $insert_id, qdbm_where $where = null)
    {
        $link = static::get_mysqli_link();
        $id = $insert_id;
        $name = $column_inf['name'];
        $type = $column_inf['type'];
        $is_xss_filter = $column_inf['is_xss_filter'];
        $is_add_index = $column_inf['is_add_index'];

        if(is_null($where)) {
            if(is_null($id) && is_null($id))
                qdbm_ext_tools::error('last_id null');
        }
        $id = qdbm_ext_tools::xss_filter($id);
        $name = qdbm_ext_tools::xss_filter($name);

        switch ($type) {
            case qdbm_type_column::small_string:
            case qdbm_type_column::string:
            case qdbm_type_column::datetime:
            case qdbm_type_column::decimal_auto:
                if($is_xss_filter)
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
                if($is_xss_filter)
                    $value = qdbm_ext_tools::xss_filter($value);
                break;
        }

        if($type == qdbm_type_column::decimal_auto) {
            $tmp_decimal_size = qdbm_ext_tools::decimal_size($value);
            $tmp_int_size = $tmp_decimal_size[0];
            $tmp_scale_size = $tmp_decimal_size[1];
            $raw_type = qdbm::get_raw_type_column($name);
            if(!preg_match('/decimal\((\d+).(\d+)\)/ui', $raw_type, $matches))
                qdbm_ext_tools::error("$name not decimal type");

            $raw_type_int_size = $matches[1];
            $raw_type_scale_size = $matches[2];

            if($tmp_int_size > $raw_type_int_size || $tmp_scale_size > $raw_type_scale_size) {
                if($raw_type_int_size > $tmp_int_size)
                    $tmp_int_size = $raw_type_int_size;
                if($raw_type_scale_size > $tmp_scale_size)
                    $tmp_scale_size = $raw_type_scale_size;
                $sql = "ALTER TABLE `" . $this->table . "` CHANGE `" . $name . "` `" . $name . "` DECIMAL($tmp_int_size,$tmp_scale_size) NULL DEFAULT NULL;";
                $link->query($sql);
                if($link->errno !== 0)
                    qdbm_ext_tools::error($link->error . " sql:" . $sql);
            }
        }
        if($where == null) {
            $sql = "SELECT `id` FROM `" . $this->table . "` WHERE `id` = '" . $id . "'";
            $result = $link->query($sql);
            if($link->errno !== 0)
                qdbm_ext_tools::error($link->error . " sql:" . $sql);
            $itog = $result->fetch_assoc();
            if($itog == null) {
                $tmp_w_l = static::$write_locked;
                if(!$tmp_w_l)
                    $this->smart_write_lock();
                $_order = 0;
                if($id == 1)
                    $_order = 1;
                else {
                    $args = [
                        'order_by' => '_order',
                        'order_method' => qdbm_order::desc,
                        'custom_select_conjunction' => (new qdbm_select_conjunction())->add_column('_order', 'order_max', 'MAX'),
                        'group_by' => '_order'
                    ];
                    $res = static::get_rows($args);
                    $_order = $res[0]['order_max'] + 1;
                }
                $sql = "INSERT INTO `" . $this->table . "` SET `id`='" . $id . "', `_order`='" . $_order . "'";
                $link->query($sql);
                if($link->errno !== 0)
                    qdbm_ext_tools::error($link->error . " sql:" . $sql);
                if(!$tmp_w_l)
                    $this->unlock_tables();
            }

            $sql = "UPDATE `" . $this->table . "` SET `" . $name . "`=? WHERE `id` = '" . $id . "'";
        } else
            $sql = "UPDATE `" . $this->table . "` SET `" . $name . "`=? WHERE " . $where->get();

        $link->stmt_init();
        $stmt = $link->prepare($sql);
        if($stmt->errno !== 0)
            qdbm_ext_tools::error($stmt->error . " sql:" . $sql);
        if($value === 0)
            $value = "0";
        if($value == "")
            $value = null;

        $stmt->bind_param("s", $value);
        $stmt->execute();
        if($stmt->errno !== 0)
            qdbm_ext_tools::error($stmt->error . " sql:" . $sql);
    }

    function remove_rows(qdbm_where $where)
    {
        $link = static::get_mysqli_link();
        $sql = "DELETE FROM `" . $this->table . "` WHERE " . $where->get();
        $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error . " sql:" . $sql);
        return true;
    }

    function get_count(qdbm_where $where = null, $column_name = "id", $is_distinct = false, $magic_quotes = true)
    {
        $link = static::get_mysqli_link();
        $magic_quotes = $magic_quotes ? '`' : '';
        $column_name = $magic_quotes . $column_name . $magic_quotes;
        $sql = "SELECT COUNT(" . ($is_distinct ? "DISTINCT " : "") . "$column_name) AS `_count` FROM `" . $this->table . "`";
        if(!is_null($where) and !is_null($where->get()))
            $sql .= "WHERE " . $where->get();
        $result = $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error . " sql:" . $sql);

        $val = $result->fetch_assoc();
        return intval($val["_count"]);
    }

    /**
     * @param array|null $args
     * @param qdbm_where|null $where
     * @param string|array $order_by
     * @param int $order_method
     * @param int $offset
     * @param int $limit
     * @param qdbm_select_conjunction|null $custom_select_conjunction
     * @param string|null $group_by
     * @param qdbm_left_join_on|null $join
     * @param int|null $group_id_for_join_filters
     * @param bool $is_distinct
     * @return array|null
     * @throws exception
     */
    function get_rows($args = null, qdbm_where $where = null, $order_by = '_order', $order_method = qdbm_order::asc, $offset = 0, $limit = 0, qdbm_select_conjunction $custom_select_conjunction = null, $group_by = null, qdbm_left_join_on $join = null, $group_id_for_join_filters = null, $is_distinct = false)
    {
        if(!is_null($args))
            extract($args);
        $link = static::get_mysqli_link();
        $order_by = qdbm_ext_tools::xss_filter($order_by);
        $group_by = qdbm_ext_tools::xss_filter($group_by);
        $group_id_for_join_filters = qdbm_ext_tools::xss_filter($group_id_for_join_filters);

        if(!is_null($group_id_for_join_filters)) {
            $filters_table = $this->table . "_" . $group_id_for_join_filters . "_filters";
            if(static::check_table($filters_table)) {
                if(is_null($join))
                    $join = new qdbm_left_join_on($this->get_table_name(), $filters_table, 'id', 'id');
                else
                    $join->push_join($this->get_table_name(), $filters_table, 'id', 'id');
            }
        }
        $order_by = ($order_by == null) ? "_order" : $order_by;
        $order_method = ($order_method == null) ? qdbm_order::asc : $order_method;
        $sql = "SELECT " . ($is_distinct ? "DISTINCT " : "") . (is_null($custom_select_conjunction) ? '*' : $custom_select_conjunction->get()) . " FROM `" . $this->table . "` " . (is_null($join) ? '' : $join->get() . ' ') . ((is_null($where) || is_null($where->get())) ? "" : "WHERE " . $where->get()) . " " . (is_null($group_by) ? '' : "GROUP BY $group_by ");
        if(!is_array($order_by))
            $order_by = [$order_by];
        $i = 0;
        foreach ($order_by as $value) {
            $o_prefix = "ORDER BY ";
            if($i !== 0)
                $o_prefix = ", ";

            switch ($order_method) {
                case qdbm_order::asc:
                    $sql .= $o_prefix . "$value";
                    break;
                case qdbm_order::desc:
                    $sql .= $o_prefix . "$value DESC";
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
            qdbm_ext_tools::error($link->error . " sql:" . $sql);
        $itog_ = $result->fetch_all(MYSQLI_ASSOC);
        return count($itog_) ? $itog_ : null;
    }

    function get_unique_vals_in_column($column_name, qdbm_where $where = null, $magic_quotes = true)
    {
        $column_name = qdbm_ext_tools::xss_filter($column_name);
        $magic_quotes = $magic_quotes ? '`' : '';
        $sql = "SELECT DISTINCT $magic_quotes$column_name$magic_quotes FROM `" . $this->table . "`";
        if(!is_null($where))
            $sql .= "WHERE " . $where->get();
        $result = static::sql_query($sql, true);
        return is_null($result) ? null : (isset($result[$column_name]) ? $result[$column_name] : $result[0]);
    }

    function get_min_and_max_in_column($column_name, qdbm_where $where = null, $magic_quotes = true)
    {
        $column_name = qdbm_ext_tools::xss_filter($column_name);
        $magic_quotes = $magic_quotes ? '`' : '';
        $select = new qdbm_select_conjunction();
        $select->add_column('IFNULL(MIN(' . $magic_quotes . $column_name . $magic_quotes . '),0)', "min", null, null, false, false);
        $select->add_column('IFNULL(MAX(' . $magic_quotes . $column_name . $magic_quotes . '),0)', "max", null, null, false, false);
        $res = $this->get_rows(null, $where, null, null, 0, 0, $select);
        $min = $res[0]['min'];
        $max = $res[0]['max'];
        if(is_null($max) or is_null($min))
            return null;
        return array(intval($min), intval($max));
    }


    function format_ids_in_table($id = "id")
    {
        $link = static::get_mysqli_link();
        $id = qdbm_ext_tools::xss_filter($id);
        $sql = "UPDATE `" . $this->table . "` SET `$id`=(SELECT @a:=@a+1 FROM (SELECT @a:=0) i)";
        $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error . " sql:" . $sql);
        return true;
    }

    /**
     * Ставит блокировку типа WRITE на активные таблицы (таблица добавляется в активные при вызове new qdbm)
     *
     * Повторная блокировка запрещена, так как приводит к автоматической предварительной разблокировке таблиц. Если она вам нужна, то предварительно вызовите unlock_tables. Однако, исключение не будет вызываться, если текущая таблица была раннее заблокирована.
     * @see unlock_tables
     * @link https://dev.mysql.com/doc/refman/5.7/en/lock-tables.html
     * @throws exception
     */
    function smart_write_lock()
    {
        if(static::$write_locked) {
            if(in_array($this->table, static::$write_locked_arr))
                return;
            error("Re-lock is forbidden");
        }
        $link = static::get_mysqli_link();
        static::$write_locked = true;
        $tables_str = "";
        foreach (static::$active_tables as $table) {
            static::$write_locked_arr[] = $table;
            $tables_str .= ", $table WRITE";
        }
        $tables_str = substr($tables_str, 2);
        $sql = "LOCK TABLES $tables_str";
        $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error . " sql:" . $sql);
    }

    function unlock_tables()
    {
        $link = static::get_mysqli_link();
        $sql = "UNLOCK TABLES";
        $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error . " sql:" . $sql);
        static::$write_locked = false;
        static::$write_locked_arr = [];
        return true;
    }

    function close_connection()
    {
        $link = static::get_mysqli_link();
        $link->close();
    }

    function move_order($from, $to)
    {
        $link = static::get_mysqli_link();
        $this->smart_write_lock();
        $from = intval($from, 10);
        $to = intval($to, 10);
        if($from == $to)
            return true;
        $where = new qdbm_where();
        $where->equally('_order', $from);
        $where->equally('_order', $to, false);
        $result = $this->get_rows(null, $where);
        if(count($result) != 2)
            return false;
        $ids = array();
        $ids[$result[0]['_order']] = $result[0]['id'];
        $ids[$result[1]['_order']] = $result[1]['id'];
        $sql = "UPDATE `" . $this->table . "` SET ";
        if($to > $from)
            $sql .= "`_order`=`_order`-1 WHERE `_order`>$from AND `_order`<=$to ORDER BY `_order`";
        else
            $sql .= "`_order`=`_order`+1 WHERE `_order`<$from AND `_order`>=$to ORDER BY `_order`";
        $link->query($sql);
        if($link->errno !== 0)
            qdbm_ext_tools::error($link->error . " sql:" . $sql);
        $rec = [
            '_order' => $to
        ];
        $this->insert($rec, $ids[$from]);
        $this->unlock_tables();
        return true;
    }

    function move_orders(array $ids, array $from, array $to)
    {
        $this->smart_write_lock();
        $len = count($ids);

        for ($i = 0; $i < $len; $i++) {
            if($from[$i] != $to[$i]) {
                $where = new qdbm_where();
                $where->equally('id', $ids[$i]);
                $where->equally('_order', $from[$i]);
                if(is_null($this->get_rows(null, $where))) {
                    $this->unlock_tables();
                    return false;
                }

            }
        }
        $rec = [];
        for ($i = 0; $i < $len; $i++) {
            if($from[$i] != $to[$i]) {
                $where = new qdbm_where();
                $where->equally('id', $ids[$i]);
                $where->equally('_order', $from[$i]);
                $rec['_order'] = $to[$i];
                $this->insert($rec, null, $where);
            }
        }

        $this->unlock_tables();
        return true;
    }

    static function import_sql_file($file_name)
    {
        $link = static::get_mysqli_link();
        static::$check_column_table_cache = null;
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

    private function get_gf_db($tab_name = null)
    {
        $table = is_null($tab_name) ? $this->table . "_groups" : $tab_name;
        $db = new qdbm(new qdbm_gf_schema($table));
        return $db;
    }

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

    function add_group($title, $description, $parent_id = 0, $group_type = qdbm_group_type::standard)
    {
        if(!static::type_is_group($group_type)) {
            qdbm_ext_tools::error('Недопустимый тип группы');
            return false;
        }
        if($parent_id != 0) {
            $res = $this->get_group($parent_id);
            if($res[0]['group_type'] == qdbm_group_type::standard and $group_type != qdbm_group_type::filter) {
                qdbm_ext_tools::error('Нельзя добавить подгруппу в стандартную группу');
                return false;
            }
            if($res[0]['group_type'] == qdbm_group_type::expand and $group_type == qdbm_group_type::filter) {
                qdbm_ext_tools::error('Нельзя добавить группу фильтров в разворачиваемую группу');
                return false;
            }
        }
        return $this->group(null, $title, $description, $parent_id, $group_type);
    }

    /** Добавить фильтр
     * @param string $title Заголовок
     * @param string $description Описание
     * @param int $group_id ID группы. Если ID 0, то фильтр будет глобальный
     * @param qdbm_filter_type $filter_type Тип Фильтра
     * @return bool|int|null
     * @throws exception
     */
    function add_filter($title, $description, $group_id = 0, $filter_type, $column_name = null)
    {
        if(!static::type_is_filter($filter_type)) {
            qdbm_ext_tools::error('Недопустимый тип фильтра');
            return false;
        }
        if($group_id) {
            $res = $this->get_group($group_id);
            if($res[0]['group_type'] == qdbm_group_type::expand) {
                qdbm_ext_tools::error('Нельзя добавить фильтр в разворачиваемую группу');
                return false;
            }
        }
        return $this->group(null, $title, $description, $group_id, $filter_type, $column_name);
    }

    /**
     * @param int $obj_id Общий идентификатор
     * @param int $group_id Идентификатор группы типа qdbm_group_type::standard
     * @param array $filers_vals Ассоциативный массив: Имя столбца (column_name) фильтра => Значение
     */
    function save_values_for_filters($obj_id, $group_id = 0, $filers_vals)
    {
        $f_result = $this->get_recursive_filters($group_id);
        $table = $this->table;
        foreach ($f_result as $val) {
            $column_name = $val['column_name'];
            if(isset($filers_vals[$column_name])) {
                $filter_table = $val['parent_id'] ? $table . "_" . $group_id . "_filters" : $table;
                $db = $this->get_gf_db($filter_table);
                $rec = [$column_name => $filers_vals[$column_name]];
                $db->insert($rec, $obj_id);
            }
        }
    }

    function edit_group($id, $title, $description, $parent_id = 0, $force_edit = false)
    {
        $res = $this->get_group($id);
        if(!is_null($res) and !static::type_is_group($res[0]['group_type']))
            qdbm_ext_tools::error("Группы не существует");
        if($res == null and !$force_edit)
            qdbm_ext_tools::error("Группы не существует");
        return $this->group($id, $title, $description, $parent_id, $force_edit ? qdbm_group_type::standard : $res[0]['group_type']);
    }

    private function group($id = null, $title, $description, $parent_id, $group_type, $column_name = null)
    {
        $db = $this->get_gf_db();
        $new_id = $db->get_nii();
        if($id != null)
            $new_id = $id;
        $new_id = intval($new_id);
        $records = [
            'title' => $title,
            'description' => $description,
            'parent_id' => $parent_id,
            'column_name' => ''
        ];

        $column_type = null;
        if(static::type_is_group($group_type))
            $records['group_type'] = $group_type;
        else
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
        $db->insert($records, $new_id);
        if(!is_null($column_type)) {
            if(is_null($column_name))
                $column_name = "filter_" . $new_id;
            $stp_group = $parent_id ? $this->get_stp_group_for_filter($parent_id) : null;
            $records = [
                'group_type' => $group_type,
                'column_name' => $column_name
            ];
            $db->insert($records, $new_id);
            $filter_table = null;
            $table = $this->table;
            if($parent_id) {
                if(is_null($stp_group))
                    qdbm_ext_tools::error('$stp_group==null');
                $fg_id = $stp_group[0]['id'];
                $filter_table = $table . "_" . $fg_id . "_filters";
            } else
                $filter_table = $table;
            $db = $this->get_gf_db($filter_table);
            if(!$db->check_column($column_name))
                $db->add_column($column_name, qdbm_gf_schema::column_name['type'], qdbm_gf_schema::column_name['is_add_index']);
        }
        return $new_id;
    }

    function remove_group($id)
    {

        $id = qdbm_ext_tools::xss_filter($id);
        $group_inf = $this->get_group($id);
        if($group_inf == null)
            qdbm_ext_tools::error("Такой группы не существует");
        $this->remove_group_or_filter($id, $group_inf);
        return true;
    }

    function remove_filter($id)
    {
        $id = qdbm_ext_tools::xss_filter($id);
        $group_inf = $this->get_filter($id);
        if($group_inf == null)
            qdbm_ext_tools::error("Такого фильтра не существует");
        $this->remove_group_or_filter($id, $group_inf);
        return true;
    }

    private function remove_group_or_filter($id, $group_inf = null)
    {
        $id = intval($id);
        if(is_null($group_inf))
            $group_inf = $this->get_group_any_type($id);
        $stp_group_id = 0;
        $filters_table = null;
        if(static::type_is_filter($group_inf[0]['group_type'])) {
            $table = $this->table;
            if($group_inf[0]['parent_id']) {
                $stp_group_id = $this->get_stp_group_for_filter($id)[0]['id'];
                $filters_table = $table . "_" . $stp_group_id . "_filters";
                $db = $this->get_gf_db($filters_table);
                if($db->check_column($group_inf[0]['column_name']))
                    $db->remove_column($group_inf[0]['column_name']);
            }
        }

        $childrens = $this->get_groups(qdbm_order::asc, 0, 0, $id);
        if(is_null($childrens))
            $childrens = array();
        $filters = $this->get_filters(qdbm_order::asc, $id);
        if(is_null($filters))
            $filters = array();
        $childrens = array_merge($childrens, $filters);
        foreach ($childrens as $val) {
            if(!(static::type_is_filter($val['group_type']) and $val['parent_id'] == "0")) {
                $this->remove_group_or_filter($val['id']);
            }

        }

        $db = $this->get_gf_db();
        $where = new qdbm_where();
        $where->equally('id', $id);
        $db->remove_rows($where);
        if($group_inf[0]['parent_id'] and static::type_is_filter($group_inf[0]['qdbm_group_type']) and is_null($this->get_recursive_filters($stp_group_id)))
            static::remove_table($filters_table);
    }

    /**
     * Получить родительскую группу типа qdbm_group_type::standard для фильтра
     * @param int $id Идентификатор фильтра
     * @throws exception
     */
    function get_stp_group_for_filter($id)
    {
        $g_r = $this->get_group_any_type($id);
        if(is_null($g_r))
            qdbm_ext_tools::error('$g_r==null');
        $p_id = $g_r[0]['parent_id'];
        if($g_r[0]['group_type'] != qdbm_group_type::standard)
            return $this->get_stp_group_for_filter($p_id);
        return $g_r;
    }

    private function get_group_any_type($id)
    {
        $db = $this->get_gf_db();
        $new_id = $db->get_nii(false);
        if($new_id == 1) {
            return null;
        }
        $id = qdbm_ext_tools::xss_filter($id);
        $where = new qdbm_where();
        $where->equally('id', $id);
        $result = $db->get_rows(null, $where);
        return $result;
    }

    public function get_group($id)
    {
        $result = $this->get_group_any_type($id);
        if(!is_null($result) and !static::type_is_group($result[0]['group_type']))
            return null;
        return $result;
    }

    public function get_filter($id)
    {
        $result = $this->get_group_any_type($id);
        if(!is_null($result) and !static::type_is_filter($result[0]['filter_type']))
            return null;
        return $result;
    }

    public function get_groups($order = qdbm_order::asc, $offset = 0, $limit = 0, $parent_id = 0, $group_type = qdbm_group_type::all)
    {
        $parent_id = intval($parent_id);
        $db = $this->get_gf_db();
        $new_id = $db->get_nii(false);
        if($new_id == 1) {
            return null;
        }
        $where_main = new qdbm_where();
        $where_main->equally('parent_id', $parent_id);

        if(static::type_is_group($group_type)) {
            $where_main->equally('filter_type', $group_type);
        } elseif($group_type == qdbm_group_type::all) {
            $ext_where = new qdbm_where();
            $group_constants = qdbm_ext_tools::get_constants_in_class('qdbm_group_type');
            $group_constants_len = count($group_constants);
            $i = 0;
            foreach ($group_constants as $type) {
                if($i == $group_constants_len - 1)
                    break;
                $ext_where->equally('group_type', $type, false);
                $i++;
            }
            $where_main->push_where($ext_where);
        }
        $result = $db->get_rows(null, $where_main, null, $order, $offset, $limit);
        return $result;
    }

    public function get_filters($order = qdbm_order::asc, $group_id, $filter_type = qdbm_filter_type::all, $offset = 0, $limit = 0)
    {
        $group_id = intval($group_id);
        $db = $this->get_gf_db();
        $new_id = $db->get_nii(false);
        if($new_id == 1) {
            return null;
        }

        $where_main = new qdbm_where();
        $where_main->equally('parent_id', $group_id);
        $where_main->equally('parent_id', 0, false);
        if(static::type_is_filter($filter_type)) {
            $where_main->equally('filter_type', $filter_type);
        } elseif($filter_type == qdbm_filter_type::all) {
            $ext_where = new qdbm_where();
            $filter_constants = qdbm_ext_tools::get_constants_in_class('qdbm_filter_type');
            $filter_constants_len = count($filter_constants);
            $i = 0;
            foreach ($filter_constants as $type) {
                if($i == $filter_constants_len - 1)
                    break;
                $ext_where->equally('filter_type', $type, false);
                $i++;
            }
            $where_main->push_where($ext_where);
        }
        $result = $db->get_rows(null, $where_main, null, $order, $offset, $limit);

        return $result;
    }

    public function get_recursive_filters($group_id)
    {
        $group_id_arr = array($group_id);
        $fg_result = $this->get_all_recursive_children_group($group_id, qdbm_group_type::filter);
        if(!is_null($fg_result)) {
            foreach ($fg_result as $fg) {
                array_push($group_id_arr, $fg['id']);
            }
        }

        $f_result = array();
        foreach ($group_id_arr as $g_id) {
            $tmp_f_result = $this->get_filters(qdbm_order::asc, $g_id);
            if(!is_null($tmp_f_result))
                $f_result = array_merge($f_result, $tmp_f_result);
        }
        return count($f_result) ? $f_result : null;
    }

    function get_unique_vals_in_filter($filter_id, qdbm_where $where = null, $magic_quotes = true)
    {
        $filter = $this->get_filter($filter_id);
        if($filter[0]['parent_id']) {
            $stp_group_id = $this->get_stp_group_for_filter($filter_id)[0]['id'];
            $filters_table = $this->table . "_" . $stp_group_id . "_filters";
            $db = $this->get_gf_db($filters_table);
        }
        $res = $db->get_unique_vals_in_column($filter[0]['column_name'], $where, $magic_quotes);
        return $res;
    }

    function get_min_and_max_in_filter($filter_id, qdbm_where $where = null, $magic_quotes = true)
    {
        $filter = $this->get_filter($filter_id);
        if($filter[0]['parent_id']) {
            $stp_group_id = $this->get_stp_group_for_filter($filter_id)[0]['id'];
            $filters_table = $this->table . "_" . $stp_group_id . "_filters";
            $db = $this->get_gf_db($filters_table);
        }
        $res = $db->get_min_and_max_in_column($filter[0]['column_name'], $where, $magic_quotes);
        return $res;
    }

    public function group_move_order($from, $to)
    {
        $db = $this->get_gf_db();
        $new_id = $db->get_nii();
        if($new_id == 1) {
            return null;
        }
        $db->move_order($from, $to);
        return true;
    }

    public function group_move_orders(array $ids, array $from, array $to)
    {
        $db = $this->get_gf_db();
        $new_id = $db->get_nii();
        if($new_id == 1) {
            return null;
        }
        $db->move_orders($ids, $from, $to);
        return true;
    }

    public function filter_move_order($from, $to)
    {
        $this->group_move_order($from, $to);
    }

    public function filter_move_orders(array $ids, array $from, array $to)
    {
        $this->group_move_orders($ids, $from, $to);
    }

    public function get_all_parents_group($parent_id)
    {
        return $this->get_all_parents_r($parent_id, array());
    }

    private function get_all_parents_r($parent_id, $out_arr)
    {
        $res = $this->get_group($parent_id);
        array_push($out_arr, $res[0]);
        $parent_id = $res[0]['parent_id'];
        if($parent_id == 0)
            return $out_arr;
        return $this->get_all_parents_r($parent_id, $out_arr);
    }

    public function get_all_recursive_children_group($id, $group_type = qdbm_group_type::all)
    {
        return $this->get_all_recursive_children_group_r($id, array(), $group_type);
    }

    private function get_all_recursive_children_group_r($id, $out_arr, $group_type)
    {
        $res = $this->get_groups(qdbm_order::asc, 0, 0, $id, $group_type);
        if(!is_null($res)) {
            foreach ($res as $val) {
                array_push($out_arr, $val);
                $out_arr = $this->get_all_recursive_children_group_r($val['id'], $out_arr, $group_type);
            }
        }
        return $out_arr;
    }
}