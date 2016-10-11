# Четкая мокалка

Любая подмена работает только в тестовом окружении. Основная цель данного подхода -
тестировать только тот кусок, который нам необходимо без подтягивания прицепа связанных вызовов,
а также отход от разделения логики основного кода для режима тестирования и отладки.

## Доступный функционал
* `MethodMocker` - подмена/снифф методов, вызов приватных методов
* `HttpClientMocker` - подмена/снифф HTTP запросов
* `ConstantMocker` - переопределение констант
* `PropertyAccess` - чтение и запись private и protected свойств

# Подмена методов
```php
MethodMockerEntity MethodMocker::mock(string $className, string $methodName, string|null $newAction = null);
```
`$newAction` необходим в случае полной подменой метода без каких-либо проверок. Полезно в случае переопределения каких-то методов вывода, например `_sendJsonResponse` в CakePHP2.

## Доступные методы `MethodMockerEntity`:
* Манипуляция с кол-вом вызовов
    * `singleCall()` - один раз
    * `anyCall()` - как минимум 1 раз (по-умолчанию)
    * `expectCall(int <кол-во>)` - если мы специально хотим указать, что вызовов быть не должно, то `$mock->expectCall(0);`
* Проверка входных параметров - `expectArgs(mixed <аргумент1>, mixed <аргумент2>, ..)`
* Возвращаемое значение
    * `willReturnValue(mixed <значние>)`
    * `willReturnAction(function($args) { /* код проверки */;  return 'mock result';})` будет вызвана функция, результат которой вернется в качестве ответа мока
    * `null` по-умолчанию
* Кол-во вызовов подменённого метода - `getCallCount()`

## Примеры
Мок с возвращаемым значением:
```php
MethodMocker::mock('App\Lib\File', 'zip')->singleCall()
    ->willReturnValue(true);
```

Мок с кэлбэком:
```php
MethodMocker::mock(Promo::class, '_getShippedOrdersCount')->willReturnAction(function ($args) {
    return $this->_currentOrderCount;
});
```

# Сниф методов
```php
MethodMockerEntity MethodMocker::sniff(string $className, string $methodName, function($args, $originalResult) { /* код снифа */ });
```
Для снифа, также как и для мока, можно задавать проверку на кол-во вызовов (по-умолчанию 1).

## Пример
```php
$memcacheUsed = false;
MethodMocker::sniff(City::class, '_getFromCache', function($args, $origResult) use (&$memcacheUsed) {
    $memcacheUsed = ($origResult !== false);
});
```

# Подмена методов при запуске тестирования
Бывают случаи, когда в режиме тестирования нам надо на постоянной основе подменить какие-то методы. Например, чтение/запись в библиотеку Cookie в CakePHP2.
Для этого в папку `tests\Suite\Mock` добавляем класс, отнаследованный от `ClassMockEntity`. Метод `init()` - инициализация подмены во время запуска теста.
Пример подмены:
```php
public static function init() {
    MethodMocker::mock('Cookie', 'set', 'return App\Suite\Mock\MockCookie::set(...func_get_args());');
    MethodMocker::mock('Cookie', 'get', 'return App\Suite\Mock\MockCookie::get(...func_get_args());');
    MethodMocker::mock('Cookie', 'delete', 'return App\Suite\Mock\MockCookie::delete(...func_get_args());');
}
```

# Подмена констант
```php
ConstantMocker::mock(string $className, string $constantName, string $newValue)
```

# Вызов private или protected метода
```php
mixed MethodMocker::callPrivateOrProtectedMethod(string $className, string $methodName, object|null $objectInstance = null, array|null $args = null)
```

## Примеры
Вызов protected метода обычного класса:
```php
$testClass = new testMockSecondClass();
$result = MethodMocker::callPrivateOrProtectedMethod(testMockSecondClass::class, '_protectedMethod', $testClass, [1]);
```
Вызов приватного метода библиотеки:
```php
$result = MethodMocker::callPrivateOrProtectedMethod(testMockSecondClass::class, '_privateStaticMethod', null, [1]);
```
# Подмена HTTP запросов
```php
HttpClientMockerEntity HttpClientMocker::mock(string $url, string $method)
```
## Варианты параметра `$method`:
* `Request::METHOD_GET`
* `Request::METHOD_POST`
* `Request::METHOD_PUT`
* `Request::METHOD_DELETE`
* `Request::METHOD_PATCH`
* `Request::METHOD_OPTIONS`
* `Request::METHOD_TRACE`
* `Request::METHOD_HEAD`

## Доступные методы `HttpClientMockerEntity`:
* Манипуляция с кол-вом вызовов:
    * `singleCall()` - один раз
    * `anyCall()` - как минимум 1 раз (по-умолчанию)
    * `expectCall(int <кол-во>)` - если мы специально хотим указать, что вызовов быть не должно, то `$mock->expectCall(0);`
* Проверка тела запроса (POST данных) - `expectBody(array $body)`. Порядок следования аргументов не учитывается.
* Возвращаемое значение (понятное дело, что в конченом счете все конвертируется в строку):
    * `willReturnString(string <Строка>)`
    * `willReturnJson(array <JSON массив>)`
    * `willReturnAction(callable <функция>)` На вход функция получает объект `Cake\Network\Http\Request`, на выходе должная быть возвращена строка
    * Если ничего не указать, ты получим `\Exception`
* Кол-во вызовов подменённого запроса - `getCallCount()`

## Примеры
Мок с возвращаемым значением:
```php
$postData = [
    'record_id' => 1,
    'pos' => 2,
    'dsm_template' => 3,
    'dsm_settings' => 4,
    'dsm_fields' => 5,
    'item_id' => 6,
    'product_no' => 7,
];

$mock = HttpClientMocker::mock(Site::getPool() . '/crm/makeTemplate', Request::METHOD_POST)
    ->expectBody($postData)
    ->willReturnJson(['status' => 'ok']);
```

Мок с кэлбэком:
```php
public function testGenerateCalendarMagicWord() {
    $salesOrderId = 1;
    $testMagicWord = '';

    HttpClientMocker::mock(Site::getPool() . '/crm/addWordToDiscount', Request::METHOD_POST)
        ->singleCall()
        ->willReturnAction(function ($response) use (&$testMagicWord) {
            /**
             * @var Response $response
             */
            $postData = $response->body();
            $this->assertNotEmpty($postData['discount_id'], 'Не передался ID скидки');
            $this->assertNotEmpty($postData['word'], 'Не сгенерировалось волшебное слово');
            $testMagicWord = $postData['word'];
            return json_encode(['status' => 'ok']);
        });

    $resultMagicWord = Site::generateCalendarMagicWord($salesOrderId);
    $this->assertEquals($testMagicWord, $resultMagicWord, 'Вернулось некорректное магическое слово');
}
```

# Доступ к свойствам

## Методы

```php
	PropertyAccess::setStatic($className, $propertyName, $value)
	PropertyAccess::set($object, $propertyName, $value)
	PropertyAccess::getStatic($className, $propertyName)
	PropertyAccess::get($object, $propertyName)
```
* `setStatic` - запись в статическое свойство
* `set` - запись в обычное свойство
* `getStatic` - чтение статического свойства
* `get` - чтение обычного свойство

#### Параметры
* `$className` - для статических свойств, строка, название класса
* `$object` - для обычных свойств, сам объект
* `$propertyName` - для всех, название свойства
* `$value` - mixed, записываемое значение

## Примеры
Чтение
```php
	$pco = PCO::getInstance();
	$coeffs = PropertyAccess::get($pco, '_coeffs');
```
Запись
```php
	PropertyAccess::setStatic(PickPointDelivery::class, '_sessionId', null);
```