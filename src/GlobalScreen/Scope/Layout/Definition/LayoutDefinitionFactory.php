<?php namespace ILIAS\GlobalScreen\Scope\Layout\Definition;

use ILIAS\UI\NotImplementedException;

/**
 * Class LayoutDefinitionFactory
 *
 * @package ILIAS\GlobalScreen\Scope\LayoutDefinition
 */
class LayoutDefinitionFactory {

	/**
	 * @var array
	 */
	private static $views = [];


	/**
	 * @return LayoutDefinition
	 */
	public function standardView(): LayoutDefinition {
		return $this->get(StandardLayoutDefinition::class);
	}


	/**
	 * @return LayoutDefinition
	 * @throws NotImplementedException
	 */
	public function printView(): LayoutDefinition {
		throw new NotImplementedException();
	}


	/**
	 * @param string $class_name
	 *
	 * @return mixed
	 */
	private function get(string $class_name) {
		if (!isset(self::$views[$class_name])) {
			self::$views[$class_name] = new $class_name();
		}

		return self::$views[$class_name];
	}
}