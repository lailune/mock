<?php
namespace ArtSkills\Test\TestCase;

use ArtSkills\Mock\MethodMockerEntity;
use ArtSkills\Test\Fixture\MockTestChildFixture;
use ArtSkills\Test\Fixture\MockTestFixture;
use \Exception;

/**
 * @covers \ArtSkills\Mock\MethodMockerEntity
 */
class MethodMockerEntityTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * Вызов нужного метода
	 *
	 * @param MockTestFixture $instance
	 * @param bool $isPrivate
	 * @param bool $isProtected
	 * @return string
	 */
	private function _callFixtureMethod($instance, $isPrivate, $isProtected) {
		if ($isPrivate) {
			if (empty($instance)) {
				return MockTestFixture::callPrivateStatic();
			} else {
				return $instance->callPrivate();
			}
		} elseif ($isProtected) {
			if (empty($instance)) {
				return MockTestFixture::callProtectedStatic();
			} else {
				return $instance->callProtected();
			}
		} else {
			if (empty($instance)) {
				return MockTestFixture::staticFunc();
			} else {
				return $instance->publicFunc();
			}
		}
	}

	/**
	 * тестируемые методы
	 * @return array
	 */
	public function mockMethodsProvider() {
		return [
			['publicFunc', false, false, false],
			['staticFunc', true, false, false],
			['_privateFunc', false, true, false],
			['_privateStaticFunc', true, true, false],
			['_protectedFunc', false, false, true],
			['_protectedStaticFunc', true, false, true],
		];
	}

	/**
	 * тесты моков всех сочетаний public/protected/private static/non-static
	 * @dataProvider mockMethodsProvider
	 *
	 * @param string $methodName
	 * @param bool $isStatic
	 * @param bool $isPrivate
	 * @param bool $isProtected
	 */
	public function testSimpleMocks($methodName, $isStatic, $isPrivate, $isProtected) {
		if ($isStatic) {
			$instance = null;
		} else {
			$instance = new MockTestFixture();
		}
		$originalResult = $this->_callFixtureMethod($instance, $isPrivate, $isProtected);
		$mockResult = "mock " . $methodName;
		$mock = new MethodMockerEntity('mockid', MockTestFixture::class, $methodName, false, function() use($mockResult) {return $mockResult;});
		$this->assertEquals($mockResult, $this->_callFixtureMethod($instance, $isPrivate, $isProtected));
		unset($mock);

		$this->assertEquals($originalResult, $this->_callFixtureMethod($instance, $isPrivate, $isProtected));
	}

	/**
	 * Мок на несуществующий класс
	 *
	 * @expectedException Exception
	 * @expectedExceptionMessage  class "badClass" does not exist!
	 */
	public function testMockBadClass() {
		new MethodMockerEntity('mockid', 'badClass', '_protectedFunc');
	}

	/**
	 * Мок на несуществующий метод
	 *
	 * @expectedException Exception
	 * @expectedExceptionMessage  method "badMethod" in class "ArtSkills\Test\Fixture\MockTestFixture" does not exist!
	 */
	public function testMockBadMethod() {
		new MethodMockerEntity('mockid', MockTestFixture::class, 'badMethod');
	}

	/**
	 * Восстановленный мок, для тестов того, что с ним ничего нельзя сделать
	 * @return MethodMockerEntity
	 */
	private function _getRestoredMock() {
		$mock = $this->_getMock();
		$mock->expectCall(0);
		$mock->restore();
		return $mock;
	}

	/**
	 * Мок вернули, а его конфигурируют
	 *
	 * @expectedException Exception
	 * @expectedExceptionMessage   mock entity is restored!
	 */
	public function testRestoredExpectCall() {
		$this->_getRestoredMock()->expectCall();
	}

	/**
	 * Мок вернули, а его конфигурируют
	 *
	 * @expectedException Exception
	 * @expectedExceptionMessage   mock entity is restored!
	 */
	public function testRestoredExpected() {
		$this->_getRestoredMock()->expectArgs(false);
	}

	/**
	 * Мок вернули, а его конфигурируют
	 *
	 * @expectedException Exception
	 * @expectedExceptionMessage   mock entity is restored!
	 */
	public function testRestoredWillReturnValue() {
		$this->_getRestoredMock()->willReturnValue(true);
	}

	/**
	 * Мок вернули, а его конфигурируют
	 *
	 * @expectedException Exception
	 * @expectedExceptionMessage   mock entity is restored!
	 */
	public function testRestoredWillReturnAction() {
		$this->_getRestoredMock()->willReturnAction(function ($args) {
			return $args;
		});
	}

	/**
	 * Мок вернули, а его вызывают
	 *
	 * @expectedException Exception
	 * @expectedExceptionMessage   mock entity is restored!
	 */
	public function testRestoredDoAction() {
		$this->_getRestoredMock()->doAction([]);
	}

	/**
	 * Метод без аргументов
	 *
	 * @expectedException Exception
	 * @expectedExceptionMessage  mock entity is restored!
	 */
	public function testRestoredExpectArgs() {
		$this->_getRestoredMock()->expectArgs();
	}

	/**
	 * Мок для тестов
	 * @return MethodMockerEntity
	 */
	private function _getMock() {
		return new MethodMockerEntity('mockid', MockTestFixture::class, 'staticFunc', false);
	}

	/**
	 * Вызывали ли мок хотя бы раз
	 *
	 * @expectedException Exception
	 * @expectedExceptionMessage  is not called!
	 */
	public function testMockCallCheck() {
		$this->_getMock();
	}

	/**
	 * Метод без аргументов
	 *
	 * @expectedException Exception
	 * @expectedExceptionMessage  method expectArgs() requires at least one arg!
	 */
	public function testExpectedArgs() {
		$mock = $this->_getMock()->expectCall(0);
		$mock->expectArgs();
	}




	/**
	 * Для тестов наследования
	 *
	 * @return array
	 */
	public function mockInheritedProvider() {
		return [
			/* тип вызова,
			метод переопределён?,
			замокать класс-наследник? (или родитель),
			вызываемый метод определён в наследнике? (или в родителе),
			результат - замокан? (или вернётся исходный) */
			['this', false, false, false, true],
			['this', false, false, true, true],
			//['this', false, true, false, true],	// runkit restore fail (segfault for public)
			//['this', false, true, true, true],	// runkit restore fail (segfault for public)
			['this', true, false, false, false],
			['this', true, false, true, false],
			//['this', true, true, false, true],	// runkit restore fail (segfault for public)
			//['this', true, true, true, true],		// runkit restore fail (segfault for public & private)


			['self', false, false, false, true],
			['self', false, false, true, true],
			//['self', false, true, false, false],	// not fails, but corrupts class (even for public and private)
			//['self', false, true, true, true],	// runkit restore fail (segfault for public)
			['self', true, false, false, true],
			['self', true, false, true, false],
			//['self', true, true, false, false],	// ok
			//['self', true, true, true, true],		// runkit restore fail (segfault for public and private)

			['static', false, false, false, true],
			['static', false, false, true, true],
			//['static', false, true, false, true],	// runkit restore fail (segfault for public)
			//['static', false, true, true, true],	// runkit restore fail (segfault for public)
			['static', true, false, false, false],
			['static', true, false, true, false],
			//['static', true, true, false, true],	// runkit restore fail (segfault for public)
			//['static', true, true, true, true],	// runkit restore fail (segfault for public and private)

			['parent', false, false, true, true],
			//['parent', false, true, true, false],	// ok
			['parent', true, false, true, true],
			//['parent', true, true, true, false],	// ok

			// упомянутые сегфолты выпадают не сразу, а на одном из последующих моков (по крайней мере при следующем вызове через this)

			// вывод: не стоит мокать отнаследованный метод, не зависимо от того, переопределён он или нет
			// даже если это на самом деле не отнаследованный метод, а private с таким же названием
		];
	}

	/**
	 * тесты моков с наследованием
	 * уже не имеет смысл, все интересные тесты отключены из-за проблем с ранкитом
	 * можно использовать для проверки ранкита, включив интересные тесты
	 *
	 * @dataProvider mockInheritedProvider
	 *
	 * @param string $callType		тип вызова
	 * @param bool $isRedefined		метод переопределён?
	 * @param bool $mockChild		замокать класс-наследник? (или родитель)
	 * @param bool $callChild		вызываемый метод определён в наследнике? (или в родителе)
	 * @param bool $changedResult	результат - замокан? (или вернётся исходный)
	 */
	public function testInheritedMocks($callType, $isRedefined, $mockChild, $callChild, $changedResult) {
		if (!$callChild && ($callType == 'parent')) {
			self::fail('бред');
		}
		$isStatic = ($callType != 'this');
		$methodName = MockTestChildFixture::getInheritTestFuncName($isStatic, $isRedefined);
		if ($mockChild) {
			$mockClass = MockTestChildFixture::class;
		} else {
			$mockClass = MockTestFixture::class;
		}

		$testObject = new MockTestChildFixture();
		$originalResult = $testObject->call($callChild, $isStatic, $isRedefined, $callType);

		$mockResult = "mock " . $methodName . ' ' . $callType . ' ' . (int)$mockChild . ' ' . (int)$callChild;
		$mock = new MethodMockerEntity('mockid', $mockClass, $methodName, false, function() use($mockResult) {return $mockResult;});

		if ($changedResult) {
			$expectedResult = $mockResult;
		} else {
			$expectedResult = $originalResult;
		}
		$actualResult = $testObject->call($callChild, $isStatic, $isRedefined, $callType);

		$this->assertEquals($expectedResult, $actualResult);
		unset($mock);

		$this->assertEquals($originalResult, $testObject->call($callChild, $isStatic, $isRedefined, $callType));
	}


	/**
	 * мок не отнаследованного protected метода в классе-наследнике
	 */
	public function testProtectedMockChild() {
		$originalResult = MockTestChildFixture::callChildOnlyProtected();
		$mockResult = 'mock child only protected';
		$mock = new MethodMockerEntity('mockid', MockTestChildFixture::class, '_childOnlyFunc', false, "return '$mockResult';");
		$this->assertEquals($mockResult, MockTestChildFixture::callChildOnlyProtected());
		unset($mock);

		$this->assertEquals($originalResult, MockTestChildFixture::callChildOnlyProtected());
	}

	/**
	 * нельзя просниффать при полной подмене
	 * @expectedException Exception
	 * @expectedExceptionMessage Sniff mode does not support full mock
	 */
	public function testSniff() {
		new MethodMockerEntity('mockid', MockTestFixture::class, 'staticFunc', true, function() {return 'sniff';});
	}


	/**
	 * нельзя мокать отнаследованное
	 * @expectedException Exception
	 * @expectedExceptionMessage cannot mock method staticFunc in child class
	 */
	public function testCallInherited() {
		new MethodMockerEntity('mockid', MockTestChildFixture::class, 'staticFunc', false, function() {return 'sniff';});
	}


}
