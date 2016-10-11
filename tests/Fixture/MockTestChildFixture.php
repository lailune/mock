<?php
namespace ArtSkills\Test\Fixture;

class MockTestChildFixture extends MockTestFixture
{

	/**
	 * для тестов в переопределением
	 *
	 * @return string
	 */
	protected static function _redefinedStaticFunc() {
		return 'redefined protected static';
	}

	/**
	 * для тестов в переопределением
	 *
	 * @return string
	 */
	protected function _redefinedFunc() {
		return 'redefined protected';
	}

	/**
	 * вызов метода для тестов в переопределением
	 *
	 * @param bool $callChild
	 * @param bool $isStatic
	 * @param bool $isRedefined
	 * @param string $callType
	 * @return string
	 */
	public function call($callChild, $isStatic, $isRedefined, $callType) {
		if ($callChild) {
			return self::callChild($isStatic, $isRedefined, $callType);
		} else {
			return parent::callParent($isStatic, $isRedefined, $callType);
		}
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
	public function callChild($isStatic, $isRedefined, $callType) {
		$methodName = self::getInheritTestFuncName($isStatic, $isRedefined);
		if ($isStatic) {
			if ($callType == 'self') {
				return self::$methodName();
			} elseif ($callType == 'static') {
				return static::$methodName();
			} elseif ($callType == 'parent') {
				return parent::$methodName();
			} else {
				throw new \Exception('bad call type');
			}
		} else {
			return $this->$methodName();
		}
	}

	/**
	 * для простых тестов класса-наследника
	 *
	 * @return string
	 */
	protected static function _childOnlyFunc() {
		return 'original child only';
	}

	/**
	 * для простых тестов класса-наследника
	 *
	 * @return string
	 */
	public static function callChildOnlyProtected() {
		return self::_childOnlyFunc();
	}

}