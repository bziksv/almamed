<?php

abstract class shopSearchproDetectorChecker
{
	protected $rule;
	protected $env;
	protected $data;

	/**
	 * @param shopSearchproDetectorRule $rule
	 * @param shopSearchproEnv $env
	 * @param $data
	 */
	final public function __construct(shopSearchproDetectorRule $rule, shopSearchproEnv $env, $data)
	{
		$this->rule = $rule;
		$this->env = $env;
		$this->data = $data;
	}

	/**
	 * @return shopSearchproDetectorRule
	 */
	final protected function getRule()
	{
		return $this->rule;
	}

	/**
	 * @return shopSearchproEnv
	 */
	final protected function getEnv()
	{
		return $this->env;
	}

	/**
	 * @return null|shopSearchproDetectorAction
	 */
	abstract public function getAction();

	/**
	 * @param string $action
	 * @param mixed $params`
	 * @throws waException
	 * @return shopSearchproDetectorAction
	 */
	final protected function createAction($action, array $params = array())
	{
		$class_name = sprintf('shopSearchproDetector%sAction', ucfirst($action));

		if(!class_exists($class_name)) {
			throw new waException("Не найден класс \"{$class_name}\"");
		}

		$params['type'] = $this->getRule()->getActionType();

		$class = new $class_name($params);

		return $class;
	}
}