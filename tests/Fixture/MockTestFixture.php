<?php
namespace ArtSkills\Test\Fixture;

const SINGLE_TEST_CONST = '666';
/**
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class MockTestFixture
{
	const TEST_CONSTANT = 123;
	const CLASS_CONST_NAME = 'TEST_CONSTANT';
	const GLOBAL_CONST_NAME = __NAMESPACE__ . '\SINGLE_TEST_CONST';
	/**
	 * для тестов
	 *
	 * @var string
	 */
	protected $_protectedProperty = 'testProtected';

	/**
	 * для тестов
	 *
	 * @var string
	 */
	private static $_privateProperty = 'testPrivateStatic';

	/**
	 * Тестовый метод
	 *
	 * @return string
	 */
	public static function staticFunc() {
		return 'original public static';
	}

	/**
	 * Тестовый метод
	 *
	 * @return string
	 */
	public function publicFunc() {
		return 'original public';
	}

	/**
	 * Тестовый метод
	 *
	 * @return string
	 */
	private function _privateFunc() {
		return 'original private';
	}

	/**
	 * Тестовый метод
	 *
	 * @return string
	 */
	private static function _privateStaticFunc() {
		return 'original private static';
	}

	/**
	 * Тестовый метод
	 *
	 * @return string
	 */
	protected function _protectedFunc() {
		return 'original protected';
	}

	/**
	 * Тестовый метод
	 *
	 * @return string
	 */
	protected static function _protectedStaticFunc() {
		return 'original protected static';
	}

	/**
	 * Вызов для проверки _protectedFunc
	 *
	 * @return string
	 */
	public function callProtected() {
		return $this->_protectedFunc();
	}

	/**
	 * Вызов для проверки _privateFunc
	 *
	 * @return string
	 */
	public function callPrivate() {
		return $this->_privateFunc();
	}

	/**
	 * Вызов для проверки _privateStaticFunc
	 *
	 * @return string
	 */
	public static function callPrivateStatic() {
		return self::_privateStaticFunc();
	}

	/**
	 * Вызов для проверки _protectedStaticFunc
	 *
	 * @return string
	 */
	public static function callProtectedStatic() {
		return self::_protectedStaticFunc();
	}

	/**
	 * Тестовый метод
	 *
	 * @return string
	 */
	public function methodNoArgs() {
		return 'original no args';
	}

	/**
	 * Тестовый метод
	 *
	 * @param mixed $first
	 * @param mixed $second
	 * @return string
	 */
	public function methodArgs($first, $second) {
		return $first . ' ' . $second;
	}

	/**
	 * Тестовый метод
	 *
	 * @param mixed $first
	 * @param mixed $second
	 * @return string
	 */
	public static function staticMethodArgs($first, $second) {
		return 'static ' . $first . ' ' . $second;
	}

	/**
	 * Тестовый метод
	 *
	 * @param string $arg
	 * @return string
	 */
	protected function _protectedArgs($arg) {
		return 'protected args ' . $arg;
	}


	/**
	 * для тестов в переопределением
	 *
	 * @return string
	 */
	protected static function _redefinedStaticFunc() {
		return 'parent protected static';
	}

	/**
	 * для тестов в переопределением
	 *
	 * @return string
	 */
	protected function _redefinedFunc() {
		return 'parent protected';
	}

	/**
	 * вызов метода для тестов в переопределением
	 *
	 * @param bool $isStatic
	 * @param bool $isRedefined
	 * @param string $callType
	 * @return string
	 * @throws \Exception
	 */
	public function callParent($isStatic, $isRedefined, $callType) {
		$methodName = self::getInheritTestFuncName($isStatic, $isRedefined);
		if ($isStatic) {
			if ($callType == 'self') {
				return self::$methodName();
			} elseif ($callType == 'static') {
				return static::$methodName();
			} else {
				throw new \Exception('bad call type');
			}
		} else {
			return $this->$methodName();
		}
	}

	/**
	 * название метода для тестов в переопределением
	 *
	 * @param bool $isStatic
	 * @param bool $isRedefined
	 * @return string
	 */
	public static function getInheritTestFuncName($isStatic, $isRedefined) {
		if ($isRedefined) {
			$methodName = '_redefined';
		} else {
			$methodName = '_protected';
		}
		if ($isStatic) {
			$methodName .= 'Static';
		}
		$methodName .= 'Func';
		return $methodName;
	}

	/**
	 * Функция с типизированными и обязательными параметрами и передачей по ссылке
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 *
	 * @param MockTestFixture $typedParam
	 * @param mixed $byRefParam
	 * @param array $arrayParam
	 * @param mixed $requiredParam
	 * @param array $mayBeNotArray
	 */
	public static function complexParams(
		MockTestFixture $typedParam, &$byRefParam, array $arrayParam, $requiredParam, $mayBeNotArray = []
	) {
		// noop
	}


	/**
	 * Функция с дефолтными значениями
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 *
	 * @param array $arrayParam
	 * @param float $floatParam
	 * @param string $stringParam
	 * @param bool $boolParam
	 * @param null $nullParam
	 * @return array
	 */
	public static function defaultValues(
		$arrayParam = ['a' => [null]], $floatParam = 2.5, $stringParam = 'asd', $boolParam = true, $nullParam = null
	) {
		return [];
	}

	/**
	 * Функция с вариадиком
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 * @param array ...$variadicParam
	 * @return array
	 */
	public static function variadicParam(...$variadicParam) {
		return [];
	}





}