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
        public $tab_name = "mb_last_command";
        const chat_id = array('type' => qdbm_type_column::unsigned_big_int, 'is_xss_filter' => true, 'is_add_index' => true);
        const key = array('type' => qdbm_type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
        const last_command = array('type' => qdbm_type_column::small_string, 'is_xss_filter' => false, 'is_add_index' => false);
        const last_modify = array('type' => qdbm_type_column::datetime, 'is_xss_filter' => false, 'is_add_index' => true);
}

class last_command //Пример класса с использованием QuickDBM
{
       private $db = null;
        public $db_c = null;
    
        /** @noinspection PhpMissingParentConstructorInspection */
        public function __construct()
        {
            $this->db_c = new last_command_db_c();
            $this->db = new qdbm($this->db_c);
        }
    
        private function gen_where($chat_id = null, $key = null)
        {
            $where = new qdbm_where();
            if(!is_null($chat_id))
                $where->equally('chat_id', $chat_id);
            if(!is_null($key))
                $where->equally('key', $key);
            else
                $where->is_null('key');
            return $where;
        }
    
        function get($chat_id, $key = null, $is_raw_return = false)
        {
            $db = $this->db;
            $where = $this->gen_where($chat_id, $key);
            $res = $db->get_rows(null, $where);
            if($is_raw_return)
                return $res;
            return is_null($res) ? null : $res[0]['last_command'];
        }
    
        function set($chat_id, $val, $key = null, $xss_filter = true)
        {
            $db = $this->db;
            if($xss_filter)
                $val = $this->et::xss_filter($val);
            $new_id = $db->get_nii();
            $res = $this->get($chat_id, $key, true);
            if(!is_null($res))
                $new_id = $res[0]['id'];
    
            $rec = [
                'chat_id' => $chat_id,
                'last_command' => $val,
                'key' => $key,
                'last_modify' => qdbm_ext_tools::get_current_datetime()
            ];
            $db->insert($rec, $new_id);
        }
    
        function del($chat_id, $key = null)
        {
            $db = $this->db;
            $where = $this->gen_where($chat_id, $key);
            $db->remove_rows($where);
        }
    
        function clear()
        {
            $time_filter = 24 * 60 * 60;
            $cur_time = time();
            ini_set('max_execution_time', 0);
            $time_lock_path = 'last_command_time_lock';
            if(file_exists($time_lock_path) && bcsub($cur_time, filemtime($time_lock_path)) < 1 * 60 * 60)
                return;
            file_put_contents($time_lock_path, '');
            $db = $this->db;
            $where = new qdbm_where();
            $where->less('last_modify', "DATE_SUB(NOW(), INTERVAL $time_filter SECOND)", true, null, false, false, false);
            $db->remove_rows($where);
        }
}
```
