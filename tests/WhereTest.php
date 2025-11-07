<?php

namespace showyweb\qdbm\tests;

use PHPUnit\Framework\TestCase;
use showyweb\qdbm\where;

/**
 * Тесты для класса where
 */
class WhereTest extends TestCase
{
    /**
     * Тест создания объекта where
     */
    public function testWhereCreation()
    {
        $where = new where();
        $this->assertInstanceOf(where::class, $where);
        $result = $where->_get();
        $this->assertNull($result['sql']);
        $this->assertIsArray($result['params']);
        $this->assertEmpty($result['params']);
    }

    /**
     * Тест метода equally
     */
    public function testEqually()
    {
        $where = new where();
        $where->equally('name', 'John');
        $result = $where->_get();
        $this->assertStringContainsString('name', $result['sql']);
        $this->assertStringContainsString('=', $result['sql']);
        $this->assertStringContainsString('?', $result['sql']);
        $this->assertIsArray($result['params']);
        $this->assertCount(1, $result['params']);
        $this->assertEquals('John', $result['params'][0]);
    }

    /**
     * Тест метода equally с числовым значением
     */
    public function testEquallyWithNumber()
    {
        $where = new where();
        $where->equally('age', '25', true, null, true, false);
        $result = $where->_get();
        $this->assertStringContainsString('age', $result['sql']);
        $this->assertStringContainsString('?', $result['sql']);
        $this->assertIsArray($result['params']);
        $this->assertCount(1, $result['params']);
        $this->assertEquals('25', $result['params'][0]);
    }

    /**
     * Тест метода not_equally
     */
    public function testNotEqually()
    {
        $where = new where();
        $where->not_equally('status', 'inactive');
        $result = $where->_get();
        $this->assertStringContainsString('status', $result['sql']);
        $this->assertStringContainsString('!=', $result['sql']);
        $this->assertStringContainsString('?', $result['sql']);
        $this->assertIsArray($result['params']);
        $this->assertCount(1, $result['params']);
        $this->assertEquals('inactive', $result['params'][0]);
    }

    /**
     * Тест метода in
     */
    public function testIn()
    {
        $where = new where();
        $where->in('category', ['news', 'blog', 'article']);
        $result = $where->_get();
        $this->assertStringContainsString('category', $result['sql']);
        $this->assertStringContainsString('IN', $result['sql']);
        $this->assertStringContainsString('?', $result['sql']);
        $this->assertIsArray($result['params']);
        $this->assertCount(3, $result['params']);
        $this->assertEquals('news', $result['params'][0]);
        $this->assertEquals('blog', $result['params'][1]);
        $this->assertEquals('article', $result['params'][2]);
    }

    /**
     * Тест метода not_in
     */
    public function testNotIn()
    {
        $where = new where();
        $where->not_in('status', ['deleted', 'archived']);
        $result = $where->_get();
        $this->assertStringContainsString('status', $result['sql']);
        $this->assertStringContainsString('NOT IN', $result['sql']);
        $this->assertStringContainsString('?', $result['sql']);
        $this->assertIsArray($result['params']);
        $this->assertCount(2, $result['params']);
        $this->assertEquals('deleted', $result['params'][0]);
        $this->assertEquals('archived', $result['params'][1]);
    }

    /**
     * Тест метода more (больше)
     */
    public function testMore()
    {
        $where = new where();
        $where->more('price', '100', true, null, true, false);
        $result = $where->_get();
        $this->assertStringContainsString('price', $result['sql']);
        $this->assertStringContainsString('>', $result['sql']);
        $this->assertStringContainsString('?', $result['sql']);
        $this->assertIsArray($result['params']);
        $this->assertCount(1, $result['params']);
        $this->assertEquals('100', $result['params'][0]);
    }

    /**
     * Тест метода more_or_equally (больше или равно)
     */
    public function testMoreOrEqually()
    {
        $where = new where();
        $where->more_or_equally('quantity', '10', true, null, true, false);
        $result = $where->_get();
        $this->assertStringContainsString('quantity', $result['sql']);
        $this->assertStringContainsString('>=', $result['sql']);
        $this->assertStringContainsString('?', $result['sql']);
        $this->assertIsArray($result['params']);
        $this->assertCount(1, $result['params']);
        $this->assertEquals('10', $result['params'][0]);
    }

    /**
     * Тест комбинирования условий с AND
     */
    public function testMultipleConditionsWithAnd()
    {
        $where = new where();
        $where->equally('status', 'active', true);
        $where->equally('verified', '1', true, null, true, false);
        $result = $where->_get();
        $this->assertStringContainsString('status', $result['sql']);
        $this->assertStringContainsString('verified', $result['sql']);
        $this->assertStringContainsString('AND', $result['sql']);
        $this->assertIsArray($result['params']);
        $this->assertCount(2, $result['params']);
        $this->assertEquals('active', $result['params'][0]);
        $this->assertEquals('1', $result['params'][1]);
    }

    /**
     * Тест комбинирования условий с OR
     */
    public function testMultipleConditionsWithOr()
    {
        $where = new where();
        $where->equally('type', 'premium', true);
        $where->equally('type', 'vip', false); // false = OR
        $result = $where->_get();
        $this->assertStringContainsString('type', $result['sql']);
        $this->assertStringContainsString('OR', $result['sql']);
        $this->assertIsArray($result['params']);
        $this->assertCount(2, $result['params']);
        $this->assertEquals('premium', $result['params'][0]);
        $this->assertEquals('vip', $result['params'][1]);
    }

    /**
     * Тест метода push_where (вложенные условия)
     */
    public function testPushWhere()
    {
        $where1 = new where();
        $where1->equally('age', '18', true, null, true, false);

        $where2 = new where();
        $where2->equally('name', 'John');
        $where2->push_where($where1, true);

        $result = $where2->_get();
        $this->assertStringContainsString('name', $result['sql']);
        $this->assertStringContainsString('age', $result['sql']);
        $this->assertStringContainsString('AND', $result['sql']);
        // Проверяем наличие скобок для группировки
        $this->assertStringContainsString('(', $result['sql']);
        $this->assertStringContainsString(')', $result['sql']);
        $this->assertIsArray($result['params']);
        $this->assertCount(2, $result['params']);
        $this->assertEquals('John', $result['params'][0]);
        $this->assertEquals('18', $result['params'][1]);
    }

    /**
     * Тест с отключением экранирования
     */
    public function testWithoutQuotes()
    {
        $where = new where(false);
        $where->equally('column', 'value');
        $result = $where->_get();
        $this->assertStringContainsString('column', $result['sql']);
        $this->assertStringContainsString('?', $result['sql']);
        // При отключении magic_quotes не должно быть обратных кавычек вокруг имени колонки
        $this->assertStringNotContainsString('`column`', $result['sql']);
        $this->assertIsArray($result['params']);
        $this->assertCount(1, $result['params']);
        $this->assertEquals('value', $result['params'][0]);
    }

    /**
     * Тест с булевым значением
     */
    public function testEquallyWithBoolean()
    {
        $where = new where();
        $where->equally('is_active', true, true, null, true, false);
        $result = $where->_get();
        $this->assertStringContainsString('is_active', $result['sql']);
        $this->assertStringContainsString('?', $result['sql']);
        $this->assertIsArray($result['params']);
        $this->assertCount(1, $result['params']);
        // Булевы значения должны быть преобразованы в 1 или 0
        $this->assertEquals(1, $result['params'][0]);
    }

    /**
     * Тест сложного запроса с несколькими условиями
     */
    public function testComplexQuery()
    {
        $where = new where();
        $where->equally('status', 'active', true);
        $where->more('age', '18', true, null, true, false);
        $where->in('role', ['admin', 'moderator'], true);

        $result = $where->_get();
        $this->assertStringContainsString('status', $result['sql']);
        $this->assertStringContainsString('age', $result['sql']);
        $this->assertStringContainsString('role', $result['sql']);
        $this->assertStringContainsString('AND', $result['sql']);
        $this->assertStringContainsString('?', $result['sql']);
        $this->assertIsArray($result['params']);
        $this->assertCount(4, $result['params']);
        $this->assertEquals('active', $result['params'][0]);
        $this->assertEquals('18', $result['params'][1]);
        $this->assertEquals('admin', $result['params'][2]);
        $this->assertEquals('moderator', $result['params'][3]);
    }
}
