<?php

namespace showyweb\qdbm\tests;

use PHPUnit\Framework\TestCase;
use showyweb\qdbm\db;
use showyweb\qdbm\schema;
use showyweb\qdbm\type_column;
use showyweb\qdbm\where;
use showyweb\qdbm\select_q;
use showyweb\qdbm\order;

/**
 * Тестовая схема для интеграционных тестов
 */
class TestProductSchema extends schema
{
    public $tab_name = "test_products";

    const name = array('type' => type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
    const price = array('type' => type_column::unsigned_int, 'is_xss_filter' => true, 'is_add_index' => false);
    const quantity = array('type' => type_column::unsigned_int, 'is_xss_filter' => true, 'is_add_index' => false);
    const is_available = array('type' => type_column::bool, 'is_xss_filter' => true, 'is_add_index' => false);
    const description = array('type' => type_column::string, 'is_xss_filter' => false, 'is_add_index' => false);
}

/**
 * Интеграционные тесты для класса db
 *
 * По умолчанию использует SQLite в памяти (:memory:) - быстро и без внешних зависимостей.
 * Для тестирования с MySQL установите переменную окружения DB_DRIVER=mysql.
 *
 * Переменные окружения для SQLite (используются по умолчанию):
 * - DB_DRIVER=sqlite (по умолчанию)
 * - DB_PATH=:memory: (по умолчанию)
 * - DB_PREFIX=test_ (по умолчанию)
 *
 * Переменные окружения для MySQL:
 * - DB_DRIVER=mysql
 * - DB_HOST
 * - DB_NAME
 * - DB_USER
 * - DB_PASSWORD
 * - DB_PREFIX
 */
class DatabaseTest extends TestCase
{
    private static $db;
    private static $schema;

    public static function setUpBeforeClass(): void
    {
        // Определяем драйвер из переменной окружения (по умолчанию sqlite)
        $driver = getenv('DB_DRIVER') ?: 'sqlite';

        if ($driver === 'sqlite') {
            // SQLite в памяти - быстро и не требует внешней БД
            $dbPath = getenv('DB_PATH') ?: ':memory:';
            $prefix = getenv('DB_PREFIX') ?: 'test_';

            db::set_pdo_auth([
                'driver' => 'sqlite',
                'db_path' => $dbPath,
                'table_prefix' => $prefix
            ]);
        } else {
            // MySQL подключение (для обратной совместимости)
            $host = getenv('DB_HOST') ?: 'localhost';
            $dbName = getenv('DB_NAME') ?: 'quickdbm_test';
            $user = getenv('DB_USER') ?: 'root';
            $password = getenv('DB_PASSWORD') ?: '';
            $prefix = getenv('DB_PREFIX') ?: 'test_';

            // Проверяем подключение к MySQL
            try {
                $pdo = new \PDO(
                    "mysql:host={$host}",
                    $user,
                    $password,
                    [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (\PDOException $e) {
                throw new \Exception(
                    "ОШИБКА: MySQL недоступен!\n" .
                    "Подключение: {$user}@{$host}\n" .
                    "Ошибка: " . $e->getMessage() . "\n" .
                    "Настройте подключение к MySQL в phpunit.xml или через переменные окружения."
                );
            }

            // Создаём тестовую базу данных, если её нет
            try {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}`");
            } catch (\PDOException $e) {
                throw new \Exception("Не удалось создать базу данных: $dbName");
            }

            // Выбираем базу данных
            try {
                $pdo->exec("USE `{$dbName}`");
            } catch (\PDOException $e) {
                throw new \Exception("Не удалось выбрать базу данных: $dbName");
            }

            // Закрываем соединение (опционально, будет закрыто автоматически)
            $pdo = null;

            // Настраиваем QuickDBM для MySQL
            db::set_pdo_auth([
                'driver' => 'mysql',
                'db_name' => $dbName,
                'host' => $host,
                'user' => $user,
                'password' => $password,
                'table_prefix' => $prefix
            ]);
        }

        self::$schema = new TestProductSchema();
        self::$db = new db(self::$schema);
    }

    /**
     * Тест создания объекта db
     */
    public function testDatabaseObjectCreation()
    {
        $this->assertInstanceOf(db::class, self::$db);
    }

    /**
     * Тест вставки записи с AUTO_INCREMENT
     */
    public function testAutoIncrementInsert()
    {
        $record = [
            'name' => 'Auto ID Product',
            'price' => 1000,
            'quantity' => 25,
            'is_available' => true,
            'description' => 'This product uses AUTO_INCREMENT'
        ];

        $newId = self::$db->insert($record);
        $this->assertIsInt($newId);
        $this->assertGreaterThan(0, $newId);

        // Проверяем, что запись добавлена
        $where = new where();
        $where->equally('id', $newId, true, null, true, false);
        $result = self::$db->get_rows(new select_q(null, $where));

        $this->assertNotNull($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Auto ID Product', $result[0]['name']);
        // Проверяем, что _order равен id
        $this->assertEquals($newId, $result[0]['_order'], '_order should equal id');
    }

    /**
     * Тест вставки записи
     */
    public function testInsertRecord()
    {
        $record = [
            'name' => 'Test Product',
            'price' => 1000,
            'quantity' => 50,
            'is_available' => true,
            'description' => 'This is a test product'
        ];

        $newId = self::$db->insert($record);

        // Проверяем, что запись добавлена
        $where = new where();
        $where->equally('id', $newId, true, null, true, false);
        $result = self::$db->get_rows(new select_q(null, $where));

        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Test Product', $result[0]['name']);
        $this->assertEquals(1000, $result[0]['price']);
        // Проверяем, что _order равен id
        $this->assertEquals($newId, $result[0]['_order'], '_order should equal id');
    }

    /**
     * Тест получения всех записей
     */
    public function testGetAllRecords()
    {
        // Вставим несколько записей
        for ($i = 1; $i <= 3; $i++) {
            $record = [
                'name' => "Product $i",
                'price' => $i * 100,
                'quantity' => $i * 10,
                'is_available' => true,
                'description' => "Description for product $i"
            ];
            self::$db->insert($record);
        }

        // Получаем все записи
        $result = self::$db->get_rows();

        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));
    }

    /**
     * Тест получения записей с условием WHERE
     */
    public function testGetRecordsWithWhere()
    {
        // Вставим тестовую запись
        $record = [
            'name' => 'Expensive Product',
            'price' => 5000,
            'quantity' => 5,
            'is_available' => true,
            'description' => 'Very expensive'
        ];
        $newId = self::$db->insert($record);

        // Ищем записи с ценой больше 4000
        $where = new where();
        $where->more('price', '4000', true, null, true, false);
        $result = self::$db->get_rows(new select_q(null, $where));

        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));

        // Проверяем, что все найденные записи имеют цену > 4000
        foreach ($result as $row) {
            $this->assertGreaterThan(4000, $row['price']);
        }
    }

    /**
     * Тест получения записей с лимитом
     */
    public function testGetRecordsWithLimit()
    {
        $select = new select_q(null, null, '_order', order::asc, 0, 2);
        $result = self::$db->get_rows($select);

        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(2, count($result));
    }

    /**
     * Тест получения записей с сортировкой
     */
    public function testGetRecordsWithOrdering()
    {
        $select = new select_q(null, null, 'price', order::desc, 0, 5);
        $result = self::$db->get_rows($select);

        $this->assertNotNull($result);
        $this->assertIsArray($result);

        // Проверяем, что записи отсортированы по убыванию цены
        if (count($result) > 1) {
            for ($i = 0; $i < count($result) - 1; $i++) {
                $this->assertGreaterThanOrEqual($result[$i + 1]['price'], $result[$i]['price']);
            }
        }
    }

    /**
     * Тест обновления записи
     */
    public function testUpdateRecord()
    {
        // Вставляем запись
        $record = [
            'name' => 'Product to Update',
            'price' => 100,
            'quantity' => 10,
            'is_available' => true,
            'description' => 'Original description'
        ];
        $newId = self::$db->insert($record);

        // Обновляем запись
        $updatedRecord = [
            'name' => 'Updated Product',
            'price' => 200,
            'quantity' => 20,
            'is_available' => false,
            'description' => 'Updated description'
        ];

        $where = new where();
        $where->equally('id', $newId, true, null, true, false);
        self::$db->insert($updatedRecord, $newId, $where);

        // Проверяем обновление
        $result = self::$db->get_rows(new select_q(null, $where));

        $this->assertNotNull($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Updated Product', $result[0]['name']);
        $this->assertEquals(200, $result[0]['price']);
        $this->assertEquals(20, $result[0]['quantity']);
    }

    /**
     * Тест удаления записей
     */
    public function testRemoveRecords()
    {
        // Вставляем запись для удаления
        $record = [
            'name' => 'Product to Delete',
            'price' => 999,
            'quantity' => 1,
            'is_available' => true,
            'description' => 'Will be deleted'
        ];
        $newId = self::$db->insert($record);

        // Проверяем, что запись существует
        $where = new where();
        $where->equally('id', $newId, true, null, true, false);
        $result = self::$db->get_rows(new select_q(null, $where));
        $this->assertCount(1, $result);

        // Удаляем запись
        self::$db->remove_rows($where);

        // Проверяем, что запись удалена
        $result = self::$db->get_rows(new select_q(null, $where));
        $this->assertNull($result);
    }

    /**
     * Тест удаления нескольких записей
     */
    public function testRemoveMultipleRecords()
    {
        // Вставляем несколько записей с особой ценой
        $testPrice = 77777;
        for ($i = 1; $i <= 3; $i++) {
            $record = [
                'name' => "Temp Product $i",
                'price' => $testPrice,
                'quantity' => $i,
                'is_available' => true,
                'description' => "Temporary $i"
            ];
            self::$db->insert($record);
        }

        // Удаляем все записи с этой ценой
        $where = new where();
        $where->equally('price', $testPrice, true, null, true, false);

        // Проверяем, что записи существуют
        $result = self::$db->get_rows(new select_q(null, $where));
        $this->assertGreaterThanOrEqual(3, count($result));

        // Удаляем
        self::$db->remove_rows($where);

        // Проверяем, что записи удалены
        $result = self::$db->get_rows(new select_q(null, $where));
        $this->assertNull($result);
    }

    /**
     * Очистка после всех тестов
     */
    public static function tearDownAfterClass(): void
    {
        if (self::$db) {
            // Удаляем тестовую таблицу
            try {
                $where = new where();
                // Удаляем все записи из тестовой таблицы
                $allRecords = self::$db->get_rows();
                if ($allRecords) {
                    foreach ($allRecords as $record) {
                        $where = new where();
                        $where->equally('id', $record['id'], true, null, true, false);
                        self::$db->remove_rows($where);
                    }
                }
            } catch (\Exception $e) {
                // Игнорируем ошибки при очистке
            }
        }
    }
}
