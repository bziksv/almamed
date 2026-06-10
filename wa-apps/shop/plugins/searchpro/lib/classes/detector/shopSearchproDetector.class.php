<?php

class shopSearchproDetector
{
	protected $env;
	protected $initial_rules;
	protected $rules;

	/**
	 * @param shopSearchProEnv $env
	 * @param $rules
	 */

	public function __construct(shopSearchProEnv $env, $rules)
	{
		$this->env = $env;
		$this->initial_rules = $rules;
	}

	/**
	 * @return shopSearchproEnv
	 */
	protected function getEnv()
	{
		return $this->env;
	}

	/**
	 * @return shopSearchproDetectorRule[]
	 */
	protected function getRules()
	{
		if(!isset($this->rules)) {
			$this->rules = array();
			$env = $this->getEnv();

			foreach($this->initial_rules as $rule) {
				$this->rules[] = new shopSearchproDetectorRule($env, $rule);
			}
		}

		return $this->rules;
	}

	/**
	 * @param array $data
	 * @return shopSearchproDetectorAction|null
	 */
	public function getAction($data)
	{
		foreach($this->getRules() as $rule) {
			$action = $rule->getAction($data);

			if($action) {
				return $action;
			}
		}

		return null;
	}
}