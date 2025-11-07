<?php

namespace showyweb\qdbm\tests;

use PHPUnit\Framework\TestCase;
use showyweb\qdbm\select_q;
use showyweb\qdbm\where;
use showyweb\qdbm\order;

/**
 * Тесты для класса select_q
 */
class SelectQTest extends TestCase
{
    /**
     * Тест создания простого объекта select_q
     */
    public function testSelectQCreation()
    {
        $select = new select_q();
        $this->assertInstanceOf(select_q::class, $select);
    }

    /**
     * Тест создания select_q с where условием
     */
    public function testSelectQWithWhere()
    {
        $where = new where();
        $where->equally('status', 'active');

        $select = new select_q(null, $where);
        $this->assertInstanceOf(select_q::class, $select);
    }

    /**
     * Тест создания select_q с параметрами сортировки
     */
    public function testSelectQWithOrdering()
    {
        $select = new select_q(null, null, 'name', order::desc);
        $this->assertInstanceOf(select_q::class, $select);
    }

    /**
     * Тест создания select_q с лимитом
     */
    public function testSelectQWithLimit()
    {
        $select = new select_q(null, null, '_order', order::asc, 0, 10);
        $this->assertInstanceOf(select_q::class, $select);
    }

    /**
     * Тест создания select_q с offset и limit
     */
    public function testSelectQWithOffsetAndLimit()
    {
        $select = new select_q(null, null, '_order', order::asc, 20, 10);
        $this->assertInstanceOf(select_q::class, $select);
    }

    /**
     * Тест генерации SQL с простым WHERE
     */
    public function testGenerateSqlWithWhere()
    {
        $where = new where();
        $where->equally('id', '1', true, null, true, false);

        $select = new select_q(null, $where);
        $sql = $select->_get('test_table', 'prefix_');
        $sql = $sql['sql'] ?? '';
        $this->assertStringContainsString('SELECT', $sql);
        $this->assertStringContainsString('test_table', $sql);
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertStringContainsString('id', $sql);
    }

    /**
     * Тест генерации SQL с ORDER BY ASC
     */
    public function testGenerateSqlWithOrderAsc()
    {
        $select = new select_q(null, null, 'name', order::asc);
        $sql = $select->_get('test_table', 'prefix_');
        $sql = $sql['sql'] ?? '';
        $this->assertStringContainsString('SELECT', $sql);
        $this->assertStringContainsString('ORDER BY', $sql);
        $this->assertStringContainsString('name', $sql);
        // ASC обычно не пишется явно, просто ORDER BY column
    }

    /**
     * Тест генерации SQL с ORDER BY DESC
     */
    public function testGenerateSqlWithOrderDesc()
    {
        $select = new select_q(null, null, 'created_at', order::desc);
        $sql = $select->_get('test_table', 'prefix_');
        $sql = $sql['sql'] ?? '';
        $this->assertStringContainsString('SELECT', $sql);
        $this->assertStringContainsString('ORDER BY', $sql);
        $this->assertStringContainsString('created_at', $sql);
        $this->assertStringContainsString('DESC', $sql);
    }

    /**
     * Тест генерации SQL с ORDER BY RAND
     */
    public function testGenerateSqlWithOrderRand()
    {
        $select = new select_q(null, null, 'id', order::rand);
        $sql = $select->_get('test_table', 'prefix_');
        $sql = $sql['sql'] ?? '';
        $this->assertStringContainsString('SELECT', $sql);
        $this->assertStringContainsString('ORDER BY', $sql);
        $this->assertStringContainsString('rand()', $sql);
    }

    /**
     * Тест генерации SQL с LIMIT
     */
    public function testGenerateSqlWithLimit()
    {
        $select = new select_q(null, null, '_order', order::asc, 0, 10);
        $sql = $select->_get('test_table', 'prefix_');
        $sql = $sql['sql'] ?? '';
        $this->assertStringContainsString('SELECT', $sql);
        $this->assertStringContainsString('LIMIT', $sql);
        $this->assertStringContainsString('0,10', $sql);
    }

    /**
     * Тест генерации SQL с OFFSET и LIMIT
     */
    public function testGenerateSqlWithOffsetAndLimit()
    {
        $select = new select_q(null, null, '_order', order::asc, 20, 10);
        $sql = $select->_get('test_table', 'prefix_');
        $sql = $sql['sql'] ?? '';
        $this->assertStringContainsString('SELECT', $sql);
        $this->assertStringContainsString('LIMIT', $sql);
        $this->assertStringContainsString('20,10', $sql);
    }

    /**
     * Тест генерации SQL с DISTINCT
     */
    public function testGenerateSqlWithDistinct()
    {
        $select = new select_q(null, null, '_order', order::asc, 0, 0, null, null, null, null, true);
        $sql = $select->_get('test_table', 'prefix_');
        $sql = $sql['sql'] ?? '';
        $this->assertStringContainsString('SELECT DISTINCT', $sql);
    }

    /**
     * Тест генерации SQL с множественной сортировкой
     */
    public function testGenerateSqlWithMultipleOrderBy()
    {
        $select = new select_q(null, null, ['status', 'name'], order::asc);
        $sql = $select->_get('test_table', 'prefix_');
        $sql = $sql['sql'] ?? '';
        $this->assertStringContainsString('SELECT', $sql);
        $this->assertStringContainsString('ORDER BY', $sql);
        $this->assertStringContainsString('status', $sql);
        $this->assertStringContainsString('name', $sql);
    }

    /**
     * Тест создания select_q через массив параметров
     */
    public function testSelectQWithArgsArray()
    {
        $args = [
            'order_by' => 'title',
            'order_method' => order::desc,
            'limit' => 5
        ];

        $select = new select_q($args);
        $sql = $select->_get('test_table', 'prefix_');
        $sql = $sql['sql'] ?? '';
        $this->assertStringContainsString('title', $sql);
        $this->assertStringContainsString('DESC', $sql);
        $this->assertStringContainsString('LIMIT', $sql);
    }

    /**
     * Тест комплексного запроса
     */
    public function testComplexQuery()
    {
        $where = new where();
        $where->equally('status', 'published');
        $where->more('views', '100', true, null, true, false);

        $select = new select_q(null, $where, 'created_at', order::desc, 0, 20);
        $sql = $select->_get('articles', 'blog_');
        $sql = $sql['sql'] ?? '';
        $this->assertStringContainsString('SELECT', $sql);
        $this->assertStringContainsString('articles', $sql);
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertStringContainsString('status', $sql);
        $this->assertStringContainsString('views', $sql);
        $this->assertStringContainsString('ORDER BY', $sql);
        $this->assertStringContainsString('created_at', $sql);
        $this->assertStringContainsString('DESC', $sql);
        $this->assertStringContainsString('LIMIT', $sql);
    }
}
