<?php
namespace ArtSkills\Mock;

use \ReflectionMethod;


/**
 * Мокает метды в классах так, чтобы в основном коде не пришлось править ровным счетом ничего!
 * необходим модуль runkit
 */
class MethodMocker
{

	/**
	 * Стэк моков в рамках одного теста
	 *
	 * @var array
	 */
	private static $_mockList = [];

	/**
	 * Мокаем метод
	 *
	 * @param string $className абсолютный путь к классу
	 * @param string $methodName
	 * @param string|null $newAction новое событие метода
	 * @return MethodMockerEntity
	 * @throws \Exception
	 */
	public static function mock($className, $methodName, $newAction = null) {
		self::_newMockCheck($className, $methodName);
		$key = self::_buildKey($className, $methodName);
		self::$_mockList[$key] = new MethodMockerEntity($key, $className, $methodName, false, $newAction);
		return self::$_mockList[$key];
	}

	/**
	 * Снифаем метод
	 *
	 * @param string $className
	 * @param string $methodName
	 * @param null|callable $sniffAction функция, вызываемая при вызове подслушиваемого метода: function($args,
	 *     $originalResult) {}, $originalResult - результат выполнения подслушиваемого метода
	 * @return MethodMockerEntity
	 * @throws \Exception
	 */
	public static function sniff($className, $methodName, $sniffAction = null) {
		self::_newMockCheck($className, $methodName);
		$key = self::_buildKey($className, $methodName);
		self::$_mockList[$key] = new MethodMockerEntity($key, $className, $methodName, true);
		if ($sniffAction !== null) {
			self::$_mockList[$key]->willReturnAction($sniffAction);
		}
		return self::$_mockList[$key];
	}

	/**
	 * Проверка на возможность замокать метод
	 *
	 * @param string $className
	 * @param string $methodName
	 * @throws \Exception
	 */
	private static function _newMockCheck($className, $methodName) {
		$key = self::_buildKey($className, $methodName);
		if (isset(self::$_mockList[$key])) {
			throw new \Exception($key . ' already mocked!');
		}
	}

	/**
	 * Формируем уникальный ключ
	 *
	 * @param string $className
	 * @param string $methodName
	 * @return string
	 */
	private static function _buildKey($className, $methodName) {
		return $className . '::' . $methodName;
	}

	/**
	 * Мок событие
	 *
	 * @param string $mockKey
	 * @param array $args
	 * @param mixed $origMethodResult результат выполнения оригинального метода в режиме снифа
	 * @return mixed
	 * @throws \Exception
	 */
	public static function doAction($mockKey, $args, $origMethodResult = null) {
		if (!isset(self::$_mockList[$mockKey])) {
			throw new \Exception($mockKey . " mock object doesn't exist!");
		}

		/**
		 * @var MethodMockerEntity $mockObject
		 */
		$mockObject = self::$_mockList[$mockKey];
		return $mockObject->doAction($args, $origMethodResult);
	}

	/**
	 * Возвращаем все подмененные методы
	 *
	 * @param bool $hasFailed был ли тест завален
	 */
	public static function restore($hasFailed = false) {
		/** @var MethodMockerEntity $mock */
		foreach (self::$_mockList as $mock) {
			$mock->restore($hasFailed);
		}

		self::$_mockList = [];
	}

	/**
	 * Делает protected и private методы публичными
	 *
	 * @param object|string $object. строка с названием класса для статических, непосредственно инстанс для обычных методов
	 * @param string $methodName
	 * @param array|null $args аргументы вызова
	 * @return mixed
	 * @throws \Exception
	 */
	public static function callPrivate($object, $methodName, $args = null) {
		if (is_string($object)) {
			$className = $object;
			$object = null;
			if (!class_exists($className)) {
				throw new \Exception('class "' . $className . '" does not exist!');
			}
		} else {
			$className = get_class($object);
		}

		if (!method_exists($className, $methodName)) {
			throw new \Exception('method "' . $methodName . '" in class "' . $className . '" does not exist!');
		}

		$reflectionMethod = new ReflectionMethod($className, $methodName);
		if (!$reflectionMethod->isPrivate() && !$reflectionMethod->isProtected()) {
			throw new \Exception('method "' . $methodName . '" in class "' . $className . '" is not private and is not protected!');
		}

		$reflectionMethod->setAccessible(true);
		if ($args !== null) {
			$result = $reflectionMethod->invokeArgs($object, $args);
		} else {
			$result = $reflectionMethod->invoke($object);
		}

		$reflectionMethod->setAccessible(false);
		return $result;
	}
}
