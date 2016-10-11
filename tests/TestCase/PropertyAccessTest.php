<?php
namespace ArtSkills\Test\TestCase;

use ArtSkills\Mock\PropertyAccess;
use ArtSkills\Test\Fixture\MockTestFixture;

/**
 * @covers \ArtSkills\Mock\PropertyAccess
 */
class PropertyAccessTest extends \PHPUnit_Framework_TestCase
{

	/** тест чтения и записи */
	public function test() {
		$testObject = new MockTestFixture();
		self::assertEquals('testProtected', PropertyAccess::get($testObject, '_protectedProperty'), 'Не прочиталось protected свойство');
		self::assertEquals('testPrivateStatic', PropertyAccess::getStatic(MockTestFixture::class, '_privateProperty'), 'Не прочиталось private static свойство');

		$newValue = 'newTestValue';
		PropertyAccess::set($testObject, '_protectedProperty', $newValue);
		self::assertEquals($newValue, PropertyAccess::get($testObject, '_protectedProperty'), 'Не записалось protected свойство');

		$newStaticValue = 'newTestStaticValue';
		PropertyAccess::setStatic(MockTestFixture::class, '_privateProperty', $newStaticValue);
		self::assertEquals($newStaticValue, PropertyAccess::getStatic($testObject, '_privateProperty'), 'Не записалось private static свойство');
	}

	/**
	 * тест несуществующего свойства
	 * @expectedException \Exception
	 * @expectedExceptionMessage does not exist
	 */
	public function testBadProperty() {
		PropertyAccess::setStatic(MockTestFixture::class, '_unexistent', 'asd');
	}

}


