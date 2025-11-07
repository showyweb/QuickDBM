<?php

namespace showyweb\qdbm\tests;

use PHPUnit\Framework\TestCase;
use showyweb\qdbm\ext_tools;

/**
 * Тесты для класса ext_tools
 */
class ExtToolsTest extends TestCase
{
    /**
     * Тест функции xss_filter с целыми числами
     */
    public function testXssFilterWithInteger()
    {
        $result = ext_tools::xss_filter(123);
        $this->assertEquals(123, $result);
        $this->assertIsInt($result);
    }

    /**
     * Тест функции xss_filter с дробными числами
     */
    public function testXssFilterWithFloat()
    {
        $result = ext_tools::xss_filter(123.45);
        $this->assertEquals(123.45, $result);
        $this->assertIsFloat($result);
    }

    /**
     * Тест функции xss_filter с null
     */
    public function testXssFilterWithNull()
    {
        $result = ext_tools::xss_filter(null);
        $this->assertNull($result);
    }

    /**
     * Тест функции xss_filter со звездочкой
     */
    public function testXssFilterWithAsterisk()
    {
        $result = ext_tools::xss_filter("*");
        $this->assertEquals("*", $result);
    }

    /**
     * Тест функции xss_filter с простым текстом
     */
    public function testXssFilterWithSimpleText()
    {
        $result = ext_tools::xss_filter("Hello World");
        $this->assertStringContainsString("Hello", $result);
        $this->assertStringContainsString("World", $result);
    }

    /**
     * Тест функции xss_filter с вредоносным скриптом
     */
    public function testXssFilterWithScript()
    {
        $malicious = "<script>alert('XSS')</script>";
        $result = ext_tools::xss_filter($malicious);
        // После фильтрации не должно быть тегов script
        $this->assertStringNotContainsString("<script>", $result);
        $this->assertStringNotContainsString("</script>", $result);
    }

    /**
     * Тест функции xss_filter с массивом
     */
    public function testXssFilterWithArray()
    {
        $input = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 123
        ];
        $result = ext_tools::xss_filter($input);
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    /**
     * Тест функции xss_filter с кириллицей
     */
    public function testXssFilterWithCyrillic()
    {
        $text = "Привет мир";
        $result = ext_tools::xss_filter($text);
        $this->assertStringContainsString("Привет", $result);
        $this->assertStringContainsString("мир", $result);
    }

    /**
     * Тест функции remove_nbsp
     */
    public function testRemoveNbsp()
    {
        $text = "Hello&nbsp;World";
        $result = ext_tools::remove_nbsp($text);
        $this->assertEquals("Hello World", $result);
    }

    /**
     * Тест функции utf8_strlen
     */
    public function testUtf8Strlen()
    {
        $this->assertEquals(5, ext_tools::utf8_strlen("Hello"));
        $this->assertEquals(6, ext_tools::utf8_strlen("Привет"));
    }

    /**
     * Тест функции get_current_datetime - проверяет формат
     */
    public function testGetCurrentDatetime()
    {
        $datetime = ext_tools::get_current_datetime();
        $this->assertRegExp('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $datetime);
    }

    /**
     * Тест функций to_datetime и to_timestamp
     */
    public function testDatetimeConversion()
    {
        $timestamp = 1609459200; // 2021-01-01 00:00:00 UTC
        $datetime = ext_tools::to_datetime($timestamp);
        $this->assertRegExp('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $datetime);

        $backToTimestamp = ext_tools::to_timestamp($datetime);
        $this->assertEquals($timestamp, $backToTimestamp);
    }

    /**
     * Тест функции decimal_size
     */
    public function testDecimalSize()
    {
        // Тест с целым числом
        list($precision, $scale) = ext_tools::decimal_size('123');
        $this->assertEquals(3, $precision);
        $this->assertEquals(0, $scale);

        // Тест с дробным числом
        list($precision, $scale) = ext_tools::decimal_size('123.45');
        $this->assertEquals(5, $precision);
        $this->assertEquals(2, $scale);

        // Тест с большим дробным числом
        list($precision, $scale) = ext_tools::decimal_size('12345.6789');
        $this->assertEquals(9, $precision);
        $this->assertEquals(4, $scale);
    }

    /**
     * Тест функции first с массивом
     */
    public function testFirstWithArray()
    {
        $array = ['first', 'second', 'third'];
        $result = ext_tools::first($array);
        $this->assertEquals('first', $result);
    }

    /**
     * Тест функции first с null
     */
    public function testFirstWithNull()
    {
        $result = ext_tools::first(null);
        $this->assertNull($result);
    }

    /**
     * Тест функции characters_escape и characters_unescape
     */
    public function testCharactersEscapeUnescape()
    {
        $original = "Test with text";
        $escaped = ext_tools::characters_escape($original);

        // Проверяем, что строка экранирована
        $this->assertNotEmpty($escaped);

        // Проверяем обратное преобразование
        $unescaped = ext_tools::characters_unescape($escaped);
        $this->assertEquals($original, $unescaped);

        // Тест с простым текстом
        $simple = "Hello World 123";
        $escapedSimple = ext_tools::characters_escape($simple);
        $unescapedSimple = ext_tools::characters_unescape($escapedSimple);
        $this->assertEquals($simple, $unescapedSimple);
    }

    /**
     * Тест функции utf8_str_split
     */
    public function testUtf8StrSplit()
    {
        $text = "Hello";
        $chars = ext_tools::utf8_str_split($text);
        $this->assertIsArray($chars);
        $this->assertCount(5, $chars);
        $this->assertEquals('H', $chars[0]);
        $this->assertEquals('o', $chars[4]);

        // Тест с кириллицей
        $textCyrillic = "Привет";
        $charsCyrillic = ext_tools::utf8_str_split($textCyrillic);
        $this->assertIsArray($charsCyrillic);
        $this->assertCount(6, $charsCyrillic);
    }
}
