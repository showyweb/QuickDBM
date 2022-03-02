<?
/**
 * Name:    SHOWYWeb QuickDBM
 * Version: 4.5.0
 * Author:  Novojilov Pavel Andreevich
 * Support: https://github.com/showyweb/QuickDBM
 * License: MIT license. http://www.opensource.org/licenses/mit-license.php
 * Copyright (c) 2017 Pavel Novojilov
 */

namespace showyweb\qdbm;

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
        throw new \exception($mes);
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
     * @param string|null $search Значение которое будет искаться в столбцах с помощью LIKE %$search%
     * @param array|null $search_columns Названия столбцов в которых будет производится поиск $search
     * @param array|null $map_columns Бывает такая проблема, что некоторые столбцы в sql select переименовываешь через as,
     * но во where то что объявил с помощью as не работает.
     * Поэтому через $map_columns делается сопоставление. $map_columns должен быть в таком формате array('новое имя то что через as'=>'то имя которое подставится во where')
     * @param where|null $where
     * @param callable|null $custom_filters_callback
     * @return where
     */
    static function filter_scope(string $filters = null, string $search = null, array $search_columns = null, array $map_columns = null, where $where = null, callable $custom_filters_callback = null, bool $value_quotes = true, bool $magic_quotes = true)
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
        $where = is_null($where) ? new where($value_quotes, $magic_quotes) : $where;

        if (!empty($filters)) {
            $filters = explode(';', $filters);
            $filters_kv = [];
            foreach ($filters as $i => $values) {
                if(empty($values)) {
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
                    $f_where = new where($value_quotes, $magic_quotes);

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
                            $s_f_where = new where($value_quotes, $magic_quotes);
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
                            $s_f_where = new where($value_quotes, $magic_quotes);
                            foreach ($f_values as $f_value) {
                                $f_value = explode(':', $f_value);
                                $f_values_type_filter($f_value, $f_type);
                                $s_f_where2 = new where($value_quotes, $magic_quotes);
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
            $search_where = new where($value_quotes, $magic_quotes);
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
    private $where = null;
    private $value_quotes = true;
    private $magic_quotes = true;

    /**
     * @param bool $value_quotes //Экранировать значения по умолчанию
     * @param bool $magic_quotes //Экранировать названия столбцов по умолчанию
     */
    function __construct(bool $value_quotes = true, bool $magic_quotes = true)
    {
        $this->value_quotes = $value_quotes;
        $this->magic_quotes = $magic_quotes;
        return $this;
    }

    function _get()
    {
        return $this->where;
    }

    private function push(string $text, bool $before_use_and)
    {
        if(is_null($this->where))
            $this->where = $text;
        else
            $this->where .= ($before_use_and ? ' AND ' : ' OR ') . $text;
    }


    private function gen_column(string $column, ?string $sql_function = null, ?bool $magic_quotes = null)
    {
        if (is_null($magic_quotes))
            $magic_quotes = $this->magic_quotes;
        $magic_quotes = $magic_quotes ? '`' : '';
        $sql_function = ext_tools::xss_filter($sql_function);
        return is_null($sql_function) ? $magic_quotes . $column . $magic_quotes : $sql_function . '(' . $magic_quotes . $column . $magic_quotes . ')';
    }


    function push_where(where $object, bool $before_use_and = true)
    {
        $where_text = $object->_get();
        if($where_text == "")
            return $this;
        $where_text = '(' . $where_text . ')';
        $this->push($where_text, $before_use_and);
        return $this;
    }

    function equally(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $value_quotes = null, ?bool $magic_quotes = null)
    {
        if (is_null($value_quotes))
            $value_quotes = $this->value_quotes;
        if (is_null($magic_quotes))
            $magic_quotes = $this->magic_quotes;
        if(gettype($value) == type_column::bool)
            $value = $value ? 1 : 0;
        if($xss_filter) {
            $column = ext_tools::xss_filter($column);
            $value = ext_tools::xss_filter($value);
        }
        $value_quotes = $value_quotes ? "'" : "";
        $this->push($this->gen_column($column, $sql_function, $magic_quotes) . "=$value_quotes$value$value_quotes", $before_use_and);
        return $this;
    }

    function not_equally(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $value_quotes = null, ?bool $magic_quotes = null)
    {
        if (is_null($value_quotes))
            $value_quotes = $this->value_quotes;
        if (is_null($magic_quotes))
            $magic_quotes = $this->magic_quotes;
        if(gettype($value) == type_column::bool)
            $value = $value ? 1 : 0;
        if($xss_filter) {
            $column = ext_tools::xss_filter($column);
            $value = ext_tools::xss_filter($value);
        }
        $value_quotes = $value_quotes ? "'" : "";
        $this->push($this->gen_column($column, $sql_function, $magic_quotes) . "!=$value_quotes$value$value_quotes", $before_use_and);
        return $this;
    }

    private function _in(bool $is_not_in = false, ?string $column = null, array $values = null, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $value_quotes = null, ?bool $magic_quotes = null)
    {
        if (is_null($value_quotes))
            $value_quotes = $this->value_quotes;
        if (is_null($magic_quotes))
            $magic_quotes = $this->magic_quotes;
        $value_quotes = $value_quotes ? "'" : "";
        if ($xss_filter)
            $column = ext_tools::xss_filter($column);
        foreach ($values as $i => $value) {
            if (gettype($value) == type_column::bool)
                $value = $value ? 1 : 0;
            if ($xss_filter)
                $value = ext_tools::xss_filter($value);
            $values[$i] = $value_quotes . $value . $value_quotes;
        }
        $value = implode(',', $values);
        $this->push($this->gen_column($column, $sql_function, $magic_quotes) . ($is_not_in ? ' NOT' : '') . " IN ($value)", $before_use_and);
        return $this;
    }

    function in(string $column, array $values, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $value_quotes = null, ?bool $magic_quotes = null)
    {
        if (is_null($value_quotes))
            $value_quotes = $this->value_quotes;
        if (is_null($magic_quotes))
            $magic_quotes = $this->magic_quotes;
        return $this->_in(false, $column, $values, $before_use_and, $sql_function, $xss_filter, $value_quotes, $magic_quotes);
    }

    function not_in(string $column, array $values, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $value_quotes = null, ?bool $magic_quotes = null)
    {
        if (is_null($value_quotes))
            $value_quotes = $this->value_quotes;
        if (is_null($magic_quotes))
            $magic_quotes = $this->magic_quotes;
        return $this->_in(true, $column, $values, $before_use_and, $sql_function, $xss_filter, $value_quotes, $magic_quotes);
    }

    function more(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $value_quotes = null, ?bool $magic_quotes = null)
    {
        if (is_null($value_quotes))
            $value_quotes = $this->value_quotes;
        if (is_null($magic_quotes))
            $magic_quotes = $this->magic_quotes;
        if($xss_filter) {
            $column = ext_tools::xss_filter($column);
            $value = ext_tools::xss_filter($value);
        }
        $value_quotes = $value_quotes ? "'" : "";
        $this->push($this->gen_column($column, $sql_function, $magic_quotes) . ">$value_quotes$value$value_quotes", $before_use_and);
        return $this;
    }

    function more_or_equally(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $value_quotes = null, ?bool $magic_quotes = null)
    {
        if (is_null($value_quotes))
            $value_quotes = $this->value_quotes;
        if (is_null($magic_quotes))
            $magic_quotes = $this->magic_quotes;
        if($xss_filter) {
            $column = ext_tools::xss_filter($column);
            $value = ext_tools::xss_filter($value);
        }
        $value_quotes = $value_quotes ? "'" : "";
        $this->push($this->gen_column($column, $sql_function, $magic_quotes) . ">=$value_quotes$value$value_quotes", $before_use_and);
        return $this;
    }

    function less(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $value_quotes = null, ?bool $magic_quotes = null)
    {
        if (is_null($value_quotes))
            $value_quotes = $this->value_quotes;
        if (is_null($magic_quotes))
            $magic_quotes = $this->magic_quotes;
        if($xss_filter) {
            $column = ext_tools::xss_filter($column);
            $value = ext_tools::xss_filter($value);
        }
        $value_quotes = $value_quotes ? "'" : "";
        $this->push($this->gen_column($column, $sql_function, $magic_quotes) . "<$value_quotes$value$value_quotes", $before_use_and);
        return $this;
    }


    function less_or_equally(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $value_quotes = null, ?bool $magic_quotes = null)
    {
        if (is_null($value_quotes))
            $value_quotes = $this->value_quotes;
        if (is_null($magic_quotes))
            $magic_quotes = $this->magic_quotes;
        if($xss_filter) {
            $column = ext_tools::xss_filter($column);
            $value = ext_tools::xss_filter($value);
        }
        $value_quotes = $value_quotes ? "'" : "";
        $this->push($this->gen_column($column, $sql_function, $magic_quotes) . "<=$value_quotes$value$value_quotes", $before_use_and);
        return $this;
    }

    function is_null(string $column, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true)
    {
        if($xss_filter)
            $column = ext_tools::xss_filter($column);
        $this->push($this->gen_column($column, $sql_function) . " IS NULL", $before_use_and);
        return $this;
    }

    function is_not_null(string $column, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true)
    {
        if($xss_filter)
            $column = ext_tools::xss_filter($column);
        $this->push($this->gen_column($column, $sql_function) . " IS NOT NULL", $before_use_and);
        return $this;
    }

    function partial_like(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter = true, ?bool $value_quotes = null, ?bool $magic_quotes = null)
    {
        if (is_null($value_quotes))
            $value_quotes = $this->value_quotes;
        if (is_null($magic_quotes))
            $magic_quotes = $this->magic_quotes;
        $value_quotes = $value_quotes ? "'" : "";
        if($xss_filter) {
            $column = ext_tools::xss_filter($column);
            $value = ext_tools::xss_filter($value);
        }
        $this->push($this->gen_column($column, $sql_function, $magic_quotes) . " LIKE $value_quotes%$value%$value_quotes", $before_use_and);
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
    function full_text_search_bm_not_safe(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter_column = true, ?bool $value_quotes = null, ?bool $magic_quotes = null)
    {
        if (is_null($value_quotes))
            $value_quotes = $this->value_quotes;
        if (is_null($magic_quotes))
            $magic_quotes = $this->magic_quotes;
        $value_quotes = $value_quotes ? "'" : "";
        if($xss_filter_column)
            $column = ext_tools::xss_filter($column);
        $this->push("MATCH (" . $this->gen_column($column, $sql_function, $magic_quotes) . ") AGAINST ( $value_quotes$value$value_quotes  IN BOOLEAN MODE )", $before_use_and);
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
    function regexp_not_safe(string $column, string $value, bool $before_use_and = true, ?string $sql_function = null, bool $xss_filter_column = true, ?bool $value_quotes = null, ?bool $magic_quotes = null)
    {
        if (is_null($value_quotes))
            $value_quotes = $this->value_quotes;
        if (is_null($magic_quotes))
            $magic_quotes = $this->magic_quotes;
        $value_quotes = $value_quotes ? "'" : "";
        if($xss_filter_column)
            $column = ext_tools::xss_filter($column);
        $this->push($this->gen_column($column, $sql_function, $magic_quotes) . " REGEXP $value_quotes$value$value_quotes", $before_use_and);
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
        if(!is_null($args)) {
            extract($args);
            unset($args);
        }
        $args = [];
        foreach ($parameters as $parameter) {
            if($parameter->name === "args")
                continue;
            $args[$parameter->name] = ${$parameter->name};
        }
        $this->args[] = ['add_column', $args];
        return $this;
    }

    private function _add_column($column, $as_column = null, $sql_function = null, $custom_table_name = null, $column_xss_filter = true, $column_magic_quotes = true)
    {
        $column_magic_quotes = $column_magic_quotes ? '`' : '';
        if($column == "*" or !is_null($sql_function)) $column_magic_quotes = "";
        if($column_xss_filter)
            $column = ext_tools::xss_filter($column);
        $as_column = ext_tools::xss_filter($as_column);
        $sql_function = ext_tools::xss_filter($sql_function);
        $custom_table_name = ext_tools::xss_filter($custom_table_name);
        $column = (is_null($custom_table_name) ? '' : $custom_table_name . '.') . $column_magic_quotes . $column . $column_magic_quotes;
        $column = (is_null($sql_function) ? $column : $sql_function . '(' . $column . ')') . (is_null($as_column) ? '' : ' AS `' . $as_column . '`');
        if($this->sql != "")
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
                    if(!is_null($args[1]['custom_table_name']))
                        $args[1]['custom_table_name'] = $table_prefix . $args[1]['custom_table_name'];
                    call_user_func_array(array($this, '_add_column'), $args[1]);
                    break;
                case 'add_sql':
                    if($this->sql != "")
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
     */
    function __construct($args = null, where $where = null, $order_by = '_order', $order_method = order::asc, $offset = 0, $limit = 0, select_exp $select = null, $group_by = null, left_join_on $join = null, $group_id_for_join_filters = null, $is_distinct = false)
    {
        if(!is_null($args))
            extract($args);
        $reflector = new \ReflectionClass(__CLASS__);
        $parameters = $reflector->getMethod(__FUNCTION__)->getParameters();

        foreach ($parameters as $parameter) {
            if($parameter->name === "args")
                continue;
            $this->args[$parameter->name] = ${$parameter->name};
        }
    }

    private $cur_table = null;
    private $table_prefix = null;

    private function generate_sql(where $where = null, $order_by = '_order', $order_method = order::asc, $offset = 0, $limit = 0, select_exp $select = null, $group_by = null, left_join_on $join = null, $group_id_for_join_filters = null, $is_distinct = false)
    {
        $cur_table = $this->cur_table;
        $table_prefix = $this->table_prefix;
        $order_by = ext_tools::xss_filter($order_by);
        $group_by = ext_tools::xss_filter($group_by);
        $group_id_for_join_filters = ext_tools::xss_filter($group_id_for_join_filters);

        if(!is_null($group_id_for_join_filters)) {
            $filters_table = $cur_table . "_" . $group_id_for_join_filters . "_filters";
            if(db::check_table($filters_table)) {
                if(is_null($join))
                    $join = new left_join_on;
                $join->push($cur_table, $filters_table, 'id', 'id');
            }
        }
        $order_by = ($order_by == null) ? "_order" : $order_by;
        $order_method = ($order_method == null) ? order::asc : $order_method;
        $select = is_null($select) ? null : $select->_get($table_prefix);
        $sql = "SELECT " . ($is_distinct ? "DISTINCT " : "") . (empty($select) ? '*' : $select) . " FROM `" . $cur_table . "` " . (is_null($join) ? '' : $join->_get($table_prefix) . ' ') . ((is_null($where) || is_null($where->_get())) ? "" : "WHERE " . $where->_get()) . " " . (is_null($group_by) ? '' : "GROUP BY $group_by ");
        if(!is_array($order_by))
            $order_by = [$order_by];
        $i = 0;
        foreach ($order_by as $value) {
            $o_prefix = "ORDER BY ";
            if($i !== 0)
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

        if($limit != 0) {
            $offset = intval($offset);
            $limit = intval($limit);
            $sql .= " LIMIT " . $offset . "," . $limit;
        } elseif($offset != 0)
            ext_tools::error("offset не может быть без limit");
        return $sql;
    }

    function _get($cur_table, $table_prefix)
    {
        $this->cur_table = $cur_table;
        $this->table_prefix = $table_prefix;
        return call_user_func_array(array($this, 'generate_sql'), $this->args);
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
        if(is_null($as_table_name))
            $as_table_name = $join_table;
        if(is_null($derived_table))
            $this->join .= "LEFT JOIN $join_table $as_table_name ON ($cur_table.$column_in_current_table=$as_table_name.$column_in_join_table) ";
        else
            $this->join .= "LEFT JOIN ({$derived_table->_get($join_table,"")}) $as_table_name ON ($cur_table.$column_in_current_table=$as_table_name.$column_in_join_table) ";
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
 * array('type'=>qdbm\type_column, 'is_xss_filter'=>bool,'is_add_index'=>bool)
 *
 * Ключ type - Тип столбца, устанавливается только при автосоздании столбца, если столбец существует, то значение $value только фильтруется согласно типу.
 *
 * Ключ is_xss_filter - Если true, то фильтр sql/xss включен.
 *
 * Ключ is_add_index - Если true, то при автосоздании столбца, автоматически добавляется индекс sql типа INDEX. Для типа qdbm\type_column::string, sql тип индекса будет FULLTEXT
 *
 * Например:
 *
 * class test_db_c extends qdbm\schema
 * {
 *
 * public $tab_name = "test";
 *
 * const chat_id = array('type' => qdbm\type_column::unsigned_big_int, 'is_xss_filter' => true, 'is_add_index' => true);
 *
 * const key = array('type' => qdbm\type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
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
     * qdbm\schema constructor.
     * @param string $tab_name Если не null, то переопределяет свойство $tab_name
     * @throws \exception
     */
    public function __construct($tab_name = null)
    {
        if(!is_null($tab_name))
            $this->tab_name = $tab_name;

        if(empty($this->tab_name))
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

    public function __construct(schema $qdbm_schema)
    {
        if(is_null(static::$path_cache)) {
            static::$path_cache = __DIR__ . "/.QuickDBM_cache";
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
            ext_tools::save_to_text_file(static::$path_cache, $str, null);
        }
        static::$check_column_table_cache = null;
    }

    function check_column($column)
    {
        if(is_null(static::$check_column_table_cache)) {
            $str = ext_tools::open_txt_file(static::$path_cache, null);
            static::$check_column_table_cache = is_null($str) ? [] : unserialize($str);
        }
        $c_key = static::$mysqli_auth['db_name'] . '.' . $this->table;
        if (!isset(static::$check_column_table_cache[$c_key])) {
            static::$check_column_table_cache[$c_key] = [];
            static::$cache_is_modified = true;
        }
        $name = $column;
        $link = static::get_mysqli_link();
        $name = ext_tools::xss_filter($name);
        if (isset(static::$check_column_table_cache[$c_key][$name]))
            return static::$check_column_table_cache[$c_key][$name];
        $sql = "SHOW COLUMNS FROM `" . $this->table . "` LIKE '" . $name . "'";
        $result = $link->query($sql);
        if($link->errno !== 0)
            ext_tools::error($link->error . " sql:" . $sql);
        $itog = $result->fetch_assoc();

        if(is_null($itog))
            return false;
        else
            static::$check_column_table_cache[$c_key][$name] = true;
        static::$cache_is_modified = true;
        return static::$check_column_table_cache[$c_key][$name];
    }

    static function get_mysqli_link()
    {
        $mysqli_link = &static::$mysqli_link;
        if(!is_null($mysqli_link))
            return $mysqli_link;
        $mysqli = &static::$mysqli_auth;
        if(!isset($mysqli["host"]))
            ext_tools::error("Не указаны даннае авторизации mysql");
        $mysqli_link = new \mysqli($mysqli["host"], $mysqli["user"], $mysqli["password"]);
        if(!$mysqli_link)
            ext_tools::error("В настоящее время сервер не может подключиться к базе данных...");
        if(!$mysqli_link) exit(mysqli_error($mysqli_link));
        /* check connection */
        if(mysqli_connect_errno()) {
            ext_tools::error("Ошибка подключения: " . mysqli_connect_error());
        }
        mysqli_query($mysqli_link, "set character_set_client	='utf8mb4'");
        mysqli_query($mysqli_link, "set character_set_results='utf8mb4'");
        mysqli_query($mysqli_link, "set collation_connection	='utf8mb4_general_ci'");
        mysqli_query($mysqli_link, "SET lc_time_names='ru_UA'");
        if($stmt = mysqli_prepare($mysqli_link, "set character_set_client=?")) {
            $utf8 = 'utf8mb4';
            mysqli_stmt_bind_param($stmt, "s", $utf8);
            $result = mysqli_stmt_execute($stmt);
        }
        if($stmt = mysqli_prepare($mysqli_link, "set character_set_results=?")) {
            $utf8 = 'utf8mb4';
            mysqli_stmt_bind_param($stmt, "s", $utf8);
            $result = mysqli_stmt_execute($stmt);
        }
        if($stmt = mysqli_prepare($mysqli_link, "set collation_connection=?")) {
            $utf8_general_ci = 'utf8mb4_general_ci';
            mysqli_stmt_bind_param($stmt, "s", $utf8_general_ci);
            $result = mysqli_stmt_execute($stmt);
        }
        if($stmt = mysqli_prepare($mysqli_link, "SET lc_time_names=?")) {
            $utf8_general_ci = 'ru_UA';
            mysqli_stmt_bind_param($stmt, "s", $utf8_general_ci);
            $result = mysqli_stmt_execute($stmt);
        }
        if (!$mysqli_link->set_charset("utf8mb4"))
            ext_tools::error("Ошибка при загрузке набора символов utf8mb4: " . $mysqli->error);
        $select_status = $mysqli_link->select_db($mysqli["db_name"]);
        if(!$select_status)
            static::set_db_name($mysqli["db_name"], $mysqli_link);
        if(function_exists('mysqlnd_ms_set_qos'))
            mysqlnd_ms_set_qos($mysqli_link, MYSQLND_MS_QOS_CONSISTENCY_EVENTUAL, MYSQLND_MS_QOS_OPTION_AGE, 0);
        return $mysqli_link;
    }

    static function raw_sql($sql, $return_array = false)
    {
        $link = static::get_mysqli_link();
        static::$check_column_table_cache = [];
        $result = $link->query($sql);
        if($link->errno !== 0)
            ext_tools::error($link->error . " sql:" . $sql);
        if(!$return_array)
            return $result;
        $itog_ = $result->fetch_all(MYSQLI_ASSOC);
        return $itog_;
    }

    static function check_table($check_table)
    {
        $c_key = static::$mysqli_auth['db_name'] . '.' . $check_table;
        if (!is_null(static::$check_column_table_cache) && isset(static::$check_column_table_cache[$c_key]))
            return true;
        $link = static::get_mysqli_link();
        $check_table = ext_tools::xss_filter($check_table);
        if(($check_table_res = mysqli_query($link, 'SHOW COLUMNS FROM ' . $check_table)) and isset($check_table_res) and mysqli_fetch_assoc($check_table_res))
            return true;
        else
            return false;
    }

    static function check_db_name($db_name)
    {
        $link = static::get_mysqli_link();
        $db_name = ext_tools::xss_filter($db_name);
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
        $db_name = ext_tools::xss_filter($db_name);
        if(!static::check_db_name($db_name)) {
            $sql = "CREATE DATABASE `" . $db_name . "` CHARACTER SET utf8mb4 COLLATE utf8_general_ci";
            $link->query($sql);
            if($link->errno !== 0)
                ext_tools::error($link->error . " sql:" . $sql);

        }
        if(!$link->select_db($db_name)) {
            if($link->errno !== 0)
                ext_tools::error($link->error);
        }
    }

    private function set_table($table)
    {
        $link = static::get_mysqli_link();
        $table = ext_tools::xss_filter($table);
        $table_prefix = static::$mysqli_auth['table_prefix'];
        if(!empty($table_prefix))
            $table = $table_prefix . $table;
        if(!static::check_table($table)) {
            $sql = "CREATE TABLE IF NOT EXISTS `" . $table . "` (`id` bigint(255) unsigned  NOT NULL,`_order` bigint(255) unsigned NOT NULL, UNIQUE `id` (`id`), INDEX `_order` (`_order`))
            ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $link->query($sql);
            if($link->errno !== 0)
                ext_tools::error($link->error . " sql:" . $sql);
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
        $table = ext_tools::xss_filter($table);
        $sql = "DROP TABLE `$table`";
        $result = $link->query($sql);
        if($link->errno !== 0)
            ext_tools::error($link->error . " sql:" . $sql);
    }

    function get_raw_type_column($column)
    {
        $name = $column;
        $link = static::get_mysqli_link();
        $name = ext_tools::xss_filter($name);
        $sql = "SHOW COLUMNS FROM `" . $this->table . "` LIKE '" . $name . "'";
        $result = $link->query($sql);
        if($link->errno !== 0)
            ext_tools::error($link->error . " sql:" . $sql);
        $itog = $result->fetch_assoc();
        if(is_null($itog))
            return null;
        return $itog['type'];

    }

    function add_column($column, $type, $is_add_index)
    {
        $name = $column;
        static::$check_column_table_cache = null;
        $sql = '';
        switch ($type) {
            case type_column::small_string:
                $sql = "ALTER TABLE `" . $this->table . "`  ADD `" . $name . "` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL";
                break;
            case type_column::string:
                $sql = "ALTER TABLE `" . $this->table . "`  ADD `" . $name . "` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL";
                break;
            case type_column::decimal_auto:
                $tmp_decimal_size = ext_tools::decimal_size("1");
                $tmp_int_size = $tmp_decimal_size[0];
                $tmp_scale_size = $tmp_decimal_size[1];
                $sql = "ALTER TABLE `" . $this->table . "` ADD `" . $name . "` DECIMAL($tmp_int_size,$tmp_scale_size) NULL DEFAULT NULL;";
                break;
            case type_column::int:
                $sql = "ALTER TABLE `" . $this->table . "` ADD `" . $name . "` INT(255) NULL DEFAULT NULL";
                break;
            case type_column::big_int:
                $sql = "ALTER TABLE `" . $this->table . "` ADD `" . $name . "` BIGINT(255) NULL DEFAULT NULL";
                break;
            case type_column::unsigned_int:
                $sql = "ALTER TABLE `" . $this->table . "` ADD `" . $name . "` INT(255) unsigned NULL DEFAULT NULL";
                break;
            case type_column::unsigned_big_int:
                $sql = "ALTER TABLE `" . $this->table . "` ADD `" . $name . "` BIGINT(255) unsigned NULL DEFAULT NULL";
                break;
            case type_column::bool:
                $sql = "ALTER TABLE `" . $this->table . "` ADD `" . $name . "` BOOLEAN NULL DEFAULT NULL";
                break;
            case type_column::datetime:
                $sql = "ALTER TABLE `" . $this->table . "` ADD `" . $name . "` DATETIME NULL DEFAULT NULL";
                break;
        }
        if($is_add_index)
            switch ($type) {
                case type_column::string:
                    $sql .= ' , ADD FULLTEXT `' . $name . '` (`' . $name . '`)';
                    break;
                default:
                    $sql .= ' , ADD INDEX `' . $name . '` (`' . $name . '`)';
                    break;
            }
        $link = static::get_mysqli_link();
        $link->query($sql);
        if($link->errno !== 0)
            ext_tools::error($link->error . " sql:" . $sql);
    }

    function remove_column($column)
    {
        $name = $column;
        $link = static::get_mysqli_link();
        $c_key = static::$mysqli_auth['db_name'] . '.' . $this->table;
        if (isset(static::$check_column_table_cache[$c_key]) and isset(static::$check_column_table_cache[$c_key][$name]))
            unset(static::$check_column_table_cache[$c_key][$name]);
        $name = ext_tools::xss_filter($name);
        $sql = "ALTER TABLE `" . $this->table . "` DROP `" . $name . "`";
        $link->query($sql);
        if($link->errno !== 0)
            ext_tools::error($link->error . " sql:" . $sql);
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
     * @return int
     * @throws \exception
     * @see smart_write_lock
     */
    function get_new_insert_id($is_auto_write_lock = true)
    {
        if($is_auto_write_lock)
            $this->smart_write_lock();
        $sql = "SELECT `id` FROM " . $this->table . " ORDER BY `id` DESC LIMIT 0 , 1";
        $link = static::get_mysqli_link();
        $result = $link->query($sql);
        if($link->errno !== 0)
            ext_tools::error($link->error . " sql:" . $sql);
        $itog = $result->fetch_assoc();
        if($itog !== null)
            $new_id = $itog["id"] + 1;
        else
            $new_id = 1;
        return $new_id;
    }


    /**
     * @param array $records Список записей в виде ["column"=>'value', "column2"=>'value2']
     * @param integer $insert_id Идентификатор строки в таблице. Если строка не найдена, то она вставляется как новая. Если параметр $where, не null, то $insert_id игнорируется
     * @param where|null $where [optional] Альтернативное условие в запросе, по умолчанию при обновлении записи используется `id`=$insert_id
     * @throws \exception
     */
    function insert($records, $insert_id, where $where = null)
    {
        $link = static::get_mysqli_link();
        $id = $insert_id;
        $id = ext_tools::xss_filter($id);
        if(is_null($where)) {
            if(is_null($id))
                ext_tools::error('id null');

            $sql = "SELECT `id` FROM `" . $this->table . "` WHERE `id` = '" . $id . "' LIMIT 0,1";
            $result = $link->query($sql);
            if($link->errno !== 0)
                ext_tools::error($link->error . " sql:" . $sql);
            $itog = $result->fetch_assoc();
            if(is_null($itog)) {
                $tmp_w_l = static::$write_locked;
                if(!$tmp_w_l)
                    $this->smart_write_lock();
                $_order = 0;
                if($id == 1)
                    $_order = 1;
                else {
                    $args = [
                        'order_by' => '_order',
                        'order_method' => order::desc,
                        'select' => (new select_exp())->add_column(null, '_order', 'order_max', 'MAX'),
                        'group_by' => '_order'
                    ];
                    $res = static::get_rows(new select_q($args));
                    $_order = $res[0]['order_max'] + 1;
                }
                $sql = "INSERT INTO `" . $this->table . "` SET `id`='" . $id . "', `_order`='" . $_order . "'";
                $link->query($sql);
                if($link->errno !== 0)
                    ext_tools::error($link->error . " sql:" . $sql);
                if(!$tmp_w_l)
                    $this->unlock_tables();
            }
        }


        $sql = "";
        $s_values = [];
        $bind_params = [""];
        $i = 0;
        foreach ($records as $key => $value) {
            $column_inf = $this->columns[$key];
            $name = $column_inf['name'];
            $type = $column_inf['type'];
            $is_xss_filter = $column_inf['is_xss_filter'];
            $is_add_index = $column_inf['is_add_index'];
            $name = ext_tools::xss_filter($name);
            switch ($type) {
                case type_column::small_string:
                case type_column::string:
                case type_column::datetime:
                case type_column::decimal_auto:
                    if($is_xss_filter)
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
                    $value = ($value === "1" or $value === "0") ? $value : $value ? "1" : "0";
                    break;
                default:
                    if($is_xss_filter)
                        $value = ext_tools::xss_filter($value);
                    break;
            }

            if($type == type_column::decimal_auto) {
                $tmp_decimal_size = ext_tools::decimal_size($value);
                $tmp_int_size = $tmp_decimal_size[0];
                $tmp_scale_size = $tmp_decimal_size[1];
                $raw_type = db::get_raw_type_column($name);
                if(!preg_match('/decimal\((\d+).(\d+)\)/ui', $raw_type, $matches))
                    ext_tools::error("$name not decimal type");

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
                        ext_tools::error($link->error . " sql:" . $sql);
                }
            }

            $sql .= ", `" . $name . "`=?";

            if($value === 0)
                $value = "0";
            if($value == "")
                $value = null;
            $bind_params[0] .= "s";
            $s_values[] = $value;
            $bind_params[] = &$s_values[$i];
            $i++;
        }
        $sql = substr($sql, 1);
        $sql = "UPDATE `" . $this->table . "` SET" . $sql . " WHERE " . (is_null($where) ? "`id` = '" . $id . "'" : $where->_get());

        $link->stmt_init();
        $stmt = $link->prepare($sql);
        if($stmt->errno !== 0)
            ext_tools::error($stmt->error . " sql:" . $sql);
        call_user_func_array(array($stmt, 'bind_param'), $bind_params);
        $stmt->execute();
        unset($s_values, $bind_params);
        if($stmt->errno !== 0)
            ext_tools::error($stmt->error . " sql:" . $sql);
    }

    function remove_rows(where $where)
    {
        $link = static::get_mysqli_link();
        $sql = "DELETE FROM `" . $this->table . "` WHERE " . $where->_get();
        $link->query($sql);
        if($link->errno !== 0)
            ext_tools::error($link->error . " sql:" . $sql);
        return true;
    }

    function get_count(where $where = null, $column = "id", $is_distinct = false, $magic_quotes = true)
    {
        $link = static::get_mysqli_link();
        $magic_quotes = $magic_quotes ? '`' : '';
        $column = $magic_quotes . $column . $magic_quotes;
        $sql = "SELECT COUNT(" . ($is_distinct ? "DISTINCT " : "") . "$column) AS `_count` FROM `" . $this->table . "`";
        if(!is_null($where) and !is_null($where->_get()))
            $sql .= "WHERE " . $where->_get();
        $result = $link->query($sql);
        if($link->errno !== 0)
            ext_tools::error($link->error . " sql:" . $sql);

        $val = $result->fetch_assoc();
        return intval($val["_count"]);
    }

    /*
     *@return array|null
     * */
    function get_rows(select_q $select_query = null)
    {
        if(is_null($select_query))
            $select_query = new select_q();
        $link = static::get_mysqli_link();
        $table_prefix = static::$mysqli_auth['table_prefix'];
        $cur_table = $this->get_table_name();
        $sql = $select_query->_get($cur_table, $table_prefix);
        $result = $link->query($sql);
        if($link->errno !== 0)
            ext_tools::error($link->error . " sql:" . $sql);
        $itog_ = $result->fetch_all(MYSQLI_ASSOC);
        return count($itog_) ? $itog_ : null;
    }

    function get_unique_vals_in_column($column, where $where = null, $magic_quotes = true)
    {
        $column = ext_tools::xss_filter($column);
        $magic_quotes = $magic_quotes ? '`' : '';
        $sql = "SELECT DISTINCT $magic_quotes$column$magic_quotes FROM `" . $this->table . "`";
        if(!is_null($where))
            $sql .= "WHERE " . $where->_get();
        $result = static::raw_sql($sql, true);
        return is_null($result) ? null : (isset($result[$column]) ? $result[$column] : $result[0]);
    }

    function get_min_and_max_in_column($column, where $where = null, $magic_quotes = true)
    {
        $column = ext_tools::xss_filter($column);
        $magic_quotes = $magic_quotes ? '`' : '';
        $select = new select_exp();
        $select->add_column([
            'column' => 'IFNULL(MIN(' . $magic_quotes . $column . $magic_quotes . '),0)',
            'as_column' => 'min',
            'column_xss_filter' => false,
            'column_magic_quotes' => false
        ]);
        $select->add_column([
            'column' => 'IFNULL(MAX(' . $magic_quotes . $column . $magic_quotes . '),0)',
            'as_column' => 'max',
            'column_xss_filter' => false,
            'column_magic_quotes' => false
        ]);
        $res = $this->get_rows(new select_q(null, $where, null, null, 0, 0, $select));
        $min = $res[0]['min'];
        $max = $res[0]['max'];
        if(is_null($max) or is_null($min))
            return null;
        return array(intval($min), intval($max));
    }


    function format_ids_in_table($id = "id")
    {
        $link = static::get_mysqli_link();
        $id = ext_tools::xss_filter($id);
        $sql = "UPDATE `" . $this->table . "` SET `$id`=(SELECT @a:=@a+1 FROM (SELECT @a:=0) i)";
        $link->query($sql);
        if($link->errno !== 0)
            ext_tools::error($link->error . " sql:" . $sql);
        return true;
    }

    /**
     * Ставит блокировку типа WRITE на активные таблицы (таблица добавляется в активные при вызове new qdbm)
     *
     * Повторная блокировка запрещена, так как приводит к автоматической предварительной разблокировке таблиц. Если она вам нужна, то предварительно вызовите unlock_tables. Однако, исключение не будет вызываться, если текущая таблица была раннее заблокирована.
     * @throws \exception
     * @link https://dev.mysql.com/doc/refman/5.7/en/lock-tables.html
     * @see unlock_tables
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
        if(static::$write_locked) {
            if(in_array($this_table, static::$write_locked_arr))
                return;
            ext_tools::error("Re-lock is forbidden");
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
            ext_tools::error($link->error . " sql:" . $sql);
    }

    function unlock_tables()
    {
        return static::s_unlock_tables();
    }

    static function s_unlock_tables()
    {
        $link = static::get_mysqli_link();
        $sql = "UNLOCK TABLES";
        $link->query($sql);
        if($link->errno !== 0)
            ext_tools::error($link->error . " sql:" . $sql);
        static::$write_locked = false;
        static::$write_locked_arr = [];
        return true;
    }

    function close_connection()
    {
        static::s_close_connection();
    }

    function s_close_connection()
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
        $where = new where();
        $where->equally('_order', $from);
        $where->equally('_order', $to, false);
        $result = $this->get_rows(new select_q(null, $where));
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
            ext_tools::error($link->error . " sql:" . $sql);
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
                $where = new where();
                $where->equally('id', $ids[$i]);
                $where->equally('_order', $from[$i]);
                if(is_null($this->get_rows(new select_q(null, $where)))) {
                    $this->unlock_tables();
                    return false;
                }

            }
        }
        $rec = [];
        for ($i = 0; $i < $len; $i++) {
            if($from[$i] != $to[$i]) {
                $where = new where();
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
        $sql_text = ext_tools::open_txt_file($file_name, null);
        if(is_null($sql_text))
            ext_tools::error('error import sql file ' . $file_name);
        $link->multi_query($sql_text);
        if($link->errno !== 0)
            ext_tools::error($link->error);

        do {
            $link->use_result();
        } while ($link->more_results() && $link->next_result());
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

        $group_constants = ext_tools::get_constants_in_class('qdbm\group_type');
        foreach ($group_constants as $type) {
            if($type == $group_type and $group_type != group_type::all)
                return true;
        }
        return false;
    }

    static function type_is_filter($filter_type)
    {
        $filter_constants = ext_tools::get_constants_in_class('qdbm\filter_type');
        foreach ($filter_constants as $type) {
            if($type == $filter_type and $filter_type != filter_type::all)
                return true;
        }
        return false;
    }

    function add_group($title, $description, $parent_id = 0, $group_type = group_type::standard)
    {
        if(!static::type_is_group($group_type)) {
            ext_tools::error('Недопустимый тип группы');
            return false;
        }
        if($parent_id != 0) {
            $res = $this->get_group($parent_id);
            if($res[0]['group_type'] == group_type::standard and $group_type != group_type::filter) {
                ext_tools::error('Нельзя добавить подгруппу в стандартную группу');
                return false;
            }
            if($res[0]['group_type'] == group_type::expand and $group_type == group_type::filter) {
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
        if(!static::type_is_filter($filter_type)) {
            ext_tools::error('Недопустимый тип фильтра');
            return false;
        }
        if($group_id) {
            $res = $this->get_group($group_id);
            if($res[0]['group_type'] == group_type::expand) {
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
            if(isset($filers_vals[$column])) {
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
        if(!is_null($res) and !static::type_is_group($res[0]['group_type']))
            ext_tools::error("Группы не существует");
        if($res == null and !$force_edit)
            ext_tools::error("Группы не существует");
        return $this->group($id, $title, $description, $parent_id, $force_edit ? group_type::standard : $res[0]['group_type']);
    }

    private function group($id = null, $title, $description, $parent_id, $group_type, $column = null)
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
            'column' => ''
        ];

        $column_type = null;
        if(static::type_is_group($group_type))
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
        if(!is_null($column_type)) {
            if(is_null($column))
                $column = "filter_" . $new_id;
            $stp_group = $parent_id ? $this->get_stp_group_for_filter($parent_id) : null;
            $records = [
                'group_type' => $group_type,
                'column' => $column
            ];
            $db->insert($records, $new_id);
            $filter_table = null;
            $table = $this->table;
            if($parent_id) {
                if(is_null($stp_group))
                    ext_tools::error('$stp_group==null');
                $fg_id = $stp_group[0]['id'];
                $filter_table = $table . "_" . $fg_id . "_filters";
            } else
                $filter_table = $table;
            $db = $this->get_gf_db($filter_table);
            if(!$db->check_column($column))
                $db->add_column($column, gf_schema::column['type'], gf_schema::column['is_add_index']);
        }
        return $new_id;
    }

    function remove_group($id)
    {

        $id = ext_tools::xss_filter($id);
        $group_inf = $this->get_group($id);
        if($group_inf == null)
            ext_tools::error("Такой группы не существует");
        $this->remove_group_or_filter($id, $group_inf);
        return true;
    }

    function remove_filter($id)
    {
        $id = ext_tools::xss_filter($id);
        $group_inf = $this->get_filter($id);
        if($group_inf == null)
            ext_tools::error("Такого фильтра не существует");
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
                if($db->check_column($group_inf[0]['column']))
                    $db->remove_column($group_inf[0]['column']);
            }
        }

        $childrens = $this->get_groups(order::asc, 0, 0, $id);
        if(is_null($childrens))
            $childrens = array();
        $filters = $this->get_filters(order::asc, $id);
        if(is_null($filters))
            $filters = array();
        $childrens = array_merge($childrens, $filters);
        foreach ($childrens as $val) {
            if(!(static::type_is_filter($val['group_type']) and $val['parent_id'] == "0")) {
                $this->remove_group_or_filter($val['id']);
            }

        }

        $db = $this->get_gf_db();
        $where = new where();
        $where->equally('id', $id);
        $db->remove_rows($where);
        if($group_inf[0]['parent_id'] and static::type_is_filter($group_inf[0]['qdbm_group_type']) and is_null($this->get_recursive_filters($stp_group_id)))
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
        if(is_null($g_r))
            ext_tools::error('$g_r==null');
        $p_id = $g_r[0]['parent_id'];
        if($g_r[0]['group_type'] != group_type::standard)
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
        $id = ext_tools::xss_filter($id);
        $where = new where();
        $where->equally('id', $id);
        $result = $db->get_rows(new select_q(null, $where));
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

    public function get_groups($order = order::asc, $offset = 0, $limit = 0, $parent_id = 0, $group_type = group_type::all)
    {
        $parent_id = intval($parent_id);
        $db = $this->get_gf_db();
        $new_id = $db->get_nii(false);
        if($new_id == 1) {
            return null;
        }
        $where_main = new where();
        $where_main->equally('parent_id', $parent_id);

        if(static::type_is_group($group_type)) {
            $where_main->equally('filter_type', $group_type);
        } elseif($group_type == group_type::all) {
            $ext_where = new where();
            $group_constants = ext_tools::get_constants_in_class('qdbm\group_type');
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
        $result = $db->get_rows(new select_q(null, $where_main, null, $order, $offset, $limit));
        return $result;
    }

    public function get_filters($order = order::asc, $group_id, $filter_type = filter_type::all, $offset = 0, $limit = 0)
    {
        $group_id = intval($group_id);
        $db = $this->get_gf_db();
        $new_id = $db->get_nii(false);
        if($new_id == 1) {
            return null;
        }

        $where_main = new where();
        $where_main->equally('parent_id', $group_id);
        $where_main->equally('parent_id', 0, false);
        if(static::type_is_filter($filter_type)) {
            $where_main->equally('filter_type', $filter_type);
        } elseif($filter_type == filter_type::all) {
            $ext_where = new where();
            $filter_constants = ext_tools::get_constants_in_class('qdbm\filter_type');
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
        $result = $db->get_rows(new select_q(null, $where_main, null, $order, $offset, $limit));

        return $result;
    }

    public function get_recursive_filters($group_id)
    {
        $group_id_arr = array($group_id);
        $fg_result = $this->get_all_recursive_children_group($group_id, group_type::filter);
        if(!is_null($fg_result)) {
            foreach ($fg_result as $fg) {
                array_push($group_id_arr, $fg['id']);
            }
        }

        $f_result = array();
        foreach ($group_id_arr as $g_id) {
            $tmp_f_result = $this->get_filters(order::asc, $g_id);
            if(!is_null($tmp_f_result))
                $f_result = array_merge($f_result, $tmp_f_result);
        }
        return count($f_result) ? $f_result : null;
    }

    function get_unique_vals_in_filter($filter_id, where $where = null, $magic_quotes = true)
    {
        $filter = $this->get_filter($filter_id);
        if($filter[0]['parent_id']) {
            $stp_group_id = $this->get_stp_group_for_filter($filter_id)[0]['id'];
            $filters_table = $this->table . "_" . $stp_group_id . "_filters";
            $db = $this->get_gf_db($filters_table);
        }
        $res = $db->get_unique_vals_in_column($filter[0]['column'], $where, $magic_quotes);
        return $res;
    }

    function get_min_and_max_in_filter($filter_id, where $where = null, $magic_quotes = true)
    {
        $filter = $this->get_filter($filter_id);
        if($filter[0]['parent_id']) {
            $stp_group_id = $this->get_stp_group_for_filter($filter_id)[0]['id'];
            $filters_table = $this->table . "_" . $stp_group_id . "_filters";
            $db = $this->get_gf_db($filters_table);
        }
        $res = $db->get_min_and_max_in_column($filter[0]['column'], $where, $magic_quotes);
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

    public function get_all_recursive_children_group($id, $group_type = group_type::all)
    {
        return $this->get_all_recursive_children_group_r($id, array(), $group_type);
    }

    private function get_all_recursive_children_group_r($id, $out_arr, $group_type)
    {
        $res = $this->get_groups(order::asc, 0, 0, $id, $group_type);
        if(!is_null($res)) {
            foreach ($res as $val) {
                array_push($out_arr, $val);
                $out_arr = $this->get_all_recursive_children_group_r($val['id'], $out_arr, $group_type);
            }
        }
        return $out_arr;
    }
}