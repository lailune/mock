<?php
namespace ArtSkills\Mock;

/**
 * Обращения к private и protected свойствам
 */
class PropertyAccess
{

	/**
	 * Запись в статическое свойство
	 *
	 * @param string $className
	 * @param string $propertyName
	 * @param mixed $value
	 */
	public static function setStatic($className, $propertyName, $value) {
		$property = new \ReflectionProperty($className, $propertyName);
		$property->setAccessible(true);
		$property->setValue($value);
	}

	/**
	 * Запись в обычное свойство
	 *
	 * @param object $object
	 * @param string $propertyName
	 * @param mixed $value
	 */
	public static function set($object, $propertyName, $value) {
		$property = new \ReflectionProperty(get_class($object), $propertyName);
		$property->setAccessible(true);
		$property->setValue($object, $value);
	}


	/**
	 * Чтение статического свойства
	 *
	 * @param string $className
	 * @param string $propertyName
	 * @return mixed
	 */
	public static function getStatic($className, $propertyName) {
		$property = new \ReflectionProperty($className, $propertyName);
		$property->setAccessible(true);
		return $property->getValue();
	}

	/**
	 * Чтение  обычного свойства
	 *
	 * @param object $object
	 * @param string $propertyName
	 * @return mixed
	 */
	public static function get($object, $propertyName) {
		$property = new \ReflectionProperty(get_class($object), $propertyName);
		$property->setAccessible(true);
		return $property->getValue($object);
	}


}