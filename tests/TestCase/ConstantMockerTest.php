<?php
namespace ArtSkills\Test\TestCase;

use ArtSkills\Mock\ConstantMocker;
use ArtSkills\Test\Fixture\MockTestFixture;
use \Exception;

/**
 * @covers \ArtSkills\Mock\ConstantMocker
 */
class ConstantMockerTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_CONST_NAME = MockTestFixture::CLASS_CONST_NAME;
    const GLOBAL_CONST_NAME = MockTestFixture::GLOBAL_CONST_NAME;

    /**
     * Мок константы в классе
     */
    public function testClassMock() {
    	$originalValue = MockTestFixture::TEST_CONSTANT;
        $mockValue = 'qqq';
        ConstantMocker::mock(MockTestFixture::class, self::CLASS_CONST_NAME, $mockValue);
        $this->assertEquals($mockValue, MockTestFixture::TEST_CONSTANT);

        ConstantMocker::restore();
        $this->assertEquals($originalValue, MockTestFixture::TEST_CONSTANT);
    }

    /**
     * Мок константы вне класса
     */
    public function testSingleMock() {
    	$originalValue = constant(self::GLOBAL_CONST_NAME);
        $mockValue = 'qqq';
        ConstantMocker::mock(null, self::GLOBAL_CONST_NAME, $mockValue);
        $this->assertEquals($mockValue, constant(self::GLOBAL_CONST_NAME));

        ConstantMocker::restore();
        $this->assertEquals($originalValue, constant(self::GLOBAL_CONST_NAME));
    }

    /**
     * Проверка на существование константы
     *
     * @expectedException Exception
	 * @expectedExceptionMessage is not defined!
     */
    public function testConstantExists() {
        ConstantMocker::mock(null, 'BAD_CONST', 'bad');
    }

    /**
     * Дважды одно и то же мокнули
     *
     * @expectedException Exception
	 * @expectedExceptionMessage is already mocked!
	 */
    public function testConstantDoubleMock() {
        ConstantMocker::mock(null, self::GLOBAL_CONST_NAME, '1');
        ConstantMocker::mock(null, self::GLOBAL_CONST_NAME, '2');
    }
}
