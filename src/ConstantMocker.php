<?php
namespace ArtSkills\Mock;

/**
 * Мокалка констант в классах
 */
class ConstantMocker
{
	/**
	 * Список мокнутых констант
	 *
	 * @var array
	 */
	private static $_constantList = [];

	/**
	 * Заменяем значение константы
	 *
	 * @param string|null $className
	 * @param string $constantName
	 * @param mixed $newValue
	 * @throws \PHPUnit_Framework_AssertionFailedError|\Exception
	 */
	public static function mock($className, $constantName, $newValue) {
		if (strlen($className)) {
			$fullName = $className . '::' . $constantName;
		} else {
			$fullName = $constantName;
		}
		$origValue = @constant($fullName);
		if ($origValue === null) {
			MethodMocker::fail('Constant ' . $fullName . ' is not defined!');
		}
		if (isset(self::$_constantList[$fullName])) {
			MethodMocker::fail('Constant ' . $fullName . ' is already mocked!');
		}

		self::$_constantList[$fullName] = $origValue;
		if (!runkit_constant_redefine($fullName, $newValue)) {
			MethodMocker::fail("Can't redefine constant $fullName!");	// @codeCoverageIgnore
		}
	}

	/**
	 * Возвращаем все обратно
	 */
	public static function restore() {
		foreach (self::$_constantList as $name => $origValue) {
			runkit_constant_redefine($name, $origValue);
		}
		self::$_constantList = [];
	}
}
