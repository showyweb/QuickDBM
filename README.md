# QuickDBM
Поможет ускорить разработку при использовании БД MySQL. Таблицы генерируются автоматически, по мере необходимости.
# Использование
```php
<?php
require_once "QuickDBM.php";
qdbm::set_mysqli_auth([
        'table_prefix' => '',
        'db_name' => '',
        'host' => '',
        'user' => '',
        'password' => ''
    ]);//Настройка подключения к БД

class last_command_db_c //В этом классе описывается структура одной из таблиц.
{
    static $tab_name = "last_command";//Название таблицы
    const chat_id = array('name' => 'chat_id', 'type' => qdbm_type_column::unsigned_big_int, 'xss_filter_in_value' => true, 'new_column_add_index' => true);
    const key = array('name' => 'key', 'type' => qdbm_type_column::small_string, 'xss_filter_in_value' => true, 'new_column_add_index' => true);
    const last_command = array('name' => 'last_command', 'type' => qdbm_type_column::small_string, 'xss_filter_in_value' => true, 'new_column_add_index' => false);
    const last_modify = array('name' => 'last_modify', 'type' => qdbm_type_column::datetime, 'xss_filter_in_value' => false, 'new_column_add_index' => true);
}

class last_command //Пример класса с использованием QuickDBM
{
    function unit_res($res)
    {
        return is_null($res) ? $res : $res[0];
    }

    function get($chat_id, $key = null)
    {
        $new_id = qdbm::set_table(last_command_db_c::$tab_name);
        if($new_id == 1)
            return null;
        $where = new qdbm_where();
        $where->equally(last_command_db_c::chat_id, $chat_id);
        if(!is_null($key))
            $where->equally(last_command_db_c::key, $key);
        $res = qdbm::get_rows();
        return $this->unit_res($res);
    }

    function set($chat_id, $val, $key = null)
    {
        $new_id = qdbm::set_table(last_command_db_c::$tab_name);
        $res = $this->get($chat_id);
        if(!is_null($res))
            $new_id = $res[qdbm_column_names::id];
        qdbm::set_insert_id($new_id);
        qdbm::insert(last_command_db_c::chat_id, $chat_id);
        qdbm::insert(last_command_db_c::last_command, $val);
        if(!is_null($key))
            qdbm::insert(last_command_db_c::key, $key);
        qdbm::insert(last_command_db_c::last_modify, qdbm_ext_tools::get_current_datetime());
    }

    function clear()
    {
        $time_filter = 60 * 60;
        $new_id = qdbm::set_table(last_command_db_c::$tab_name);
        if($new_id == 1)
            return;
        $where = new qdbm_where();
        $where->less(last_command_db_c::last_modify, "DATE_SUB(NOW(), INTERVAL $time_filter SECOND)", true, null, false, false, false);
        qdbm::remove_rows($where);
    }
}
```
