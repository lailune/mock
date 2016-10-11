<?php
namespace ArtSkills\Test\TestCase;

use ArtSkills\Mock\MethodMocker;
use ArtSkills\Test\Fixture\MockTestFixture;

/**
 * @covers \ArtSkills\Mock\MethodMocker
 * @covers \ArtSkills\Mock\MethodMockerEntity
 */
class MethodMockerTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @inheritdoc
	 */
	public function tearDown() {
		MethodMocker::restore(true);
	}

	/**
	 * простой тест
	 */
	public function testSimpleMock() {
		$mockResult = 'simple mock result';
		$originalResult = MockTestFixture::staticFunc();

		MethodMocker::mock(MockTestFixture::class, 'staticFunc')->willReturnValue($mockResult);
		$result = MockTestFixture::staticFunc();
		self::assertEquals($mockResult, $result);

		MethodMocker::restore();
		$result = MockTestFixture::staticFunc();
		self::assertEquals($originalResult, $result);
	}

	/**
	 * тест WillReturnAction
	 */
	public function testWillReturnAction() {
		$argsCalled = ['arg1', 'arg2'];
		$isCalled = false;
		$returnValue = 'mock action return';

		MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs')
			->willReturnAction(function ($argsReceived) use ($argsCalled, $returnValue, &$isCalled) {
				self::assertEquals($argsCalled, $argsReceived);
				$isCalled = true;
				return $returnValue;
			});

		$result = MockTestFixture::staticMethodArgs(...$argsCalled);
		self::assertTrue($isCalled);
		self::assertEquals($returnValue, $result);
	}

	/**
	 * тест sniff
	 */
	public function testSniff() {
		$argsCalled = ['arg1', 'arg2'];
		$isCalled = false;
		$returnValue = MockTestFixture::staticMethodArgs(...$argsCalled);

		MethodMocker::sniff(MockTestFixture::class, 'staticMethodArgs',
			function ($argsReceived) use ($argsCalled, $returnValue, &$isCalled) {
				self::assertEquals($argsCalled, $argsReceived);
				$isCalled = true;
				return 'sniff not return';
			}
		);
		$result = MockTestFixture::staticMethodArgs(...$argsCalled);
		self::assertTrue($isCalled);
		self::assertEquals($returnValue, $result);
	}

	/**
	 * Дважды замокали один метов
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage methodNoArgs already mocked!
	 */
	public function testDuplicateMock() {
		MethodMocker::mock(MockTestFixture::class, 'methodNoArgs');
		MethodMocker::mock(MockTestFixture::class, 'methodNoArgs');
	}

	/**
	 * Вызвали несуществующий запмоканый метод
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage notExists mock object doesn't exist!
	 */
	public function testNotExistsMockCall() {
		MethodMocker::doAction('notExists', []);
	}




	/**
	 * Делаем приватную статичную функцию доступной
	 */
	public function testCallPrivate() {
		$this->assertEquals('original private static', MethodMocker::callPrivate(MockTestFixture::class, '_privateStaticFunc'));
	}

	/**
	 * Делаем доступным protected метод
	 */
	public function testCallProtected() {
		$testObject = new MockTestFixture();
		$this->assertEquals('protected args test arg', MethodMocker::callPrivate($testObject, '_protectedArgs', ['test arg']));
	}

	/**
	 * Несуществующий класс
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage class "BadClass" does not exist!
	 */
	public function testCallPrivateBadClass() {
		MethodMocker::callPrivate('BadClass', 'BlaBla');
	}

	/**
	 * Несуществующий метод
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage method "BlaBla" in class "ArtSkills\Test\Fixture\MockTestFixture" does not exist!
	 */
	public function testCallPrivateBadMethod() {
		MethodMocker::callPrivate(MockTestFixture::class, 'BlaBla');
	}

	/**
	 * вызов публичного
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage is not private and is not protected!
	 */
	public function testCallPrivatePublic() {
		MethodMocker::callPrivate(MockTestFixture::class, 'staticFunc');
	}






	/**
	 * ожидалось без аргументов, а они есть
	 * @expectedException \Exception
	 * @expectedExceptionMessage expected no args, but real args:
	 */
	public function testUnexpectedArgs() {
		MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs')->expectArgs(false);
		MockTestFixture::staticMethodArgs('asd', 'qwe');
	}

	/**
	 * меньше аргументов, чем ожидалось
	 * @expectedException \Exception
	 * @expectedExceptionMessage expected args:
	 */
	public function testLessArgs() {
		MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs')->expectArgs('asd', 'qwe', 'zxc');
		MockTestFixture::staticMethodArgs('asd', 'qwe');
	}

	/**
	 * больше аргументов, чем ожидалось
	 * @expectedException \Exception
	 * @expectedExceptionMessage expected args:
	 */
	public function testMoreArgs() {
		MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs')->expectArgs('asd');
		MockTestFixture::staticMethodArgs('asd', 'qwe');
	}

	/**
	 * не то значение аргумента
	 * @expectedException \Exception
	 * @expectedExceptionMessage expected args:
	 */
	public function testBadArgs() {
		MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs')->expectArgs('asd', 'zxc');
		MockTestFixture::staticMethodArgs('asd', 'qwe');
	}

	/**
	 * аргументы не в том порядке
	 * @expectedException \Exception
	 * @expectedExceptionMessage expected args:
	 */
	public function testOrderArgs() {
		MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs')->expectArgs('qwe', 'asd');
		MockTestFixture::staticMethodArgs('asd', 'qwe');
	}

	/**
	 * вызов с хорошими аргументами
	 */
	public function testGoodArgs() {
		$testObject = new MockTestFixture();
		$returnValue = 'mocked no args';
		MethodMocker::mock(MockTestFixture::class, 'methodNoArgs')->expectArgs(false)->willReturnValue($returnValue);
		self::assertEquals($returnValue, $testObject->methodNoArgs());

		$args = ['good', 'args'];
		MethodMocker::sniff(MockTestFixture::class, 'staticMethodArgs')->expectArgs(...$args);
		self::assertEquals('static good args', MockTestFixture::staticMethodArgs(...$args));

		$arg = 'goooood arrrrgs';
		MethodMocker::sniff(MockTestFixture::class, '_protectedArgs')->expectArgs($arg);
		self::assertEquals('protected args goooood arrrrgs', MethodMocker::callPrivate($testObject, '_protectedArgs', [$arg]));
	}



	/**
	 * не вызван
	 * @expectedException \Exception
	 * @expectedExceptionMessage is not called!
	 */
	public function testNotCalled() {
		MethodMocker::mock(MockTestFixture::class, 'methodNoArgs');
		MethodMocker::restore();
	}

	/**
	 * вызван меньше, чем ожидалось
	 * @expectedException \Exception
	 * @expectedExceptionMessage is called 1 times, expected 2
	 */
	public function testCalledLess() {
		MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs')->expectCall(2);
		MockTestFixture::staticMethodArgs(1, 2);
		MethodMocker::restore();
	}

	/**
	 * вызван больше, чем ожидалось
	 * @expectedException \Exception
	 * @expectedExceptionMessage expected 1 calls, but more appeared
	 */
	public function testCalledMore() {
		MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs')->singleCall();
		MockTestFixture::staticMethodArgs(1, 2);
		MockTestFixture::staticMethodArgs(1, 2);
	}


	/**
	 * вызов правильное количество раз
	 */
	public function testGoodCallCount() {
		$testObject = new MockTestFixture();
		MethodMocker::mock(MockTestFixture::class, 'methodNoArgs')->expectCall(2);
		$testObject->methodNoArgs();
		$testObject->methodNoArgs();

		MethodMocker::sniff(MockTestFixture::class, 'staticFunc')->anyCall();
		MockTestFixture::staticFunc();
		MockTestFixture::staticFunc();
		MockTestFixture::staticFunc();

		MethodMocker::sniff(MockTestFixture::class, '_protectedArgs')->expectCall(0);
		MethodMocker::restore();
		self::assertTrue(true); // всё хорошо, не было ексепшнов
	}

}


