<?php
namespace ArtSkills\Mock;

use \ReflectionMethod;

/**
 * Мок метода
 */
class MethodMockerEntity
{
	const METHOD_PUBLIC = 256; // public
	const METHOD_PROTECTED = 512; // protected
	const METHOD_PRIVATE = 1024; // private
	const METHOD_STATIC = 1; // static

	const RENAME_PREFIX = '___rk_'; // префикс при переименовании метода

	/**
	 * Метод должен быть вызван хотя бы раз
	 */
	const EXPECT_CALL_ONCE = -1;

	/**
	 * ID текущего мока в стеке MethodMocker
	 *
	 * @var string
	 */
	private $_id = '';

	/**
	 * Файл, в котором мокнули
	 *
	 * @var string
	 */
	private $_callerFile = '';

	/**
	 * Строка вызова к MethodMocker::mock
	 *
	 * @var int
	 */
	private $_callerLine = 0;

	/**
	 * Класс метода
	 *
	 * @var string
	 */
	private $_class = '';

	/**
	 * Мокаемый метод
	 *
	 * @var string
	 */
	private $_method = '';

	/**
	 * Тип мокаемого метода, логическое ИЛИ METHOD_PUBLIC, METHOD_PROTECTED, METHOD_PRIVATE, METHOD_STATIC
	 *
	 * @var int
	 */
	private $_type = self::METHOD_PUBLIC;

	/**
	 * Новое подменяемое событие
	 *
	 * @var callable|string|null
	 */
	private $_action = null;

	/**
	 * Сколько раз ожидается вызов функции
	 *
	 * @var int
	 */
	private $_expectedCallCount = self::EXPECT_CALL_ONCE;

	/**
	 * Предполагаемые аргументы
	 *
	 * @var null|array
	 */
	private $_expectedArgs = null;

	/**
	 * Возвращаемый результат
	 *
	 * @var null|mixed
	 */
	private $_returnValue = null;

	/**
	 * Возвращаемое событие
	 *
	 * @var null|callable
	 */
	private $_returnAction = null;

	/**
	 * Был ли вызов данного мока
	 *
	 * @var bool
	 */
	private $_isCalled = false;

	/**
	 * Кол-во вызовов данного мока
	 *
	 * @var int
	 */
	private $_callCounter = 0;

	/**
	 * Мок отработал и все вернул в первоначальный вид
	 *
	 * @var bool
	 */
	private $_mockRestored = false;

	/**
	 * Функция не подменяется, а снифается
	 *
	 * @var bool
	 */
	private $_sniffMode = false;

	/**
	 * MethodMockerEntity constructor.
	 * Не рекомендуется создавать непосредственно, лучше через MethodMocker
	 * При непосредственном создании доступна только полная подмена
	 * При полной подмене не доступны функции expectCall(), expectArgs() и willReturn()
	 * и $this и self обращаются к подменяемому объекту/классу
	 * Неполная подмена делается только через MethodMocker
	 * доступен весь функционал, $this и self берутся из вызываемого контекста
	 *
	 * @throws \Exception
	 * @param string $mockId
	 * @param string $className
	 * @param string $methodName
	 * @param bool $sniffMode
	 * @param null|callable|string $newAction - полная подмена
	 */
	public function __construct($mockId, $className, $methodName, $sniffMode = false, $newAction = null) {
		$calledFrom = debug_backtrace();
		$this->_callerFile = isset($calledFrom[1]['file']) ? $calledFrom[1]['file'] : $calledFrom[0]['file'];
		$this->_callerLine = isset($calledFrom[1]['line']) ? $calledFrom[1]['line'] : $calledFrom[0]['line'];

		$this->_id = $mockId;
		$this->_class = $className;
		$this->_method = $methodName;
		$this->_action = $newAction;
		$this->_sniffMode = $sniffMode;
		if (!empty($newAction) && $sniffMode) {
			throw new \Exception('Sniff mode does not support full mock');
		}
		$this->_init();
	}

	/**
	 * Инициализация
	 *
	 * @throws \Exception
	 */
	private function _init() {
		$this->_checkCanMock();

		$flags = 0;
		$reflectionMethod = new ReflectionMethod($this->_class, $this->_method);
		if ($reflectionMethod->isPublic()) {
			$flags |= RUNKIT_ACC_PUBLIC;
			$this->_type |= self::METHOD_PUBLIC;
		}
		if ($reflectionMethod->isProtected()) {
			$flags |= RUNKIT_ACC_PROTECTED;
			$this->_type |= self::METHOD_PROTECTED;
		}
		if ($reflectionMethod->isPrivate()) {
			$flags |= RUNKIT_ACC_PRIVATE;
			$this->_type |= self::METHOD_PRIVATE;
		}
		if ($reflectionMethod->isStatic()) {
			$flags |= RUNKIT_ACC_STATIC;
			$this->_type |= self::METHOD_STATIC;
		}

		$this->_mockOriginalMethod($flags);
	}

	/**
	 * Омечаем, что функция должна вызываться разово
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public function singleCall() {
		return $this->expectCall(1);
	}

	/**
	 * Омечаем, что функция должна вызываться как минимум 1 раз
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public function anyCall() {
		return $this->expectCall(self::EXPECT_CALL_ONCE);
	}

	/**
	 * Ограничение на количество вызовов данного мока
	 *
	 * @param int $times
	 * @return $this
	 * @throws \Exception
	 */
	public function expectCall($times = 1) {
		$this->_checkNotRestored();
		$this->_expectedCallCount = $times;
		return $this;
	}

	/**
	 * Устанавливаем ожидаемые аргументы, необходимо указать как минимум 1. Если данный метод не вызывать, то проверка
	 * на аргументы не проводится.
	 * Если нужно явно задать отсутствие аргументов, то задается один параметр false: ->expected(false)
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public function expectArgs() {
		$this->_checkNotRestored();

		if (!func_num_args()) {
			throw new \Exception($this->_getErrorMessage('method expectArgs() requires at least one arg!'));
		}

		$args = func_get_args();
		if (count($args) === 1 && $args[0] === false) {
			$this->_expectedArgs = false;
		} else {
			$this->_expectedArgs = $args;
		}


		return $this;
	}

	/**
	 * Что вернет подменённая функция
	 *
	 * @param mixed $value
	 * @return $this
	 * @throws \Exception
	 */
	public function willReturnValue($value) {
		$this->_checkNotRestored();

		$this->_returnAction = null;
		$this->_returnValue = $value;
		return $this;
	}

	/**
	 * Подменённая функция вернет результат функции $action(array Аргументы, [mixed Результат от оригинального метода])
	 * Второй поараметр заполняется только в режиме снифа метода
	 * Пример:
	 * ->willReturnAction(function($args){
	 *    return 'mocked: '.$args[0].' '.$args[1];
	 * });
	 *
	 * @param callable $action
	 * @return $this
	 * @throws \Exception
	 */
	public function willReturnAction($action) {
		$this->_checkNotRestored();

		$this->_returnAction = $action;
		$this->_returnValue = null;
		return $this;
	}

	/**
	 * Событие оригинальной функции
	 *
	 * @param array $args массив переданных аргументов к оригинальной функции
	 * @param mixed $origMethodResult
	 * @return mixed
	 * @throws \Exception
	 */
	public function doAction($args, $origMethodResult = null) {
		$this->_checkNotRestored();

		if (($this->_expectedCallCount > self::EXPECT_CALL_ONCE) && ($this->_callCounter >= $this->_expectedCallCount)) {
			throw new \Exception($this->_getErrorMessage('expected ' . $this->_expectedCallCount . ' calls, but more appeared'));
		}
		$this->_isCalled = true;
		$this->_callCounter++;

		if ($this->_expectedArgs !== null && !$this->_checkArgs($args)) {
			throw new \Exception($this->_getErrorMessage(
				(($this->_expectedArgs === false) ? 'expected no args' : 'expected args: ' . print_r($this->_expectedArgs, true))
				 . ', but real args: ' . print_r($args, true))
			);
		}

		if ($this->_returnValue !== null) {
			return $this->_returnValue;
		} elseif ($this->_returnAction !== null) {
			$action = $this->_returnAction;
			return $action($args, $origMethodResult);
		} else {
			return null;
		}
	}

	/**
	 * Определяем имя переименованного метода
	 *
	 * @return string
	 */
	public function getOriginalMethodName() {
		return self::RENAME_PREFIX . $this->_method;
	}

	/**
	 * Кол-во вызовов данного мока
	 *
	 * @return int
	 */
	public function getCallCount() {
		return $this->_callCounter;
	}

	/**
	 * Деструктор
	 */
	public function __destruct() {
		$this->restore();
	}

	/**
	 * Проверка на вызов, возвращаем оригинальный метод
	 *
	 * @param bool $hasFailed Был ли тест завален
	 * @throws \Exception
	 */
	public function restore($hasFailed = false) {
		if ($this->_mockRestored) {
			return;
		}

		$goodCallCount = (
			(($this->_expectedCallCount == self::EXPECT_CALL_ONCE) && $this->_isCalled)
			|| ($this->_expectedCallCount == $this->getCallCount())
		);

		runkit_method_remove($this->_class, $this->_method);
		runkit_method_rename($this->_class, $this->getOriginalMethodName(), $this->_method);
		$this->_mockRestored = true;

		// ($this->_action === null) - при полной подмене счётчик не используется
		if (!$hasFailed && ($this->_action === null) && !$goodCallCount) {
			throw new \Exception($this->_getErrorMessage(
				$this->_isCalled ? 'is called ' . $this->getCallCount() . ' times, expected ' . $this->_expectedCallCount : 'is not called!'
			));
		}
	}

	/**
	 * Проверяем аргументы
	 *
	 * @param array $realArgs
	 * @return bool
	 */
	private function _checkArgs($realArgs) {
		if ($this->_expectedArgs === false) {
			if (count($realArgs) > 0) {
				// входных значений быть не должно, а они появились
				return false;
			}
			// вызов без аргументов
			return true;
		}

		return ($this->_expectedArgs == $realArgs);
	}

	/**
	 * Мокаем оригинальный метод
	 *
	 * @param int $flags
	 * @throws \Exception
	 */
	private function _mockOriginalMethod($flags) {
		$mockerClass = MethodMocker::class;
		// можно было делать не через строки, а через функции
		// но в таком случае ранкит глючит при наследовании
		if ($this->_sniffMode) {
			$origMethodCall = ($flags & self::METHOD_STATIC ? 'self::' : '$this->') . $this->getOriginalMethodName();
			$mockAction = '$result = ' . $origMethodCall . '(...func_get_args()); ' . $mockerClass . "::doAction('" . $this->_id . "'" . ', func_get_args(), $result); return $result;';
		} else {
			if ($this->_action === null) {
				$mockAction = "return " . $mockerClass . "::doAction('" . $this->_id . "', func_get_args());";
			} else {
				$mockAction = $this->_action;
			}
		}

		runkit_method_rename(
			$this->_class,
			$this->_method,
			$this->getOriginalMethodName()
		);

		if (is_string($mockAction)) {
			$success = runkit_method_add($this->_class, $this->_method, '', $mockAction, $flags);
		} else {
			$success = runkit_method_add($this->_class, $this->_method, $mockAction, $flags);
		}
		if (!$success) {
			throw new \Exception($this->_getErrorMessage("can't mock method"));		// @codeCoverageIgnore
		}
	}

	/**
	 * Формируем сообщение об ошибке
	 *
	 * @param string $msg
	 * @return string
	 */
	private function _getErrorMessage($msg) {
		return $this->_class . '::' . $this->_method . ' (mocked in ' . $this->_callerFile . ' line ' . $this->_callerLine . ') - ' . $msg;
	}

	/**
	 * Если мок восстановлен, то кидает ексепшн
	 * @throws \Exception
	 */
	private function _checkNotRestored() {
		if ($this->_mockRestored) {
			throw new \Exception($this->_getErrorMessage('mock entity is restored!'));
		}
	}

	/**
	 * Проверка, что такой метод можно мокнуть
	 * @throws \Exception
	 */
	private function _checkCanMock() {
		if (!class_exists($this->_class)) {
			throw new \Exception($this->_getErrorMessage('class "' . $this->_class . '" does not exist!'));
		}

		if (!method_exists($this->_class, $this->_method)) {
			throw new \Exception($this->_getErrorMessage('method "' . $this->_method . '" in class "' . $this->_class . '" does not exist!'));
		}

		$reflectionMethod = new ReflectionMethod($this->_class, $this->_method);
		if ($reflectionMethod->getDeclaringClass()->getName() != $this->_class) {
			// если замокать отнаследованный непереопределённый метод, то можно попортить класс
			throw new \Exception($this->_getErrorMessage(
				'method ' . $this->_method . ' is declared in parent class ' . $reflectionMethod->getDeclaringClass()->getName() . ', mock parent instead!'
			));
		}

		if (!empty($this->_action) && ($this->_action instanceof \Closure)) {
			$reflectClass = new \ReflectionClass($this->_class);
			$reflectParent = $reflectClass->getParentClass();
			if (!empty($reflectParent) && $reflectParent->hasMethod($this->_method)) {
				// ранкит глючит, если мокать метод в дочернем классе через коллбек
				throw new \Exception($this->_getErrorMessage(
					"can't mock inherited method " . $this->_method . ' as Closure'
				));
			}
		}
	}
}
