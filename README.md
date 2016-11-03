# Четкая мокалка

Любая подмена работает только в тестовом окружении. Основная цель данного подхода -
тестировать только тот кусок, который нам необходимо без подтягивания прицепа связанных вызовов,
а также отход от разделения логики основного кода для режима тестирования и отладки.

## Доступный функционал
* `MethodMocker` - подмена/снифф методов, вызов приватных методов
* `ConstantMocker` - переопределение констант
* `PropertyAccess` - чтение и запись private и protected свойств

# Подмена методов
```php
MethodMockerEntity MethodMocker::mock(string $className, string $methodName, string|callable|null $newAction = null);
```
`$newAction` необходим в случае полной подменой метода без каких-либо проверок. Полезно в случае переопределения каких-то методов вывода, например `_sendJsonResponse` в CakePHP2.

## Доступные методы `MethodMockerEntity`:
* Манипуляция с кол-вом вызовов
    * `singleCall()` - один раз
    * `anyCall()` - как минимум 1 раз (по-умолчанию)
    * `expectCall(int <кол-во>)` - если мы специально хотим указать, что вызовов быть не должно, то `$mock->expectCall(0);`
* Проверка входных параметров - `expectArgs(mixed <аргумент1>, mixed <аргумент2>, ..)`
* Добавление переменной для `willReturnAction` - `setAdditionalVar(mixed <переменная>)`
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
Мок с кэлбэком и доп. переменной:
```php
$mock = MethodMocker::mock(Auth::class, 'user')
    ->setAdditionalVar(['utm' => 'test', 'id' => 123])
    ->willReturnAction(function ($args, $additionalVar) {
        if (isset($args[0]) { 
            return $additionalVar[$args[0]]; 
        } else {
            return $additionalVar;
        }
    });
    
Auth::user(); // [...]
Auth::user('utm'); // 'test'
    
$mock->setAdditionalVar(['utm' => 'shop', 'id' => 100]);
    
Auth::user(); // [...]
Auth::user('utm'); // 'shop'
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

# Подмена констант
```php
ConstantMocker::mock(string $className, string $constantName, string $newValue)
```

# Вызов private или protected метода
```php
mixed MethodMocker::callPrivate(string|object $object, string $methodName, array|null $args = null)
```

## Примеры
Вызов protected метода обычного класса:
```php
$testObject = new MockTestFixture();
$result = MethodMocker::callPrivate($testObject, '_protectedArgs', ['test arg']);
```
Вызов приватного метода библиотеки:
```php
$result = MethodMocker::callPrivate(MockTestFixture::class, '_privateStaticFunc');
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