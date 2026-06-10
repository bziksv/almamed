<?php

class shopSearchproDetectorRule
{
	protected $env;
	protected $rule;

	/**
	 * @param shopSearchProEnv $env
	 * @param array $rule
	 */
	public function __construct($env, $rule)
	{
		$this->env = $env;
		$this->rule = $rule;
	}

	/**
	 * @return shopSearchproEnv
	 */
	protected function getEnv()
	{
		return $this->env;
	}

	/**
	 * @return string
	 */
	public function getCondition()
	{
		return $this->rule['condition'];
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->rule['value'];
	}

	/**
	 * @return string
	 */
	public function getExtra()
	{
		return $this->rule['extra'];
	}

	/**
	 * @return string
	 */
	public function getActionType()
	{
		return $this->rule['action'];
	}

	/**
	 * @param array $data
	 * @return null|shopSearchproDetectorAction
	 */
	public function getAction($data)
	{
		$condition = $this->getCondition();

		if($condition === 'query') {
			if(!array_key_exists('query', $data)) {
				return null;
			}

			$checker = new shopSearchproDetectorQueryChecker($this, $this->getEnv(), $data['query']);

			return $checker->getAction();
		}

		return null;
	}
}