# QuickDBM Tests

Этот каталог содержит юнит-тесты и интеграционные тесты для библиотеки QuickDBM.

## Структура тестов

- **ExtToolsTest.php** - Тесты для утилитарных функций класса `ext_tools`
- **WhereTest.php** - Тесты для построителя условий WHERE
- **SelectQTest.php** - Тесты для построителя SELECT-запросов
- **SchemaTest.php** - Тесты для схем таблиц
- **DatabaseTest.php** - Интеграционные тесты для работы с базой данных (CRUD операции)

## Установка зависимостей

Перед запуском тестов установите PHPUnit через Composer:

```bash
composer install
```

## Запуск тестов

### Запуск всех тестов

```bash
./vendor/bin/phpunit
```

или

```bash
composer test
```

### Запуск только юнит-тестов (без БД)

```bash
composer test-unit
```

или

```bash
./vendor/bin/phpunit tests/ExtToolsTest.php tests/WhereTest.php tests/SchemaTest.php tests/SelectQTest.php
```

### Запуск интеграционных тестов БД

```bash
composer test-integration
```

или

```bash
./vendor/bin/phpunit tests/DatabaseTest.php
```

### Запуск тестов с подробным выводом

```bash
./vendor/bin/phpunit --verbose
```

### Запуск тестов с отчетом о покрытии кода

```bash
./vendor/bin/phpunit --coverage-html coverage
```

После выполнения откройте `coverage/index.html` в браузере.

## Настройка для интеграционных тестов

Интеграционные тесты в `DatabaseTest.php` требуют подключения к тестовой базе данных MySQL.

### Настройка через phpunit.xml

Отредактируйте файл `phpunit.xml` в корне проекта и укажите параметры подключения:

```xml
<php>
    <env name="DB_HOST" value="localhost"/>
    <env name="DB_NAME" value="quickdbm_test"/>
    <env name="DB_USER" value="root"/>
    <env name="DB_PASSWORD" value="your_password"/>
    <env name="DB_PREFIX" value="test_"/>
</php>
```

### Настройка через переменные окружения

Вы также можете установить переменные окружения перед запуском тестов:

```bash
export DB_HOST=localhost
export DB_NAME=quickdbm_test
export DB_USER=root
export DB_PASSWORD=your_password
export DB_PREFIX=test_

./vendor/bin/phpunit tests/DatabaseTest.php
```

### Создание тестовой базы данных

Создайте тестовую базу данных вручную:

```sql
CREATE DATABASE quickdbm_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Или дайте пользователю права на создание баз данных, и тесты создадут её автоматически.

### Docker окружение

Если вы используете Docker (docker-compose.yaml из проекта), база данных уже настроена:

```bash
docker-compose up -d
docker-compose exec server ./vendor/bin/phpunit
```

## Обязательные требования для интеграционных тестов

**ВАЖНО:** Интеграционные тесты из `DatabaseTest.php` **требуют обязательного наличия MySQL**.

- ✅ Если MySQL доступен → тесты выполняются
- ❌ Если MySQL недоступен → тесты завершаются с ошибкой (ERROR)

Чтобы запустить только юнит-тесты без интеграционных (без MySQL):

```bash
composer test-unit
```

Для запуска всех тестов MySQL должен быть настроен и доступен.

## Написание новых тестов

При добавлении новых функций в QuickDBM, создавайте тесты следуя этим рекомендациям:

1. **Имена файлов**: `{ClassName}Test.php`
2. **Namespace**: `showyweb\qdbm\tests`
3. **Наследование**: все тесты должны наследоваться от `PHPUnit\Framework\TestCase`
4. **Имена методов**: начинаются с `test`, например `testSomeFunctionality()`
5. **Один тест = одна проверка**: каждый тест должен проверять одну конкретную функциональность
6. **Используйте описательные имена**: `testXssFilterWithScript()` лучше, чем `testFilter1()`

### Пример структуры теста

```php
<?php

namespace showyweb\qdbm\tests;

use PHPUnit\Framework\TestCase;
use showyweb\qdbm\YourClass;

class YourClassTest extends TestCase
{
    public function testSomeFunction()
    {
        $result = YourClass::someFunction('input');
        $this->assertEquals('expected', $result);
    }
}
```

## Continuous Integration (CI)

Тесты готовы для запуска в CI/CD системах (GitHub Actions, GitLab CI, Travis CI и т.д.).

Пример для GitHub Actions:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:5.7
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: quickdbm_test
        ports:
          - 3306:3306

    steps:
      - uses: actions/checkout@v2
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '7.2'
      - run: composer install
      - run: ./vendor/bin/phpunit
        env:
          DB_HOST: 127.0.0.1
          DB_NAME: quickdbm_test
          DB_USER: root
          DB_PASSWORD: root
          DB_PREFIX: test_
```

## Troubleshooting

### Ошибка "Class not found"

Убедитесь, что вы установили зависимости:

```bash
composer install
```

И что автозагрузка настроена правильно:

```bash
composer dump-autoload
```

### Ошибки подключения к БД

1. Проверьте, что MySQL сервер запущен
2. Проверьте учетные данные в `phpunit.xml`
3. Убедитесь, что пользователь имеет права на создание баз данных
4. Проверьте, что порт 3306 доступен

### Тесты падают с ошибкой "MySQL недоступен"

Интеграционные тесты **требуют MySQL**. Если видите эту ошибку:

```
Exception: ОШИБКА: MySQL недоступен!
Подключение: root@localhost
```

Решение:
1. Запустите MySQL сервер
2. Проверьте настройки подключения в `phpunit.xml`
3. Или запускайте только юнит-тесты: `composer test-unit`
