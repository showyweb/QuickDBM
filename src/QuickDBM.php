<?php
/**
 * Name:    SHOWYWeb QuickDBM
 * Version: 4.5.3
 * Author:  Novojilov Pavel Andreevich
 * Support: https://github.com/showyweb/QuickDBM
 * License: MIT license. http://www.opensource.org/licenses/mit-license.php
 * Copyright (c) 2017 Pavel Novojilov
 */

namespace showyweb\qdbm;

use PDO;
use PDOException;

abstract class order
{
    const asc = 1;
    const desc = 2;
    const rand = 3;
}

abstract class type_column
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

abstract class group_type
{
    const standard = "standard";
    const expand = 'expand';
    const filter = 'filter';
    const all = 'all';
}

abstract class filter_type
{
    const string_filter = "string_filter";
    const str_multiple_filter = 'str_multiple_filter';
    const int_filter = "int_filter";
    const int_multiple_filter = 'int_multiple_filter';
    const bool_filter = "bool_filter";
    const int_band_filter = "int_band_filter";
    const int_multiple_band_filter = "int_multiple_band_filter";
    const datetime_band_filter = 'datetime_band_filter';
    const datetime_multiple_band_filter = 'datetime_multiple_band_filter';

    const all = 'all';
}


class ext_tools
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
            if ($value > 127) {
                if ($value >= 192 && $value <= 223)
                    $split = 2;
                elseif ($value >= 224 && $value <= 239)
                    $split = 3;
                elseif ($value >= 240 && $value <= 247)
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


    private static function prepare_chr_to_escape()
    {
        $chr_to_escape = "()*°%:+";
        $chr_to_escape_arr = static::utf8_str_split($chr_to_escape);
        $patterns_chr_to_escape = [];
        $code_escape_arr = [];
        foreach ($chr_to_escape_arr as $chr)
            $code_escape_arr[] = "&#" . ord($chr) . ";";

        $chr_to_escape_arr = preg_replace('/(\/|\.|\*|\?|\=|\(|\)|\[|\]|\'|"|\+)/Uui', '\\\$1', $chr_to_escape_arr);
        foreach ($chr_to_escape_arr as $chr) {
            $patterns_chr_to_escape[] = "/$chr/uim";
        }
        return ['chr_to_escape_arr'=> $chr_to_escape_arr, 'patterns_chr_to_escape' => $patterns_chr_to_escape, 'code_escape_arr'=> $code_escape_arr];

    }
    static function characters_escape($variable)
    {
        $chr_arr = static::prepare_chr_to_escape();
        extract($chr_arr);
        $variable = static::remove_nbsp(htmlspecialchars($variable, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $variable = preg_replace($patterns_chr_to_escape, $code_escape_arr, $variable);
        return $variable;
    }

    static function characters_unescape($variable)
    {
        $chr_arr = static::prepare_chr_to_escape();
        extract($chr_arr);
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
        if (is_int($variable))
            return intval($variable);
        if (is_float($variable))
            return floatval($variable);

        if ($variable === "*")
            return $variable;

        if (in_array($variable, static::$xss_filtered_arr))
            return $variable;

        $new_variable_for_sql = null;
        if (is_null($variable))
            return null;
        if (is_array($variable)) {
            foreach ($variable as $key => $val) {
                $variable[$key] = static::xss_filter($val);
            }

            return $variable;
        }
        if (!$max_level)
            $variable = static::characters_escape($variable);
        $characters_allowed = "йцукеёнгшщзхъфывапролджэячсмитьбюqwertyuiopasdfghjklzxcvbnm";
        $characters_allowed .= mb_strtoupper($characters_allowed, 'UTF-8') . "1234567890-_" . ($max_level ? "" : ".,&#;@/=" . PHP_EOL) . " ";
        $characters_allowed_arr = static::utf8_str_split($characters_allowed);
        $variable_for_sql_arr = static::utf8_str_split($variable);
        unset($characters_allowed, $variable_for_sql);
        $variable_for_sql_length = count($variable_for_sql_arr);
        $characters_allowed_length = count($characters_allowed_arr);
        for ($i = 0; $i < $variable_for_sql_length; $i++)
            for ($i2 = 0; $i2 < $characters_allowed_length; $i2++)
                if ($variable_for_sql_arr[$i] == $characters_allowed_arr[$i2])
                    $new_variable_for_sql .= $characters_allowed_arr[$i2];
        $new_variable_for_sql = preg_replace('/http(s)?\/\//ui', 'http$1://', $new_variable_for_sql);
        return $new_variable_for_sql;
    }

    static function error($mes)
    {
        throw new \Exception($mes);
    }

    static function get_constants_in_class($class_name_or_object)
    {
        $refl = new \ReflectionClass($class_name_or_object);
        return $refl->getConstants();

    }

    static function get_static_properties_in_class($class_name_or_object)
    {
        $refl = new \ReflectionClass($class_name_or_object);
        return $refl->getStaticProperties();
    }

    static function utf8_strlen($str)
    {
        return mb_strlen($str, 'UTF-8');
    }

    static function open_txt_file($path, $extn = 'txt')
    {
        $text = "";
        if ($extn !== null)
            $path .= '.' . $extn;
        if (!file_exists($path))
            return null;
        $lines = file($path);
        foreach ($lines as $line) {
            if (isset($text))
                $text .= $line;
            else
                $text = $line;
        }
        unset($lines);
        return $text;
    }

    static function save_to_text_file($path, $text, $extn = 'txt')
    {
        if ($extn == null)
            $extn = '';
        else
            $extn = '.' . $extn;
        $file = fopen($path . ".tmp", "w");
        if (!$file) {
            return false;
        } else {
            fputs($file, $text);
        }
        fclose($file);
        if (!file_exists($path . ".tmp")) {
            unset($text);
            return false;
        }
        if (sha1($text) == sha1_file($path . ".tmp")) {
            if (file_exists($path . $extn))
                unlink($path . $extn);
            if (!file_exists($path . ".tmp")) {
                unset($text);
                return false;
            }
            rename($path . ".tmp", $path . $extn);
        } else {
            if (!file_exists($path . ".tmp")) {
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
        $tmp_int_size = ext_tools::utf8_strlen($tmp_arr[0]);
        $tmp_scale_size = (count($tmp_arr) == 2) ? ext_tools::utf8_strlen($tmp_arr[1]) : 0;
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

    static function int_parse($v)
    {
        if (empty($v))
            return $v;
        $v = str_replace(' ', '', (string)$v);
        $v = (string)intval($v);
        return $v;
    }


    /**
     * Возвращает объект where на основе параметров фильтрации
     * @param string|null $filters Список фильтров с параметрами в формате 'column_name/filter_type|value1:value2,value3:value4;column_name2/filter_type|value1:value2,value3:value4'
     *
     * Например: 'id/int_filter|123;realty_type/str_multiple_filter|townhouse:cottage:flat;price/int_band_filter|100:10000;floors/int_multiple_band_filter|2:2,3:3'
     *
     * @param string|null $search Значение, которое будет искаться в столбцах с помощью LIKE %$search%
     * @param array|null $search_columns Названия столбцов, в которых будет производиться поиск, $search
     * @param array|null $map_columns Бывает такая проблема, что некоторые столбцы в sql select переименовываешь через as,
     * но во where то, что объявил с помощью as, не работает.
     * Поэтому через $map_columns делается сопоставление. $map_columns должен быть в таком формате array('новое имя то что через as'=>'то имя, которое подставится во where')
     * @param where|null $where
     * @param callable|null $custom_filters_callback
     * @return where
     */
    static function filter_scope(string $filters = null, string $search = null, array $search_columns = null, array $map_columns = null, where $where = null, callable $custom_filters_callback = null, bool $magic_quotes = true)
    {
        $f_values_type_filter = function (&$f_value, $f_type) {
            foreach ($f_value as $i => $v)
                switch ($f_type) {
                    case filter_type::int_band_filter:
                    case filter_type::int_multiple_band_filter:
                        $f_value[$i] = self::int_parse($v);
                        break;
                    case filter_type::datetime_band_filter:
                    case filter_type::datetime_multiple_band_filter:
                        $f_value[$i] = self::to_datetime(intval($v));
                        break;
                }
        };

        if (!is_array($map_columns))
            $map_columns = [];
        $where = is_null($where) ? new where($magic_quotes) : $where;

        if (!empty($filters)) {
            $filters = explode(';', $filters);
            $filters_kv = [];
            foreach ($filters as $i => $values) {
                if (empty($values)) {
                    continue;
                }

                $values = explode('|', $values);
                $filter = $values[0];
                $values = $values[1];
                if (is_null($values)) {
                    continue;
                }
                $filters_kv[$filter] = $values;
            }
            foreach ($filters_kv as $key => $value) {
                $f_info = explode('/', $key);
                if (empty($f_info[1])) {
                    continue;
                }

                if (!in_array($f_info[1], [filter_type::int_multiple_band_filter, filter_type::datetime_multiple_band_filter])) {
                    $filters2 = explode(',', $key);
                    $values = explode(',', $value);
                } else {
                    $filters2[] = $key;
                    $values[] = $value;
                }

                foreach ($filters2 as $index => $f_inf) {
                    $f_inf = explode('/', $f_inf);
                    $f_name = $f_inf[0];
                    $f_name_as = $f_name;
                    if (isset($map_columns[$f_name]))
                        $f_name = $map_columns[$f_name];
                    $f_type = $f_inf[1];
                    $f_value = $values[$index];
                    $f_where = new where($magic_quotes);

                    switch ($f_type) {
                        case filter_type::bool_filter:
                        case filter_type::int_filter:
                            $f_value = self::int_parse($f_value);
                            $f_where->equally($f_name, $f_value);
                            if (empty($f_value))
                                $f_where->is_null($f_name, false);
                            break;
                        case filter_type::int_multiple_filter:
                        case filter_type::str_multiple_filter:
                            $f_value = explode(':', $f_value);
                            if ($f_type === filter_type::int_multiple_filter)
                                foreach ($f_value as $i => $v)
                                    $f_value[$i] = self::int_parse($v);
                            $f_where->in($f_name, $f_value);
                            break;
                        case filter_type::int_band_filter:
                        case filter_type::datetime_band_filter:
                            $f_value = explode(':', $f_value);
                            $f_values_type_filter($f_value, $f_type);
                            $s_f_where = new where($magic_quotes);
                            $is_xss_filter = $f_type !== filter_type::datetime_band_filter;
                            if ($f_value[0] !== '')
                                $s_f_where->more_or_equally($f_name, $f_value[0], true, null, $is_xss_filter);
                            if (isset($f_value[1]) && $f_value[1] !== '')
                                $s_f_where->less_or_equally($f_name, $f_value[1], true, null, $is_xss_filter);
                            $f_where->push_where($s_f_where);
                            if ($f_value[0] === '' || intval($f_value[0]) === 0)
                                $f_where->is_null($f_name, false);
                            break;
                        case filter_type::int_multiple_band_filter:
                        case filter_type::datetime_multiple_band_filter:
                            $is_xss_filter = $f_type !== filter_type::datetime_multiple_band_filter;
                            $f_values = explode(',', $f_value);
                            $s_f_where = new where($magic_quotes);
                            foreach ($f_values as $f_value) {
                                $f_value = explode(':', $f_value);
                                $f_values_type_filter($f_value, $f_type);
                                $s_f_where2 = new where($magic_quotes);
                                if ($f_value[0] !== '')
                                    $s_f_where2->more_or_equally($f_name, $f_value[0], true, null, $is_xss_filter);
                                if ($f_value[1] !== '')
                                    $s_f_where2->less_or_equally($f_name, $f_value[0], true, null, $is_xss_filter);
                                $s_f_where->push_where($s_f_where2, false);
                                if ($f_value[0] === '' || $f_value[0] === 0)
                                    $s_f_where->is_null($f_name, false);
                            }
                            $f_where->push_where($s_f_where);
                            break;
                        case 'custom':
                            if (!is_null($custom_filters_callback))
                                $custom_filters_callback($f_where, $f_name, $f_name_as, $f_value, $map_columns);
                            break;
                    }

                    $where->push_where($f_where);
                }
            }
        }
        if (!empty($search)) {
            $search_where = new where($magic_quotes);
            if (is_array($search_columns))
                foreach ($search_columns as $column) {
                    if (isset($map_columns[$column]))
                        $column = $map_columns[$column];
                    $search_where->partial_like($column, $search, false);
                }
            $where->push_where($search_where);
        }
        return $where;
    }
}


class where
{
    private $conditions = [];
    private $magic_quotes = true;

    /**
     * @param bool $magic_quotes //Экранировать названия столбцов по умолчанию
     */
    function __construct(bool $magic_quotes = true)
    {
        $this->magic_quotes = $magic_quotes;
        return $this;
    }

    function _get($param_prefix = null)
    {
        if (empty($this->conditions)) {
            return ['sql' => null, 'params' => []];
        }

        $sql = '';
        $params = [];
        $param_counter = 0;

        foreach ($this->conditions as $condition) {
            if ($sql != '' && !is_null($condition['before_operator'])) {
                $sql .= ' ' . $condition['before_operator'] . ' ';
            }

            $condition_sql = $condition['sql'];
            $condition_params = $condition['params'];

            // Если указан префикс, преобразуем позиционные параметры в именованные
            if (!is_null($param_prefix)) {
                foreach ($condition_params as $value) {
                    $param_name = ':' . $param_prefix . $param_counter;
                    $condition_sql = preg_replace('/\?/', $param_name, $condition_sql, 1);
                    $params[$param_name] = $value;
                    $param_counter++;
                }
            } else {
                $params = array_merge($params, $condition_params);
            }

            $sql .= $condition_sql;
        }
        return ['sql' => $sql, 'params' => $params];
    }

    private function add_condition($sql, $params, $before_use_and)
    {
        $operator = count($this->conditions) > 0 ? ($before_use_and ? 'AND' : 'OR') : null;
        $this->conditions[] = [
            'sql' => $sql,
            'params' => $params,
            'before_operator' => $operator
        ];
    }

    private function gen_column(string $column, ?string $sql_function = null, ?bool $magic_quotes = null)
    {
        if (is_null($magic_quotes)) {
            $magic_quotes = $this->magic_quotes;
        }
        $magic_quotes = $magic_quotes ? '`' : '';
        $sql_function = ext_tools::xss_filter($sql_function);
        return is_null($sql_function) ? $magic_quotes . $column . $magic_quotes : $sql_function . '(' . $magic_quotes . $column . $magic_quotes . ')';
    }


    function push_where(where $object, bool $before_use_and = true)
    {
        $sub = $object->_get();
        if ($sub['sql'] == '') {
            return $this;
        }
        $sql = '(' . $sub['sql'] . ')';
        $params = $sub['params'];
        $this->add_condition($sql, $params, $before_use_and);
        return $this;
    }

    function equally(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $magic_quotes = null)
    {
        if (is_null($magic_quotes)) {
            $magic_quotes = $this->magic_quotes;
        }
        if (gettype($value) == type_column::bool) {
            $value = $value ? 1 : 0;
        }
        if ($xss_filter) {
            $column = ext_tools::xss_filter($column);
            $value = ext_tools::xss_filter($value);
        }
        $sql = $this->gen_column($column, $sql_function, $magic_quotes) . " = ?";
        $params = [$value];
        $this->add_condition($sql, $params, $before_use_and);
        return $this;
    }

    function not_equally(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $magic_quotes = null)
    {
        if (is_null($magic_quotes)) {
            $magic_quotes = $this->magic_quotes;
        }
        if (gettype($value) == type_column::bool) {
            $value = $value ? 1 : 0;
        }
        if ($xss_filter) {
            $column = ext_tools::xss_filter($column);
            $value = ext_tools::xss_filter($value);
        }
        $sql = $this->gen_column($column, $sql_function, $magic_quotes) . " != ?";
        $params = [$value];
        $this->add_condition($sql, $params, $before_use_and);
        return $this;
    }

    private function _in(bool $is_not_in = false, ?string $column = null, array $values = null, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $magic_quotes = null)
    {
        if (is_null($magic_quotes)) {
            $magic_quotes = $this->magic_quotes;
        }
        if ($xss_filter) {
            $column = ext_tools::xss_filter($column);
            foreach ($values as $i => $value) {
                if (gettype($value) == type_column::bool)
                    $value = $value ? 1 : 0;
                if ($xss_filter)
                    $value = ext_tools::xss_filter($value);
                $values[$i] = $value;
            }
            $placeholders = implode(', ', array_fill(0, count($values), '?'));
            $sql = $this->gen_column($column, $sql_function, $magic_quotes) . ($is_not_in ? ' NOT' : '') . " IN ($placeholders)";
            $params = $values;
            $this->add_condition($sql, $params, $before_use_and);
        }
        return $this;
    }

    function in(string $column, array $values, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $magic_quotes = null)
    {
        return $this->_in(false, $column, $values, $before_use_and, $sql_function, $xss_filter, $magic_quotes);
    }

    function not_in(string $column, array $values, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $magic_quotes = null)
    {
        return $this->_in(true, $column, $values, $before_use_and, $sql_function, $xss_filter, $magic_quotes);
    }

    function more(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $magic_quotes = null)
    {
        if (is_null($magic_quotes)) {
            $magic_quotes = $this->magic_quotes;
        }
        if ($xss_filter) {
            $column = ext_tools::xss_filter($column);
            $value = ext_tools::xss_filter($value);
        }
        $sql = $this->gen_column($column, $sql_function, $magic_quotes) . " > ?";
        $params = [$value];
        $this->add_condition($sql, $params, $before_use_and);
        return $this;
    }

    function more_or_equally(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $magic_quotes = null)
    {
        if (is_null($magic_quotes)) {
            $magic_quotes = $this->magic_quotes;
        }
        if ($xss_filter) {
            $column = ext_tools::xss_filter($column);
            $value = ext_tools::xss_filter($value);
        }
        $sql = $this->gen_column($column, $sql_function, $magic_quotes) . " >= ?";
        $params = [$value];
        $this->add_condition($sql, $params, $before_use_and);
        return $this;
    }

    function less(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $magic_quotes = null)
    {
        if (is_null($magic_quotes)) {
            $magic_quotes = $this->magic_quotes;
        }
        if ($xss_filter) {
            $column = ext_tools::xss_filter($column);
            $value = ext_tools::xss_filter($value);
        }
        $sql = $this->gen_column($column, $sql_function, $magic_quotes) . " < ?";
        $params = [$value];
        $this->add_condition($sql, $params, $before_use_and);
        return $this;
    }

    function less_or_equally(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $magic_quotes = null)
    {
        if (is_null($magic_quotes)) {
            $magic_quotes = $this->magic_quotes;
        }
        if ($xss_filter) {
            $column = ext_tools::xss_filter($column);
            $value = ext_tools::xss_filter($value);
        }
        $sql = $this->gen_column($column, $sql_function, $magic_quotes) . " <= ?";
        $params = [$value];
        $this->add_condition($sql, $params, $before_use_and);
        return $this;
    }

    function is_null(string $column, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true)
    {
        if ($xss_filter) {
            $column = ext_tools::xss_filter($column);
        }
        $sql = $this->gen_column($column, $sql_function) . " IS NULL";
        $params = [];
        $this->add_condition($sql, $params, $before_use_and);
        return $this;
    }

    function is_not_null(string $column, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true)
    {
        if ($xss_filter) {
            $column = ext_tools::xss_filter($column);
        }
        $sql = $this->gen_column($column, $sql_function) . " IS NOT NULL";
        $params = [];
        $this->add_condition($sql, $params, $before_use_and);
        return $this;
    }

    function partial_like(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $magic_quotes = null)
    {
        if (is_null($magic_quotes)) {
            $magic_quotes = $this->magic_quotes;
        }
        if ($xss_filter) {
            $column = ext_tools::xss_filter($column);
            $value = ext_tools::xss_filter($value);
        }
        $sql = $this->gen_column($column, $sql_function, $magic_quotes) . " LIKE ?";
        $param = "%$value%";
        $params = [$param];
        $this->add_condition($sql, $params, $before_use_and);
        return $this;
    }

    /**
     * @link https://dev.mysql.com/doc/refman/5.5/en/fulltext-boolean.html
     * @param string $column
     * @param $value string Не фильтрует это значение на атаки XSS и SQL инъекции
     * @param bool $before_use_and
     * @param null $sql_function
     * @param bool $xss_filter_column
     * @param bool $value_quotes
     * @param bool $magic_quotes
     * @return $this
     */
    function full_text_search_bm_not_safe(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter_column = true, ?bool $magic_quotes = null)
    {
        if (is_null($magic_quotes)) {
            $magic_quotes = $this->magic_quotes;
        }
        if ($xss_filter_column) {
            $column = ext_tools::xss_filter($column);
        }
        $sql = "MATCH (" . $this->gen_column($column, $sql_function, $magic_quotes) . ") AGAINST (? IN BOOLEAN MODE)";
        $params = [$value];
        $this->add_condition($sql, $params, $before_use_and);
        return $this;
    }

    /**
     * @param $column
     * @param $value string Не фильтрует это значение на атаки XSS и SQL инъекции
     * @param bool $before_use_and
     * @param null $sql_function
     * @param bool $xss_filter_column
     * @param bool $value_quotes
     * @param bool $magic_quotes
     * @return $this
     */
    function regexp_not_safe(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter_column = true, ?bool $magic_quotes = null)
    {
        if (is_null($magic_quotes)) {
            $magic_quotes = $this->magic_quotes;
        }
        if ($xss_filter_column) {
            $column = ext_tools::xss_filter($column);
        }
        $sql = $this->gen_column($column, $sql_function, $magic_quotes) . " REGEXP ?";
        $params = [$value];
        $this->add_condition($sql, $params, $before_use_and);
        return $this;
    }
}

class select_exp
{
    private $args = [];
    private $sql = "";

    function __construct()
    {
        return $this;
    }

    /**
     * @param null|array $args Можно передавать следующие аргументы в этом массиве
     * @param null|string $column
     * @param null|string $as_column [optional]
     * @param null|string $sql_function [optional]
     * @param null|string $custom_table_name [optional]
     * @param true|bool $column_xss_filter [optional]
     * @param true|bool $column_magic_quotes [optional]
     * @return $this
     * @throws \ReflectionException
     */
    function add_column($args, $column = null, $as_column = null, $sql_function = null, $custom_table_name = null, $column_xss_filter = true, $column_magic_quotes = true)
    {
        $reflector = new \ReflectionClass(__CLASS__);
        $parameters = $reflector->getMethod(__FUNCTION__)->getParameters();
        if (!is_null($args)) {
            extract($args);
            unset($args);
        }
        $args = [];
        foreach ($parameters as $parameter) {
            if ($parameter->name === "args")
                continue;
            $args[$parameter->name] = ${$parameter->name};
        }
        $this->args[] = ['add_column', $args];
        return $this;
    }

    private function _add_column($column, $as_column = null, $sql_function = null, $custom_table_name = null, $column_xss_filter = true, $column_magic_quotes = true)
    {
        $column_magic_quotes = $column_magic_quotes ? '`' : '';
        if ($column == "*" or !is_null($sql_function)) $column_magic_quotes = "";
        if ($column_xss_filter)
            $column = ext_tools::xss_filter($column);
        $as_column = ext_tools::xss_filter($as_column);
        $sql_function = ext_tools::xss_filter($sql_function);
        $custom_table_name = ext_tools::xss_filter($custom_table_name);
        $column = (is_null($custom_table_name) ? '' : $custom_table_name . '.') . $column_magic_quotes . $column . $column_magic_quotes;
        $column = (is_null($sql_function) ? $column : $sql_function . '(' . $column . ')') . (is_null($as_column) ? '' : ' AS `' . $as_column . '`');
        if ($this->sql != "")
            $this->sql .= ", ";
        $this->sql .= $column;
    }

    function add_sql($sql_fragment)
    {
        $this->args[] = ['add_sql', $sql_fragment];
        return $this;
    }

    function _get($table_prefix)
    {
        $this->sql = "";
        $select_args = &$this->args;
        foreach ($select_args as $args) {
            switch ($args[0]) {
                case 'add_column':
                    if (!is_null($args[1]['custom_table_name']))
                        $args[1]['custom_table_name'] = $table_prefix . $args[1]['custom_table_name'];
                    call_user_func_array(array($this, '_add_column'), $args[1]);
                    break;
                case 'add_sql':
                    if ($this->sql != "")
                        $this->sql .= ", ";
                    $this->sql .= $args[1];
                    break;
            }
        }
        return $this->sql;
    }
}

class select_q
{
    private $args = [];

    /** select_query
     * @param array|null $args Можно передавать следующие аргументы в этом массиве
     * @param where|null $where
     * @param string|array $order_by
     * @param int $order_method
     * @param int $offset
     * @param int $limit
     * @param select_exp|null $select Параметры извлечения строк, если null (или не указано), то выбираются все столбцы
     * @param string|null $group_by
     * @param left_join_on|null $join
     * @param int|null $group_id_for_join_filters
     * @param bool $is_distinct
     * @param bool $for_update Добавить FOR UPDATE для блокировки строк в транзакции
     */
    function __construct($args = null, where $where = null, $order_by = '_order', $order_method = order::asc, $offset = 0, $limit = 0, select_exp $select = null, $group_by = null, left_join_on $join = null, $group_id_for_join_filters = null, $is_distinct = false, $for_update = false)
    {
        if (!is_null($args))
            extract($args);
        $reflector = new \ReflectionClass(__CLASS__);
        $parameters = $reflector->getMethod(__FUNCTION__)->getParameters();

        foreach ($parameters as $parameter) {
            if ($parameter->name === "args")
                continue;
            $this->args[$parameter->name] = ${$parameter->name};
        }
    }

    private $cur_table = null;
    private $table_prefix = null;

    private function generate_sql(where $where = null, $order_by = '_order', $order_method = order::asc, $offset = 0, $limit = 0, select_exp $select = null, $group_by = null, left_join_on $join = null, $group_id_for_join_filters = null, $is_distinct = false, $for_update = false)
    {
        $cur_table = $this->cur_table;
        $table_prefix = $this->table_prefix;
        $order_by = ext_tools::xss_filter($order_by);
        $group_by = ext_tools::xss_filter($group_by);
        $group_id_for_join_filters = ext_tools::xss_filter($group_id_for_join_filters);

        if (!is_null($group_id_for_join_filters)) {
            $filters_table = $cur_table . "_" . $group_id_for_join_filters . "_filters";
            if (db::check_table($filters_table)) {
                if (is_null($join))
                    $join = new left_join_on;
                $join->push($cur_table, $filters_table, 'id', 'id');
            }
        }
        $order_by = ($order_by == null) ? "_order" : $order_by;
        $order_method = ($order_method == null) ? order::asc : $order_method;
        $select = is_null($select) ? null : $select->_get($table_prefix);
        $sql = "SELECT " . ($is_distinct ? "DISTINCT " : "") . (empty($select) ? '*' : $select) . " FROM `" . $cur_table . "` " . (is_null($join) ? '' : $join->_get($table_prefix) . ' ');
        $where_data = $where ? $where->_get() : ['sql' => '', 'params' => []];
        if (!empty($where_data['sql'])) {
            $sql .= "WHERE " . $where_data['sql'];
        }
        $sql .= (is_null($group_by) ? '' : " GROUP BY $group_by ");
        if (!is_array($order_by))
            $order_by = [$order_by];
        $i = 0;
        foreach ($order_by as $value) {
            $o_prefix = " ORDER BY ";
            if ($i !== 0)
                $o_prefix = ", ";

            switch ($order_method) {
                case order::asc:
                    $sql .= $o_prefix . "$value";
                    break;
                case order::desc:
                    $sql .= $o_prefix . "$value DESC";
                    break;
                case order::rand:
                    $sql .= $o_prefix . "rand()";
                    break;
            }
            $i++;
        }

        if ($limit != 0) {
            $offset = intval($offset);
            $limit = intval($limit);
            $sql .= " LIMIT " . $offset . "," . $limit;
        } elseif ($offset != 0)
            ext_tools::error("offset не может быть без limit");

        if ($for_update) {
            // SQLite не поддерживает FOR UPDATE, но обеспечивает блокировку через транзакции
            $driver = db::get_driver();
            if ($driver !== 'sqlite') {
                $sql .= " FOR UPDATE";
            }
        }

        return ['sql' => $sql, 'params' => $where_data['params']];
    }

    function _get($cur_table, $table_prefix)
    {
        $this->cur_table = $cur_table;
        $this->table_prefix = $table_prefix;
        return call_user_func_array(array($this, 'generate_sql'), $this->args);
    }

    /**
     * Устанавливает использование FOR UPDATE в SELECT запросе
     * @param bool $value
     * @return $this
     */
    function set_for_update($value = true)
    {
        $this->args['for_update'] = $value;
        return $this;
    }
}

class left_join_on
{
    private $args = [];
    private $join = "";

    function __construct()
    {
        return $this;
    }

    function push($cur_table, $join_table, $column_in_current_table, $column_in_join_table, select_q $derived_table = null, $as_table_name = null)
    {
        $reflector = new \ReflectionClass(__CLASS__);
        $parameters = $reflector->getMethod(__FUNCTION__)->getParameters();

        $args = array();
        foreach ($parameters as $parameter) {
            $args[$parameter->name] = ${$parameter->name};
        }
        $this->args[] = $args;
        return $this;
    }

    private function _push($cur_table, $join_table, $column_in_current_table, $column_in_join_table, select_q $derived_table = null, $as_table_name = null)
    {
        $join_table = ext_tools::xss_filter($join_table);
        $column_in_current_table = ext_tools::xss_filter($column_in_current_table);
        $column_in_join_table = ext_tools::xss_filter($column_in_join_table);
        $as_table_name = ext_tools::xss_filter($as_table_name);
        if (is_null($as_table_name))
            $as_table_name = $join_table;
        if (is_null($derived_table))
            $this->join .= "LEFT JOIN $join_table $as_table_name ON ($cur_table.$column_in_current_table=$as_table_name.$column_in_join_table) ";
        else
            $this->join .= "LEFT JOIN ({$derived_table->_get($join_table, "")['sql']}) $as_table_name ON ($cur_table.$column_in_current_table=$as_table_name.$column_in_join_table) ";
    }

    function _get($table_prefix)
    {
        $this->join = "";
        $_args = &$this->args;
        foreach ($_args as $args) {
            $args['cur_table'] = $table_prefix . $args['cur_table'];
            $args['join_table'] = $table_prefix . $args['join_table'];
            call_user_func_array(array($this, '_push'), $args);
        }
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
 * array('type'=>showyweb\qdbm\type_column, 'is_xss_filter'=>bool,'is_add_index'=>bool)
 *
 * Ключ type - Тип столбца, устанавливается только при автосоздании столбца, если столбец существует, то значение $value только фильтруется согласно типу.
 *
 * Ключ is_xss_filter - Если true, то фильтр sql/xss включен.
 *
 * Ключ is_add_index - Если true, то при автосоздании столбца, автоматически добавляется индекс sql типа INDEX. Для типа showyweb\qdbm\type_column::string, sql тип индекса будет FULLTEXT
 *
 * Например:
 *
 * class test_db_c extends showyweb\qdbm\schema
 * {
 *
 * public $tab_name = "test";
 *
 * const chat_id = array('type' => showyweb\qdbm\type_column::unsigned_big_int, 'is_xss_filter' => true, 'is_add_index' => true);
 *
 * const key = array('type' => showyweb\qdbm\type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
 *
 * }
 *
 * Если в имени столбца присутствует префикс v_, то этот столбец будет расцениваться как виртуальный, qdbm его обрабатывать не будет.
 *
 */
abstract class schema
{
    /**
     * Имя таблицы
     */
    public $tab_name = "";

    /**
     * id строки
     */
    const id = array('type' => type_column::unsigned_big_int, 'is_xss_filter' => true, 'is_add_index' => true);

    /**
     * Индекс порядка сортировки
     */
    const _order = array('type' => type_column::unsigned_big_int, 'is_xss_filter' => true, 'is_add_index' => true);

    /**
     * showyweb\qdbm\schema constructor.
     * @param string $tab_name Если не null, то переопределяет свойство $tab_name
     * @throws \exception
     */
    public function __construct($tab_name = null)
    {
        if (!is_null($tab_name))
            $this->tab_name = $tab_name;

        if (empty($this->tab_name))
            ext_tools::error("tab_name empty");
        return $this;
    }

    function get_columns()
    {
        $constants = ext_tools::get_constants_in_class($this);
        foreach ($constants as $key => $constant) {
            $this->{$key} = $key;
        }
        return $constants;
    }
}


/**
 * @see schema для групп и фильтров
 *
 * */
class gf_schema extends schema
{
    const group_type = array('type' => type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
    const filter_type = array('type' => type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
    const column = array('type' => type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
    const parent_id = array('type' => type_column::unsigned_int, 'is_xss_filter' => true, 'is_add_index' => true);
    const title = array('type' => type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
    const description = array('type' => type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
}

class db
{
    private $table = null;
    private $columns = [];
    private static $check_column_table_cache = null;
    private static $active_tables = [];
    private static $write_locked = false;
    private static $write_locked_arr = [];
    private static $transaction_active = false;
    private static $transaction_level = 0;
    private static $pdo = null;
    private static $pdo_auth = [];
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
    static function set_pdo_auth(array $config)
    {
        static::$pdo_auth = $config;
    }

    public function __construct(schema $qdbm_schema)
    {
        if (is_null(static::$path_cache)) {
            static::$path_cache = __DIR__ . "/.QuickDBM_cache";
            if (!is_dir(static::$path_cache))
                mkdir(static::$path_cache);
            static::$path_cache .= "/cache";
            //            echo static::$path_cache . "\n";
        }
        $this->set_table($qdbm_schema->tab_name);
        if (!in_array($this->table, static::$active_tables))
            static::$active_tables[] = $this->table;
        $columns = $qdbm_schema->get_columns();
        $this->columns = $columns;
        foreach ($columns as $name => $column_inf) {
            if (substr($name, 0, 2) === "v_")
                continue;
            $this->columns[$name]['name'] = $name;
            $type = $column_inf['type'];
            $is_xss_filter = $column_inf['is_xss_filter'];
            $is_add_index = $column_inf['is_add_index'];
            if (!$this->check_column($name)) {
                $this->add_column($name, $type, $is_add_index);
            }
        }
        return $this;
    }

    function __destruct()
    {
        if (static::$cache_is_modified && !is_null(static::$check_column_table_cache)) {
            $str = serialize(static::$check_column_table_cache);
            ext_tools::save_to_text_file(static::$path_cache, $str, null);
        }
        static::$check_column_table_cache = null;
        $this->commit();
    }

    function check_column($column)
    {
        if (is_null(static::$check_column_table_cache)) {
            $str = ext_tools::open_txt_file(static::$path_cache, null);
            static::$check_column_table_cache = is_null($str) ? [] : unserialize($str);
        }
        $db_name = isset(static::$pdo_auth['db_name']) ? static::$pdo_auth['db_name'] : 'sqlite';
        $c_key = $db_name . '.' . $this->table;
        if (!isset(static::$check_column_table_cache[$c_key])) {
            static::$check_column_table_cache[$c_key] = [];
            static::$cache_is_modified = true;
        }
        $name = $column;
        $pdo = static::get_pdo();
        $name = ext_tools::xss_filter($name);
        if (isset(static::$check_column_table_cache[$c_key][$name]))
            return static::$check_column_table_cache[$c_key][$name];

        $driver = static::get_driver();
        try {
            if ($driver === 'sqlite') {
                // SQLite использует PRAGMA для проверки колонок
                $sql = "PRAGMA table_info(`" . $this->table . "`)";
                $stmt = $pdo->query($sql);
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $found = false;
                foreach ($columns as $col) {
                    if ($col['name'] === $name) {
                        $found = true;
                        break;
                    }
                }
                if (!$found)
                    return false;
                else
                    static::$check_column_table_cache[$c_key][$name] = true;
            } else {
                // MySQL
                $sql = "SHOW COLUMNS FROM `" . $this->table . "` LIKE :name";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':name' => $name]);
                $itog = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($itog === false)
                    return false;
                else
                    static::$check_column_table_cache[$c_key][$name] = true;
            }
            static::$cache_is_modified = true;
            return static::$check_column_table_cache[$c_key][$name];
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " sql:" . $sql);
        }
    }

    static function get_pdo()
    {
        if (!is_null(static::$pdo))
            return static::$pdo;
        $pdo_auth = static::$pdo_auth;

        // Определяем драйвер: SQLite или MySQL
        $driver = isset($pdo_auth['driver']) ? $pdo_auth['driver'] : 'mysql';

        if ($driver === 'sqlite') {
            // SQLite подключение
            $db_path = isset($pdo_auth['db_path']) ? $pdo_auth['db_path'] : ':memory:';
            $dsn = "sqlite:$db_path";
            try {
                static::$pdo = new PDO($dsn);
                static::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                // Для SQLite не нужно выполнять USE DATABASE
            } catch (PDOException $e) {
                ext_tools::error("Ошибка подключения SQLite: " . $e->getMessage());
            }
        } else {
            // MySQL подключение (по умолчанию)
            if (!isset($pdo_auth["host"]))
                ext_tools::error("Не указаны данные авторизации PDO");
            $dsn = "mysql:host={$pdo_auth['host']};charset=utf8mb4";
            try {
                static::$pdo = new PDO($dsn, $pdo_auth["user"], $pdo_auth["password"]);
                static::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                static::$pdo->exec("set character_set_client = 'utf8mb4'");
                static::$pdo->exec("set character_set_results = 'utf8mb4'");
                static::$pdo->exec("set collation_connection = 'utf8mb4_general_ci'");
                static::$pdo->exec("SET lc_time_names = 'ru_UA'");
                $select_status = static::$pdo->exec("USE `{$pdo_auth['db_name']}`");
                if ($select_status === false)
                    static::set_db_name($pdo_auth["db_name"]);
            } catch (PDOException $e) {
                ext_tools::error("Ошибка подключения: " . $e->getMessage());
            }
        }
        return static::$pdo;
    }

    /**
     * Определяет тип драйвера БД (mysql или sqlite)
     * @return string
     */
    static function get_driver()
    {
        return isset(static::$pdo_auth['driver']) ? static::$pdo_auth['driver'] : 'mysql';
    }

    /** Небезопасная функция, то что вы передаете в параметр $sql никак не фильтруется
     * @param $sql
     * @param $params
     * @param $return_array
     * @return mixed
     * @throws \Exception
     */
    static function raw_sql($sql, $params = [], $return_array = false)
    {
        $pdo = static::get_pdo();
        static::$check_column_table_cache = [];
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if ($return_array) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            return $stmt;
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " sql:" . $sql);
        }
    }

    static function check_table($check_table)
    {
        $db_name = isset(static::$pdo_auth['db_name']) ? static::$pdo_auth['db_name'] : 'sqlite';
        $c_key = $db_name . '.' . $check_table;
        if (!is_null(static::$check_column_table_cache) && isset(static::$check_column_table_cache[$c_key]))
            return true;
        $pdo = static::get_pdo();
        $check_table = ext_tools::xss_filter($check_table);
        $driver = static::get_driver();

        try {
            if ($driver === 'sqlite') {
                // SQLite использует другой синтаксис для проверки таблиц
                $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=:table_name");
                $stmt->execute([':table_name' => $check_table]);
                return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
            } else {
                // MySQL
                $stmt = $pdo->query("SHOW COLUMNS FROM `$check_table`");
                if ($stmt && $stmt->fetch(PDO::FETCH_ASSOC))
                    return true;
                return false;
            }
        } catch (PDOException $e) {
            return false;
        }
    }

    static function check_db_name($db_name)
    {
        $driver = static::get_driver();
        if ($driver === 'sqlite') {
            // Для SQLite не используется понятие баз данных
            return true;
        }

        $pdo = static::get_pdo();
        $db_name = ext_tools::xss_filter($db_name);
        try {
            $stmt = $pdo->prepare("SHOW DATABASES LIKE :db_name");
            $stmt->execute([':db_name' => $db_name]);
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    static function set_db_name($db_name, $_pdo = null)
    {
        $driver = static::get_driver();
        if ($driver === 'sqlite') {
            // Для SQLite не используется понятие баз данных
            return;
        }

        $pdo = is_null($_pdo) ? static::get_pdo() : $_pdo;
        static::$check_column_table_cache = null;
        $db_name = ext_tools::xss_filter($db_name);
        if (!static::check_db_name($db_name)) {
            $sql = "CREATE DATABASE `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
            try {
                $pdo->exec($sql);
            } catch (PDOException $e) {
                ext_tools::error($e->getMessage() . " sql:" . $sql);
            }
        }
        try {
            $pdo->exec("USE `$db_name`");
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage());
        }
    }

    private function set_table($table)
    {
        $pdo = static::get_pdo();
        $table = ext_tools::xss_filter($table);
        $table_prefix = isset(static::$pdo_auth['table_prefix']) ? static::$pdo_auth['table_prefix'] : '';
        if (!empty($table_prefix))
            $table = $table_prefix . $table;
        if (!static::check_table($table)) {
            $driver = static::get_driver();
            if ($driver === 'sqlite') {
                // SQLite синтаксис (без ENGINE, CHARSET, и используем INTEGER вместо BIGINT)
                $sql = "CREATE TABLE IF NOT EXISTS `$table` (`id` INTEGER NOT NULL, `_order` INTEGER NOT NULL, UNIQUE(`id`))";
            } else {
                // MySQL синтаксис
                $sql = "CREATE TABLE IF NOT EXISTS `$table` (`id` BIGINT(255) UNSIGNED NOT NULL, `_order` BIGINT(255) UNSIGNED NOT NULL, UNIQUE `id` (`id`), INDEX `_order` (`_order`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            }
            try {
                $pdo->exec($sql);
                // Для SQLite создаем индекс отдельно
                if ($driver === 'sqlite') {
                    $pdo->exec("CREATE INDEX IF NOT EXISTS `{$table}__order_idx` ON `$table` (`_order`)");
                }
            } catch (PDOException $e) {
                ext_tools::error($e->getMessage() . " sql:" . $sql);
            }
        }
        $this->table = $table;
    }

    function get_table_name()
    {
        return $this->table;
    }

    static function remove_table($table)
    {
        $pdo = static::get_pdo();
        $table = ext_tools::xss_filter($table);
        $sql = "DROP TABLE `$table`";
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " sql:" . $sql);
        }
    }

    function get_raw_type_column($column)
    {
        $name = $column;
        $pdo = static::get_pdo();
        $name = ext_tools::xss_filter($name);
        $driver = static::get_driver();

        try {
            if ($driver === 'sqlite') {
                // SQLite использует PRAGMA для получения информации о колонках
                $sql = "PRAGMA table_info(`$this->table`)";
                $stmt = $pdo->query($sql);
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($columns as $col) {
                    if ($col['name'] === $name) {
                        return $col['type'];
                    }
                }
                return null;
            } else {
                // MySQL
                $sql = "SHOW COLUMNS FROM `$this->table` LIKE :name";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':name' => $name]);
                $itog = $stmt->fetch(PDO::FETCH_ASSOC);
                return $itog ? $itog['Type'] : null;
            }
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " sql:" . $sql);
        }
    }

    function add_column($column, $type, $is_add_index)
    {
        $name = $column;
        static::$check_column_table_cache = null;
        $driver = static::get_driver();
        $sql = '';

        if ($driver === 'sqlite') {
            // SQLite синтаксис (без CHARACTER SET, COLLATE, UNSIGNED)
            switch ($type) {
                case type_column::small_string:
                    $sql = "ALTER TABLE `$this->table` ADD COLUMN `$name` VARCHAR(255) DEFAULT NULL";
                    break;
                case type_column::string:
                    $sql = "ALTER TABLE `$this->table` ADD COLUMN `$name` TEXT DEFAULT NULL";
                    break;
                case type_column::decimal_auto:
                    $tmp_decimal_size = ext_tools::decimal_size("1");
                    $tmp_int_size = $tmp_decimal_size[0];
                    $tmp_scale_size = $tmp_decimal_size[1];
                    $sql = "ALTER TABLE `$this->table` ADD COLUMN `$name` DECIMAL($tmp_int_size,$tmp_scale_size) DEFAULT NULL";
                    break;
                case type_column::int:
                case type_column::unsigned_int:
                    $sql = "ALTER TABLE `$this->table` ADD COLUMN `$name` INTEGER DEFAULT NULL";
                    break;
                case type_column::big_int:
                case type_column::unsigned_big_int:
                    $sql = "ALTER TABLE `$this->table` ADD COLUMN `$name` INTEGER DEFAULT NULL";
                    break;
                case type_column::bool:
                    $sql = "ALTER TABLE `$this->table` ADD COLUMN `$name` INTEGER DEFAULT NULL";
                    break;
                case type_column::datetime:
                    $sql = "ALTER TABLE `$this->table` ADD COLUMN `$name` TEXT DEFAULT NULL";
                    break;
            }
        } else {
            // MySQL синтаксис
            switch ($type) {
                case type_column::small_string:
                    $sql = "ALTER TABLE `$this->table` ADD `$name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL";
                    break;
                case type_column::string:
                    $sql = "ALTER TABLE `$this->table` ADD `$name` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL";
                    break;
                case type_column::decimal_auto:
                    $tmp_decimal_size = ext_tools::decimal_size("1");
                    $tmp_int_size = $tmp_decimal_size[0];
                    $tmp_scale_size = $tmp_decimal_size[1];
                    $sql = "ALTER TABLE `$this->table` ADD `$name` DECIMAL($tmp_int_size,$tmp_scale_size) NULL DEFAULT NULL";
                    break;
                case type_column::int:
                    $sql = "ALTER TABLE `$this->table` ADD `$name` INT(255) NULL DEFAULT NULL";
                    break;
                case type_column::big_int:
                    $sql = "ALTER TABLE `$this->table` ADD `$name` BIGINT(255) NULL DEFAULT NULL";
                    break;
                case type_column::unsigned_int:
                    $sql = "ALTER TABLE `$this->table` ADD `$name` INT(255) UNSIGNED NULL DEFAULT NULL";
                    break;
                case type_column::unsigned_big_int:
                    $sql = "ALTER TABLE `$this->table` ADD `$name` BIGINT(255) UNSIGNED NULL DEFAULT NULL";
                    break;
                case type_column::bool:
                    $sql = "ALTER TABLE `$this->table` ADD `$name` BOOLEAN NULL DEFAULT NULL";
                    break;
                case type_column::datetime:
                    $sql = "ALTER TABLE `$this->table` ADD `$name` DATETIME NULL DEFAULT NULL";
                    break;
            }
        }

        $pdo = static::get_pdo();
        try {
            $pdo->exec($sql);

            // Создаем индекс отдельно для обоих драйверов
            if ($is_add_index) {
                if ($driver === 'sqlite') {
                    // SQLite не поддерживает FULLTEXT, создаем обычный индекс
                    $index_sql = "CREATE INDEX IF NOT EXISTS `{$this->table}_{$name}_idx` ON `$this->table` (`$name`)";
                    $pdo->exec($index_sql);
                } else {
                    // MySQL - добавляем индекс в том же запросе
                    switch ($type) {
                        case type_column::string:
                            $index_sql = "ALTER TABLE `$this->table` ADD FULLTEXT `$name` (`$name`)";
                            break;
                        default:
                            $index_sql = "ALTER TABLE `$this->table` ADD INDEX `$name` (`$name`)";
                            break;
                    }
                    $pdo->exec($index_sql);
                }
            }
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " sql:" . $sql);
        }
    }

    function remove_column($column)
    {
        $name = $column;
        $pdo = static::get_pdo();
        $db_name = isset(static::$pdo_auth['db_name']) ? static::$pdo_auth['db_name'] : 'sqlite';
        $c_key = $db_name . '.' . $this->table;
        if (isset(static::$check_column_table_cache[$c_key]) && isset(static::$check_column_table_cache[$c_key][$name]))
            unset(static::$check_column_table_cache[$c_key][$name]);
        $name = ext_tools::xss_filter($name);
        $sql = "ALTER TABLE `$this->table` DROP `$name`";
        try {
            $pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " sql:" . $sql);
        }
    }

    /**
     * @see get_new_insert_id
     */
    function get_nii($is_auto_transaction = true)
    {
        return $this->get_new_insert_id($is_auto_transaction);
    }

    /**
     * Возвращает новый id для вставки новой записи
     * @param bool $is_auto_transaction По умолчанию начинает транзакцию, чтобы не возник конфликт вставки с одинаковым id
     * @return int
     * @throws \exception
     * @see begin_transaction
     */
    function get_new_insert_id($is_auto_transaction = true)
    {
        if ($is_auto_transaction)
            $this->begin_transaction();
        $sql = "SELECT `id` FROM `$this->table` ORDER BY `id` DESC LIMIT 1";
        $pdo = static::get_pdo();
        try {
            $stmt = $pdo->query($sql);
            $itog = $stmt->fetch(PDO::FETCH_ASSOC);
            return $itog ? $itog["id"] + 1 : 1;
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " sql:" . $sql);
        }
    }


    /**
     * @param array $records Список записей в виде ["column"=>'value', "column2"=>'value2']
     * @param integer $insert_id Идентификатор строки в таблице. Если строка не найдена, то она вставляется как новая. Если параметр $where, не null, то $insert_id игнорируется
     * @param where|null $where [optional] Альтернативное условие в запросе, по умолчанию при обновлении записи используется `id`=$insert_id
     * @throws \exception
     */
    function insert($records, $insert_id, where $where = null)
    {
        $pdo = static::get_pdo();
        $id = ext_tools::xss_filter($insert_id);
        if (is_null($where)) {
            if (is_null($id))
                ext_tools::error('id null');

            $sql = "SELECT `id` FROM `$this->table` WHERE `id` = :id LIMIT 1";
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $id]);
                $itog = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$itog) {
                    $tmp_tr = static::$transaction_active;
                    if (!$tmp_tr)
                        $this->begin_transaction();
                    $_order = ($id == 1) ? 1 : $this->get_max_order() + 1;
                    $sql = "INSERT INTO `$this->table` (`id`, `_order`) VALUES (:id, :_order)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':id' => $id, ':_order' => $_order]);
                    if (!$tmp_tr)
                        $this->commit();
                }
            } catch (PDOException $e) {
                if (!$tmp_tr)
                    $this->rollback();
                ext_tools::error($e->getMessage() . " sql:" . $sql);
            }
        }

        $sql = "UPDATE `$this->table` SET ";
        $params = [];
        $i = 0;
        foreach ($records as $key => $value) {
            $column_inf = $this->columns[$key];
            $name = ext_tools::xss_filter($column_inf['name']);
            $type = $column_inf['type'];
            $is_xss_filter = $column_inf['is_xss_filter'];
            switch ($type) {
                case type_column::small_string:
                case type_column::string:
                case type_column::datetime:
                case type_column::decimal_auto:
                    if ($is_xss_filter)
                        $value = ext_tools::xss_filter($value);
                    break;
                case type_column::big_int:
                case type_column::int:
                case type_column::unsigned_int:
                case type_column::unsigned_big_int:
                    if ($value !== '')
                        $value = intval($value, 10);
                    break;
                case type_column::bool:
                    $value = ($value === "1" || $value === "0") ? $value : ($value ? "1" : "0");
                    break;
                default:
                    if ($is_xss_filter)
                        $value = ext_tools::xss_filter($value);
                    break;
            }

            if ($type == type_column::decimal_auto) {
                $this->adjust_decimal_column($name, $value);
            }

            $sql .= ($i > 0 ? ", " : "") . "`$name` = :set_$name";
            $params[":set_$name"] = ($value === "" || $value === 0) ? null : $value;
            $i++;
        }
        if (is_null($where)) {
            $sql .= " WHERE `id` = :where_id";
            $params[":where_id"] = $id;
        } else {
            $where_data = $where->_get('where_');
            $sql .= " WHERE " . $where_data['sql'];
            $params = array_merge($params, $where_data['params']);
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " sql:" . $sql);
        }
    }

    private function get_max_order()
    {
        $pdo = static::get_pdo();
        $sql = "SELECT MAX(`_order`) AS order_max FROM `$this->table`";
        try {
            $stmt = $pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['order_max'] ?? 0;
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " sql:" . $sql);
        }
    }

    private function adjust_decimal_column($name, $value)
    {
        $driver = static::get_driver();

        // SQLite не требует изменения размера DECIMAL - он хранит как REAL или TEXT
        if ($driver === 'sqlite') {
            return;
        }

        $pdo = static::get_pdo();
        $tmp_decimal_size = ext_tools::decimal_size($value);
        $tmp_int_size = $tmp_decimal_size[0];
        $tmp_scale_size = $tmp_decimal_size[1];
        $raw_type = $this->get_raw_type_column($name);
        if (!preg_match('/decimal\((\d+),(\d+)\)/ui', $raw_type, $matches))
            ext_tools::error("$name not decimal type");

        $raw_type_int_size = $matches[1];
        $raw_type_scale_size = $matches[2];

        if ($tmp_int_size > $raw_type_int_size || $tmp_scale_size > $raw_type_scale_size) {
            $tmp_int_size = max($tmp_int_size, $raw_type_int_size);
            $tmp_scale_size = max($tmp_scale_size, $raw_type_scale_size);
            $sql = "ALTER TABLE `$this->table` CHANGE `$name` `$name` DECIMAL($tmp_int_size,$tmp_scale_size) NULL DEFAULT NULL";
            try {
                $pdo->exec($sql);
            } catch (PDOException $e) {
                ext_tools::error($e->getMessage() . " sql:" . $sql);
            }
        }
    }

    function remove_rows(where $where)
    {
        $pdo = static::get_pdo();
        $where_data = $where->_get();
        $sql = "DELETE FROM `$this->table` WHERE " . $where_data['sql'];
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($where_data['params']);
            return true;
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " sql:" . $sql);
        }
    }

    function get_count(where $where = null, $column = "id", $is_distinct = false, $magic_quotes = true)
    {
        $pdo = static::get_pdo();
        $magic_quotes = $magic_quotes ? '`' : '';
        $column = $magic_quotes . $column . $magic_quotes;
        $sql = "SELECT COUNT(" . ($is_distinct ? "DISTINCT " : "") . "$column) AS `_count` FROM `$this->table`";
        $params = [];
        if (!is_null($where)) {
            $where_data = $where->_get();
            if (!empty($where_data['sql'])) {
                $sql .= " WHERE " . $where_data['sql'];
                $params = $where_data['params'];
            }
        }
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $val = $stmt->fetch(PDO::FETCH_ASSOC);
            return intval($val["_count"]);
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " sql:" . $sql);
        }
    }

    /*
     *@return array|null
     * */
    function get_rows(select_q $select_query = null)
    {
        if (is_null($select_query))
            $select_query = new select_q();

        // Автоматически использовать FOR UPDATE если транзакция активна
        if (static::$transaction_active) {
            $select_query->set_for_update(true);
        }

        $pdo = static::get_pdo();
        $table_prefix = static::$pdo_auth['table_prefix'];
        $cur_table = $this->get_table_name();
        $query_data = $select_query->_get($cur_table, $table_prefix);
        $sql = $query_data['sql'];
        $params = $query_data['params'];
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $itog_ = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return count($itog_) ? $itog_ : null;
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " sql:" . $sql);
        }
    }

    function get_unique_vals_in_column($column, where $where = null, $magic_quotes = true)
    {
        $column = ext_tools::xss_filter($column);
        $magic_quotes = $magic_quotes ? '`' : '';
        $sql = "SELECT DISTINCT $magic_quotes$column$magic_quotes FROM `$this->table`";
        $params = [];
        if (!is_null($where)) {
            $where_data = $where->_get();
            if (!empty($where_data['sql'])) {
                $sql .= " WHERE " . $where_data['sql'];
                $params = $where_data['params'];
            }
        }
        try {
            $result = static::raw_sql($sql, $params, true);
            return is_null($result) ? null : (isset($result[0][$column]) ? array_column($result, $column) : $result[0]);
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " sql:" . $sql);
        }
    }

    function get_min_and_max_in_column($column, where $where = null, $magic_quotes = true)
    {
        $column = ext_tools::xss_filter($column);
        $magic_quotes = $magic_quotes ? '`' : '';
        $select = new select_exp();
        $select->add_column([
            'column' => "IFNULL(MIN($magic_quotes$column$magic_quotes),0)",
            'as_column' => 'min',
            'column_xss_filter' => false,
            'column_magic_quotes' => false
        ]);
        $select->add_column([
            'column' => "IFNULL(MAX($magic_quotes$column$magic_quotes),0)",
            'as_column' => 'max',
            'column_xss_filter' => false,
            'column_magic_quotes' => false
        ]);
        $res = $this->get_rows(new select_q(null, $where, null, null, 0, 0, $select));
        $min = $res[0]['min'] ?? null;
        $max = $res[0]['max'] ?? null;
        return ($max === null || $min === null) ? null : [intval($min), intval($max)];
    }


    function format_ids_in_table($id = "id")
    {
        $pdo = static::get_pdo();
        $id = ext_tools::xss_filter($id);
        $sql = "UPDATE `$this->table` SET `$id` = (SELECT @a := @a + 1 FROM (SELECT @a := 0) i)";
        try {
            $pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " sql:" . $sql);
        }
    }

    /**
     * Ставит блокировку типа WRITE на активные таблицы (таблица добавляется в активные при вызове new qdbm)
     *
     * ВНИМАНИЕ: Этот метод предназначен для ручного использования. По умолчанию QuickDBM использует транзакции (begin_transaction/commit/rollback).
     * Используйте блокировки только если вам действительно нужен механизм LOCK TABLES вместо транзакций.
     *
     * Повторная блокировка запрещена, так как приводит к автоматической предварительной разблокировке таблиц. Если она вам нужна, то предварительно вызовите unlock_tables. Однако, исключение не будет вызываться, если текущая таблица была раннее заблокирована.
     * @throws \exception
     * @link https://dev.mysql.com/doc/refman/5.7/en/lock-tables.html
     * @see unlock_tables
     * @see begin_transaction
     */
    function smart_write_lock()
    {
        static::s_smart_write_lock($this->table);
    }

    /**
     * @param null|string $this_table
     * @throws \exception
     * @see smart_write_lock
     */
    static function s_smart_write_lock($this_table = null)
    {
        if (static::$write_locked) {
            if (in_array($this_table, static::$write_locked_arr))
                return;
            ext_tools::error("Re-lock is forbidden");
        }
        $pdo = static::get_pdo();
        static::$write_locked = true;
        $tables_str = "";
        foreach (static::$active_tables as $table) {
            static::$write_locked_arr[] = $table;
            $tables_str .= ", `$table` WRITE";
        }
        $tables_str = substr($tables_str, 2);
        $sql = "LOCK TABLES $tables_str";
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " sql:" . $sql);
        }
    }

    /**
     * Снимает блокировку с таблиц
     *
     * ВНИМАНИЕ: Этот метод предназначен для ручного использования вместе с smart_write_lock().
     * По умолчанию QuickDBM использует транзакции (commit/rollback).
     *
     * @return bool
     * @throws \exception
     * @see smart_write_lock
     * @see commit
     */
    function unlock_tables()
    {
        return static::s_unlock_tables();
    }

    /**
     * Статический метод для снятия блокировки с таблиц
     * @return bool
     * @throws \exception
     */
    static function s_unlock_tables()
    {
        $pdo = static::get_pdo();
        $sql = "UNLOCK TABLES";
        try {
            $pdo->exec($sql);
            static::$write_locked = false;
            static::$write_locked_arr = [];
            return true;
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " sql:" . $sql);
        }
    }

    /**
     * Начинает транзакцию. Поддерживает вложенные транзакции через счетчик уровней.
     * @return bool
     * @throws \exception
     * @see commit
     * @see rollback
     */
    function begin_transaction()
    {
        return static::s_begin_transaction();
    }

    /**
     * Статический метод для начала транзакции
     * @return bool
     * @throws \exception
     */
    static function s_begin_transaction()
    {
        $pdo = static::get_pdo();
        try {
            if (static::$transaction_level === 0) {
                $pdo->beginTransaction();
                static::$transaction_active = true;
            }
            static::$transaction_level++;
            return true;
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " Failed to begin transaction");
        }
    }

    /**
     * Фиксирует транзакцию. При вложенных транзакциях фиксация происходит только на верхнем уровне.
     * @return bool
     * @throws \exception
     * @see begin_transaction
     * @see rollback
     */
    function commit()
    {
        return static::s_commit();
    }

    /**
     * Статический метод для фиксации транзакции
     * @return bool
     * @throws \exception
     */
    static function s_commit()
    {
        $pdo = static::get_pdo();
        try {
            if (static::$transaction_level > 0) {
                static::$transaction_level--;
                if (static::$transaction_level === 0 && static::$transaction_active) {
                    $pdo->commit();
                    static::$transaction_active = false;
                }
            }
            return true;
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " Failed to commit transaction");
        }
    }

    /**
     * Откатывает транзакцию. Откат всегда происходит полностью, независимо от уровня вложенности.
     * @return bool
     * @throws \exception
     * @see begin_transaction
     * @see commit
     */
    function rollback()
    {
        return static::s_rollback();
    }

    /**
     * Статический метод для отката транзакции
     * @return bool
     * @throws \exception
     */
    static function s_rollback()
    {
        $pdo = static::get_pdo();
        try {
            if (static::$transaction_active) {
                $pdo->rollBack();
                static::$transaction_active = false;
                static::$transaction_level = 0;
            }
            return true;
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage() . " Failed to rollback transaction");
        }
    }

    function close_connection()
    {
        static::s_close_connection();
    }

    static function s_close_connection()
    {
        static::$pdo = null;
    }

    function move_order($from, $to)
    {
        $pdo = static::get_pdo();
        $this->begin_transaction();
        $from = intval($from, 10);
        $to = intval($to, 10);
        if ($from == $to)
            return true;
        $where = new where();
        $where->equally('_order', $from);
        $where->equally('_order', $to, false);
        $result = $this->get_rows(new select_q(null, $where));
        if (count($result) != 2)
            return false;
        $ids = array();
        $ids[$result[0]['_order']] = $result[0]['id'];
        $ids[$result[1]['_order']] = $result[1]['id'];
        $sql = "UPDATE `$this->table` SET ";
        if ($to > $from)
            $sql .= "`_order`=`_order`-1 WHERE `_order`>$from AND `_order`<=$to ORDER BY `_order`";
        else
            $sql .= "`_order`=`_order`+1 WHERE `_order`<$from AND `_order`>=$to ORDER BY `_order`";
        try {
            $pdo->exec($sql);
            $rec = [
                '_order' => $to
            ];
            $this->insert($rec, $ids[$from]);
            $this->commit();
            return true;
        } catch (PDOException $e) {
            $this->rollback();
            ext_tools::error($e->getMessage() . " sql:" . $sql);
        }
    }

    function move_orders(array $ids, array $from, array $to)
    {
        $this->begin_transaction();
        $len = count($ids);

        for ($i = 0; $i < $len; $i++) {
            if ($from[$i] != $to[$i]) {
                $where = new where();
                $where->equally('id', $ids[$i]);
                $where->equally('_order', $from[$i]);
                if (is_null($this->get_rows(new select_q(null, $where)))) {
                    $this->rollback();
                    return false;
                }

            }
        }
        $rec = [];
        for ($i = 0; $i < $len; $i++) {
            if ($from[$i] != $to[$i]) {
                $where = new where();
                $where->equally('id', $ids[$i]);
                $where->equally('_order', $from[$i]);
                $rec['_order'] = $to[$i];
                $this->insert($rec, null, $where);
            }
        }

        $this->commit();
        return true;
    }

    static function import_sql_file($file_name)
    {
        $pdo = static::get_pdo();
        static::$check_column_table_cache = null;
        $sql_text = ext_tools::open_txt_file($file_name, null);
        if (is_null($sql_text))
            ext_tools::error('error import sql file ' . $file_name);
        try {
            $pdo->exec($sql_text);
        } catch (PDOException $e) {
            ext_tools::error($e->getMessage());
        }
    }

    //GROUPS ZONE START ------------------------------------------------------

    private function get_gf_db($tab_name = null)
    {
        $table = is_null($tab_name) ? $this->table . "_groups" : $tab_name;
        $db = new db(new gf_schema($table));
        return $db;
    }

    static function type_is_group($group_type)
    {

        $group_constants = ext_tools::get_constants_in_class('showyweb\qdbm\group_type');
        foreach ($group_constants as $type) {
            if ($type == $group_type && $group_type != group_type::all)
                return true;
        }
        return false;
    }

    static function type_is_filter($filter_type)
    {
        $filter_constants = ext_tools::get_constants_in_class('showyweb\qdbm\filter_type');
        foreach ($filter_constants as $type) {
            if ($type == $filter_type && $filter_type != filter_type::all)
                return true;
        }
        return false;
    }

    function add_group($title, $description, $parent_id = 0, $group_type = group_type::standard)
    {
        if (!static::type_is_group($group_type)) {
            ext_tools::error('Недопустимый тип группы');
            return false;
        }
        if ($parent_id != 0) {
            $res = $this->get_group($parent_id);
            if ($res[0]['group_type'] == group_type::standard && $group_type != group_type::filter) {
                ext_tools::error('Нельзя добавить подгруппу в стандартную группу');
                return false;
            }
            if ($res[0]['group_type'] == group_type::expand && $group_type == group_type::filter) {
                ext_tools::error('Нельзя добавить группу фильтров в разворачиваемую группу');
                return false;
            }
        }
        return $this->group(null, $title, $description, $parent_id, $group_type);
    }

    /** Добавить фильтр
     * @param string $title Заголовок
     * @param string $description Описание
     * @param int $group_id ID группы. Если ID 0, то фильтр будет глобальный
     * @param filter_type $filter_type Тип Фильтра
     * @return bool|int|null
     * @throws \exception
     */
    function add_filter($title, $description, $group_id = 0, $filter_type, $column = null)
    {
        if (!static::type_is_filter($filter_type)) {
            ext_tools::error('Недопустимый тип фильтра');
            return false;
        }
        if ($group_id) {
            $res = $this->get_group($group_id);
            if ($res[0]['group_type'] == group_type::expand) {
                ext_tools::error('Нельзя добавить фильтр в разворачиваемую группу');
                return false;
            }
        }
        return $this->group(null, $title, $description, $group_id, $filter_type, $column);
    }

    /**
     * @param int $obj_id Общий идентификатор
     * @param int $group_id Идентификатор группы типа qdbm_group_type::standard
     * @param array $filers_vals Ассоциативный массив: Имя столбца (column) фильтра => Значение
     */
    function save_values_for_filters($obj_id, $group_id = 0, $filers_vals)
    {
        $f_result = $this->get_recursive_filters($group_id);
        $table = $this->table;
        foreach ($f_result as $val) {
            $column = $val['column'];
            if (isset($filers_vals[$column])) {
                $filter_table = $val['parent_id'] ? $table . "_" . $group_id . "_filters" : $table;
                $db = $this->get_gf_db($filter_table);
                $rec = [$column => $filers_vals[$column]];
                $db->insert($rec, $obj_id);
            }
        }
    }

    function edit_group($id, $title, $description, $parent_id = 0, $force_edit = false)
    {
        $res = $this->get_group($id);
        if (!is_null($res) && !static::type_is_group($res[0]['group_type']))
            ext_tools::error("Группы не существует");
        if ($res == null && !$force_edit)
            ext_tools::error("Группы не существует");
        return $this->group($id, $title, $description, $parent_id, $force_edit ? group_type::standard : $res[0]['group_type']);
    }

    private function group($id = null, $title, $description, $parent_id, $group_type, $column = null)
    {
        $db = $this->get_gf_db();
        $new_id = $db->get_nii();
        if ($id != null)
            $new_id = $id;
        $new_id = intval($new_id);
        $records = [
            'title' => $title,
            'description' => $description,
            'parent_id' => $parent_id,
            'column' => ''
        ];

        $column_type = null;
        if (static::type_is_group($group_type))
            $records['group_type'] = $group_type;
        else
            switch ($group_type) {
                case filter_type::bool_filter:
                    $column_type = type_column::bool;
                    break;
                case filter_type::int_band_filter:
                case filter_type::int_filter:
                case filter_type::int_multiple_filter:
                case filter_type::int_multiple_band_filter:
                    $column_type = type_column::int;
                    break;
                case filter_type::datetime_band_filter:
                case filter_type::datetime_multiple_band_filter:
                    $column_type = type_column::datetime;
                    break;
                case filter_type::string_filter:
                case filter_type::str_multiple_filter:
                    $column_type = type_column::small_string;
                    break;
            }
        $db->insert($records, $new_id);
        if (!is_null($column_type)) {
            if (is_null($column))
                $column = "filter_" . $new_id;
            $stp_group = $parent_id ? $this->get_stp_group_for_filter($parent_id) : null;
            $records = [
                'group_type' => $group_type,
                'column' => $column
            ];
            $db->insert($records, $new_id);
            $filter_table = null;
            $table = $this->table;
            if ($parent_id) {
                if (is_null($stp_group))
                    ext_tools::error('$stp_group==null');
                $fg_id = $stp_group[0]['id'];
                $filter_table = $table . "_" . $fg_id . "_filters";
            } else
                $filter_table = $table;
            $db = $this->get_gf_db($filter_table);
            if (!$db->check_column($column))
                $db->add_column($column, gf_schema::column['type'], gf_schema::column['is_add_index']);
        }
        return $new_id;
    }

    function remove_group($id)
    {

        $id = ext_tools::xss_filter($id);
        $group_inf = $this->get_group($id);
        if ($group_inf == null)
            ext_tools::error("Такой группы не существует");
        $this->remove_group_or_filter($id, $group_inf);
        return true;
    }

    function remove_filter($id)
    {
        $id = ext_tools::xss_filter($id);
        $group_inf = $this->get_filter($id);
        if ($group_inf == null)
            ext_tools::error("Такого фильтра не существует");
        $this->remove_group_or_filter($id, $group_inf);
        return true;
    }

    private function remove_group_or_filter($id, $group_inf = null)
    {
        $id = intval($id);
        if (is_null($group_inf))
            $group_inf = $this->get_group_any_type($id);
        $stp_group_id = 0;
        $filters_table = null;
        if (static::type_is_filter($group_inf[0]['group_type'])) {
            $table = $this->table;
            if ($group_inf[0]['parent_id']) {
                $stp_group_id = $this->get_stp_group_for_filter($id)[0]['id'];
                $filters_table = $table . "_" . $stp_group_id . "_filters";
                $db = $this->get_gf_db($filters_table);
                if ($db->check_column($group_inf[0]['column']))
                    $db->remove_column($group_inf[0]['column']);
            }
        }

        $childrens = $this->get_groups(order::asc, 0, 0, $id);
        if (is_null($childrens))
            $childrens = array();
        $filters = $this->get_filters(order::asc, $id);
        if (is_null($filters))
            $filters = array();
        $childrens = array_merge($childrens, $filters);
        foreach ($childrens as $val) {
            if (!(static::type_is_filter($val['group_type']) && $val['parent_id'] == "0")) {
                $this->remove_group_or_filter($val['id']);
            }

        }

        $db = $this->get_gf_db();
        $where = new where();
        $where->equally('id', $id);
        $db->remove_rows($where);
        if ($group_inf[0]['parent_id'] && static::type_is_filter($group_inf[0]['qdbm_group_type']) && is_null($this->get_recursive_filters($stp_group_id)))
            static::remove_table($filters_table);
    }

    /**
     * Получить родительскую группу типа qdbm_group_type::standard для фильтра
     * @param int $id Идентификатор фильтра
     * @throws \exception
     */
    function get_stp_group_for_filter($id)
    {
        $g_r = $this->get_group_any_type($id);
        if (is_null($g_r))
            ext_tools::error('$g_r==null');
        $p_id = $g_r[0]['parent_id'];
        if ($g_r[0]['group_type'] != group_type::standard)
            return $this->get_stp_group_for_filter($p_id);
        return $g_r;
    }

    private function get_group_any_type($id)
    {
        $db = $this->get_gf_db();
        $new_id = $db->get_nii(false);
        if ($new_id == 1) {
            return null;
        }
        $id = ext_tools::xss_filter($id);
        $where = new where();
        $where->equally('id', $id);
        return $db->get_rows(new select_q(null, $where));
    }

    public function get_group($id)
    {
        $result = $this->get_group_any_type($id);
        return (!is_null($result) && !static::type_is_group($result[0]['group_type'])) ? null : $result;
    }

    public function get_filter($id)
    {
        $result = $this->get_group_any_type($id);
        return (!is_null($result) && !static::type_is_filter($result[0]['group_type'])) ? null : $result;
    }

    public function get_groups($order = order::asc, $offset = 0, $limit = 0, $parent_id = 0, $group_type = group_type::all)
    {
        $parent_id = intval($parent_id);
        $db = $this->get_gf_db();
        $new_id = $db->get_nii(false);
        if ($new_id == 1) {
            return null;
        }
        $where_main = new where();
        $where_main->equally('parent_id', $parent_id);

        if (static::type_is_group($group_type)) {
            $where_main->equally('filter_type', $group_type);
        } elseif ($group_type == group_type::all) {
            $ext_where = new where();
            $group_constants = ext_tools::get_constants_in_class('showyweb\qdbm\group_type');
            $group_constants_len = count($group_constants);
            $i = 0;
            foreach ($group_constants as $type) {
                if ($i == $group_constants_len - 1)
                    break;
                $ext_where->equally('group_type', $type, false);
                $i++;
            }
            $where_main->push_where($ext_where);
        }
        return $db->get_rows(new select_q(null, $where_main, null, $order, $offset, $limit));
    }

    public function get_filters($order = order::asc, $group_id, $filter_type = filter_type::all, $offset = 0, $limit = 0)
    {
        $group_id = intval($group_id);
        $db = $this->get_gf_db();
        $new_id = $db->get_nii(false);
        if ($new_id == 1) {
            return null;
        }

        $where_main = new where();
        $where_main->equally('parent_id', $group_id);
        $where_main->equally('parent_id', 0, false);
        if (static::type_is_filter($filter_type)) {
            $where_main->equally('filter_type', $filter_type);
        } elseif ($filter_type == filter_type::all) {
            $ext_where = new where();
            $filter_constants = ext_tools::get_constants_in_class('showyweb\qdbm\filter_type');
            $filter_constants_len = count($filter_constants);
            $i = 0;
            foreach ($filter_constants as $type) {
                if ($i == $filter_constants_len - 1)
                    break;
                $ext_where->equally('filter_type', $type, false);
                $i++;
            }
            $where_main->push_where($ext_where);
        }
        return $db->get_rows(new select_q(null, $where_main, null, $order, $offset, $limit));
    }

    public function get_recursive_filters($group_id)
    {
        $group_id_arr = [$group_id];
        $fg_result = $this->get_all_recursive_children_group($group_id, group_type::filter);
        if (!is_null($fg_result)) {
            foreach ($fg_result as $fg) {
                array_push($group_id_arr, $fg['id']);
            }
        }

        $f_result = [];
        foreach ($group_id_arr as $g_id) {
            $tmp_f_result = $this->get_filters(order::asc, $g_id);
            if (!is_null($tmp_f_result))
                $f_result = array_merge($f_result, $tmp_f_result);
        }
        return count($f_result) ? $f_result : null;
    }

    function get_unique_vals_in_filter($filter_id, where $where = null, $magic_quotes = true)
    {
        $filter = $this->get_filter($filter_id);
        if ($filter[0]['parent_id']) {
            $stp_group_id = $this->get_stp_group_for_filter($filter_id)[0]['id'];
            $filters_table = $this->table . "_" . $stp_group_id . "_filters";
            $db = $this->get_gf_db($filters_table);
        }
        return $db->get_unique_vals_in_column($filter[0]['column'], $where, $magic_quotes);
    }

    function get_min_and_max_in_filter($filter_id, where $where = null, $magic_quotes = true)
    {
        $filter = $this->get_filter($filter_id);
        if ($filter[0]['parent_id']) {
            $stp_group_id = $this->get_stp_group_for_filter($filter_id)[0]['id'];
            $filters_table = $this->table . "_" . $stp_group_id . "_filters";
            $db = $this->get_gf_db($filters_table);
        }
        return $db->get_min_and_max_in_column($filter[0]['column'], $where, $magic_quotes);
    }

    public function group_move_order($from, $to)
    {
        $db = $this->get_gf_db();
        $new_id = $db->get_nii();
        if ($new_id == 1) {
            return null;
        }
        $db->move_order($from, $to);
        return true;
    }

    public function group_move_orders(array $ids, array $from, array $to)
    {
        $db = $this->get_gf_db();
        $new_id = $db->get_nii();
        if ($new_id == 1) {
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
        return $this->get_all_parents_r($parent_id, []);
    }

    private function get_all_parents_r($parent_id, $out_arr)
    {
        $res = $this->get_group($parent_id);
        array_push($out_arr, $res[0]);
        $parent_id = $res[0]['parent_id'];
        if ($parent_id == 0)
            return $out_arr;
        return $this->get_all_parents_r($parent_id, $out_arr);
    }

    public function get_all_recursive_children_group($id, $group_type = group_type::all)
    {
        return $this->get_all_recursive_children_group_r($id, [], $group_type);
    }

    private function get_all_recursive_children_group_r($id, $out_arr, $group_type)
    {
        $res = $this->get_groups(order::asc, 0, 0, $id, $group_type);
        if (!is_null($res)) {
            foreach ($res as $val) {
                array_push($out_arr, $val);
                $out_arr = $this->get_all_recursive_children_group_r($val['id'], $out_arr, $group_type);
            }
        }
        return $out_arr;
    }
}
