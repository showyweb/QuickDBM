# QuickDBM
Поможет ускорить разработку при использовании БД MySQL. Таблицы генерируются автоматически, по мере необходимости.
# Установка
```
composer require showyweb/quick-dbm
```
# Использование
```php
<?php
require_once 'vendor/autoload.php';
use showyweb\qdbm\{db, schema, type_column, ext_tools as et, where, select_q, select_exp, left_join_on, order, filter_type};
db::set_pdo_auth([
        'db_name' => '',
        'host' => '',
        'user' => '',
        'password' => '',
        'table_prefix' => ''
    ]);//Настройка подключения к БД

class dyn_options_db_c extends schema //В этом классе описывается структура одной из таблиц.
{
    public $tab_name = "";
    const key = array('type' => type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
    const val = array('type' => type_column::string, 'is_xss_filter' => false, 'is_add_index' => true);
}

class dyn_options //Пример класса с использованием QuickDBM
{
    private $db = null;
    public $db_c = null;

    public function __construct()
    {
        $mn = get_current_module_name(1);
        $this->db_c = new dyn_options_db_c($mn . '_dyn_options');
        $this->db = new db($this->db_c);
    }

    function get($key, $is_raw_return = false)
    {
        $db = $this->db;
        $where = new where();
        $where->equally('key', $key);
        $res = $db->get_rows(new select_q(null, $where));
        if($is_raw_return)
            return $res;
        return is_null($res) ? null : $res[0]['val'];
    }

    function set($key, $val, $xss_filter = true)
    {
        if($xss_filter)
            $val = et::xss_filter($val);
        $db = $this->db;
        $res = $this->get($key, true);
        $rec = [
            'key' => $key,
            'val' => $val
        ];
        // Если запись существует - обновляем её, иначе создаём новую
        if(!is_null($res)) {
            $db->insert($rec, $res[0]['id']);
        } else {
            $db->insert($rec); // AUTO_INCREMENT создаст новый ID
        }
    }

    function del($key)
    {
        $db = $this->db;
        $res = $this->get($key, true);
        if(is_null($res))
            error("$key not found");
        $where = new where();
        $where->equally('key', $key);
        $db->remove_rows($where);
    }
}
```
