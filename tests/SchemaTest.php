<?php

namespace showyweb\qdbm\tests;

use PHPUnit\Framework\TestCase;
use showyweb\qdbm\schema;
use showyweb\qdbm\type_column;

/**
 * Тестовая схема для юнит-тестов
 */
class TestUserSchema extends schema
{
    public $tab_name = "test_users";

    const username = array('type' => type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
    const email = array('type' => type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => true);
    const age = array('type' => type_column::unsigned_int, 'is_xss_filter' => true, 'is_add_index' => false);
    const is_active = array('type' => type_column::bool, 'is_xss_filter' => true, 'is_add_index' => false);
    const bio = array('type' => type_column::string, 'is_xss_filter' => false, 'is_add_index' => false);
}

/**
 * Тесты для класса schema
 */
class SchemaTest extends TestCase
{
    /**
     * Тест создания схемы с именем таблицы
     */
    public function testSchemaCreationWithTableName()
    {
        $schema = new TestUserSchema();
        $this->assertInstanceOf(schema::class, $schema);
        $this->assertEquals("test_users", $schema->tab_name);
    }

    /**
     * Тест создания схемы с переопределением имени таблицы
     */
    public function testSchemaCreationWithCustomTableName()
    {
        $schema = new TestUserSchema("custom_users");
        $this->assertEquals("custom_users", $schema->tab_name);
    }

    /**
     * Тест метода get_columns
     */
    public function testGetColumns()
    {
        $schema = new TestUserSchema();
        $columns = $schema->get_columns();

        $this->assertIsArray($columns);
        $this->assertArrayHasKey('username', $columns);
        $this->assertArrayHasKey('email', $columns);
        $this->assertArrayHasKey('age', $columns);
        $this->assertArrayHasKey('is_active', $columns);
        $this->assertArrayHasKey('bio', $columns);

        // Проверяем встроенные колонки
        $this->assertArrayHasKey('id', $columns);
        $this->assertArrayHasKey('_order', $columns);
    }

    /**
     * Тест структуры определения колонки
     */
    public function testColumnDefinitionStructure()
    {
        $schema = new TestUserSchema();
        $columns = $schema->get_columns();

        // Проверяем структуру определения колонки username
        $this->assertIsArray($columns['username']);
        $this->assertArrayHasKey('type', $columns['username']);
        $this->assertArrayHasKey('is_xss_filter', $columns['username']);
        $this->assertArrayHasKey('is_add_index', $columns['username']);

        $this->assertEquals(type_column::small_string, $columns['username']['type']);
        $this->assertTrue($columns['username']['is_xss_filter']);
        $this->assertTrue($columns['username']['is_add_index']);
    }

    /**
     * Тест встроенной колонки id
     */
    public function testBuiltInIdColumn()
    {
        $schema = new TestUserSchema();
        $columns = $schema->get_columns();

        $this->assertArrayHasKey('id', $columns);
        $this->assertEquals(type_column::unsigned_big_int, $columns['id']['type']);
        $this->assertTrue($columns['id']['is_xss_filter']);
        $this->assertTrue($columns['id']['is_add_index']);
    }

    /**
     * Тест встроенной колонки _order
     */
    public function testBuiltInOrderColumn()
    {
        $schema = new TestUserSchema();
        $columns = $schema->get_columns();

        $this->assertArrayHasKey('_order', $columns);
        $this->assertEquals(type_column::unsigned_big_int, $columns['_order']['type']);
        $this->assertTrue($columns['_order']['is_xss_filter']);
        $this->assertTrue($columns['_order']['is_add_index']);
    }

    /**
     * Тест доступа к именам колонок как свойствам после get_columns
     */
    public function testColumnNamesAsProperties()
    {
        $schema = new TestUserSchema();
        $schema->get_columns();

        // После вызова get_columns, имена колонок должны быть доступны как свойства
        $this->assertEquals('username', $schema->username);
        $this->assertEquals('email', $schema->email);
        $this->assertEquals('age', $schema->age);
        $this->assertEquals('is_active', $schema->is_active);
        $this->assertEquals('bio', $schema->bio);
        $this->assertEquals('id', $schema->id);
        $this->assertEquals('_order', $schema->_order);
    }

    /**
     * Тест создания схемы без имени таблицы (должно вызвать исключение)
     */
    public function testSchemaCreationWithoutTableNameThrowsException()
    {
        $this->expectException(\Exception::class);

        // Создаём анонимный класс с пустым tab_name
        $schema = new class extends schema {
            public $tab_name = "";
        };
    }

    /**
     * Тест различных типов колонок
     */
    public function testDifferentColumnTypes()
    {
        $schema = new class("test_types") extends schema {
            const str_col = array('type' => type_column::string, 'is_xss_filter' => true, 'is_add_index' => false);
            const small_str_col = array('type' => type_column::small_string, 'is_xss_filter' => true, 'is_add_index' => false);
            const int_col = array('type' => type_column::int, 'is_xss_filter' => true, 'is_add_index' => false);
            const uint_col = array('type' => type_column::unsigned_int, 'is_xss_filter' => true, 'is_add_index' => false);
            const bigint_col = array('type' => type_column::big_int, 'is_xss_filter' => true, 'is_add_index' => false);
            const ubigint_col = array('type' => type_column::unsigned_big_int, 'is_xss_filter' => true, 'is_add_index' => false);
            const bool_col = array('type' => type_column::bool, 'is_xss_filter' => true, 'is_add_index' => false);
            const datetime_col = array('type' => type_column::datetime, 'is_xss_filter' => true, 'is_add_index' => false);
            const decimal_col = array('type' => type_column::decimal_auto, 'is_xss_filter' => true, 'is_add_index' => false);
        };

        $columns = $schema->get_columns();

        $this->assertEquals(type_column::string, $columns['str_col']['type']);
        $this->assertEquals(type_column::small_string, $columns['small_str_col']['type']);
        $this->assertEquals(type_column::int, $columns['int_col']['type']);
        $this->assertEquals(type_column::unsigned_int, $columns['uint_col']['type']);
        $this->assertEquals(type_column::big_int, $columns['bigint_col']['type']);
        $this->assertEquals(type_column::unsigned_big_int, $columns['ubigint_col']['type']);
        $this->assertEquals(type_column::bool, $columns['bool_col']['type']);
        $this->assertEquals(type_column::datetime, $columns['datetime_col']['type']);
        $this->assertEquals(type_column::decimal_auto, $columns['decimal_col']['type']);
    }
}
