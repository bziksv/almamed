<?php

/**
 * Class shopSeofilterActiveRecord
 * @property $id
 */
abstract class shopSeofilterActiveRecord
{
	const HAS_MANY = 1;
	const HAS_ONE = 2;
	const BELONGS_TO = 3;

	/** @var waModel[] */
	protected static $_model = array();

	protected static $_metadata = array();

	private static $_relations_config = array();

	/** @var array() */
	protected $_attributes = array();
	protected $_class;
	protected $_errors = array();
	protected $_db_error;

	protected $_relations = array();
	protected $_save_relations = array();
	protected $_relation_errors = array();

	protected $_primary_key_field = 'id';
	protected $_primary_key;

	/** @var bool */
	protected $_is_new_record = true;

	public function __construct($attributes = null)
	{
		$this->_class = get_class($this);
		if (!isset(self::$_model[$this->_class]))
		{
			self::$_model[$this->_class] = $this->createModel();
		}
		$model = self::$_model[$this->_class];

		$this->_primary_key_field = $model->getTableId();

		if (!isset(self::$_metadata[$this->_class]))
		{
			self::$_metadata[$this->_class] = $model->getMetadata();
		}

		if (!isset(self::$_relations_config[$this->_class]))
		{
			self::$_relations_config[$this->_class] = $this->relations();
		}

		if ($attributes !== null)
		{
			$this->setAttributes($attributes);
		}
	}

	/**
	 * @return array
	 *
	 * element of array:
	 * 'relation_name' => array(self::RELATION_TYPE, 'relatedClassName', 'foreignKey')
	 * relatedClassName must be instance of shopSeofilterActiveRecord
	 */
	public function relations()
	{
		return array();
	}

	public function getAttributes()
	{
		return $this->_attributes;
	}

	/**
	 * @param array()|shopSeofilterActiveRecord $attributes
	 */
	public function setAttributes($attributes)
	{
		if ($attributes instanceof shopSeofilterActiveRecord)
		{
			$attributes = $attributes->getAttributes();
		}
		foreach ($attributes as $field => $attribute)
		{
			try
			{
				$this->$field = $attribute;
			}
			catch (waException $e)
			{}
		}
	}

	public function getIsNewRecord()
	{
		return $this->_is_new_record;
	}

	/**
	 * @param bool $is_new_record
	 */
	public function setIsNewRecord($is_new_record)
	{
		$this->_is_new_record = !!$is_new_record;
	}

	public function save()
	{
		$success = false;

		if ($this->beforeSave() && $this->validate())
		{
			$success = $this->saveRecord();
		}

		$this->afterSave($success);

		return $success;
	}

	private function saveRecord()
	{
		$model = $this->model();
		$attributes = $this->tableOnlyAttributes();

		if ($attributes[$this->_primary_key_field] < 0)
		{
			$attributes[$this->_primary_key_field] = null;
		}

		try
		{
			if ($this->_is_new_record)
			{
				$pk = $model->insert($attributes);
				$success = wa_is_int($pk) && $pk > 0;

				if ($success)
				{
					$this->{$this->_primary_key_field} = $pk;
				}
			}
			else
			{
				$success = $model->updateById($this->primaryKey(), $attributes);
			}
		}
		catch (waException $e)
		{
			$this->_db_error = $e->getMessage();
			return false;
		}

		if (!$success)
		{
			return false;
		}

		$this->setIsNewRecord(false);

		return $this->savePreparedRelatedObjects();
	}

	private function savePreparedRelatedObjects()
	{
		$success = true;
		$to_unset = array();
		foreach ($this->_save_relations as $name => $objects)
		{
			if (!$this->deleteAllForRelation($name))
			{
				$success = false;
				break;
			}

			$related_save_success = true;
			/** @var shopSeofilterActiveRecord[] $arr */
			$arr = is_array($objects)
				? $objects
				: array($objects);

			$relation_config = self::$_relations_config[$this->_class][$name];
			$foreign_key = $relation_config[2];

			foreach ($arr as $obj)
			{
				$obj->{$foreign_key} = $this->primaryKey();
				$obj->setIsNewRecord(true);
				$obj_id = $obj->primaryKey();

				if (!$obj->save())
				{
					$related_save_success = false;
					$success = false;

					if (is_array($objects))
					{
						if (!isset($this->_errors[$name]))
						{
							$this->_errors[$name] = array();
						}

						if ($obj_id !== null)
						{
							$this->_errors[$name][$obj_id] = $obj->errors();
						}
					}
					else
					{
						$this->_errors[$name] = $obj->errors();
					}
				}
			}

			if ($related_save_success)
			{
				$to_unset[] = $name;
				$this->_relations[$name] = $objects;
			}
		}
		unset($objects);

		foreach ($to_unset as $n)
		{
			unset($this->_save_relations[$n]);
		}

		return $success;
	}

	/**
	 * @param int|int[] $id
	 * @return null|shopSeofilterActiveRecord|shopSeofilterActiveRecord[]
	 */
	public function getById($id)
	{
		$model = $this->model();
		$attributes = $model->getById($id);

		if (!$attributes)
		{
			return null;
		}

		if (is_array($id))
		{
			$objects = array();
			foreach ($attributes as $_attributes)
			{
				/** @var shopSeofilterActiveRecord $object */
				$object = new $this($_attributes);
				$object->setIsNewRecord(false);

				$objects[] = $object;
			}

			return $objects;
		}
		else
		{
			/** @var shopSeofilterActiveRecord $object */
			$object = new $this;
			$object->setAttributes($attributes);
			$object->setIsNewRecord(false);

			return $object;
		}
	}

	public function getAll()
	{
		$model = $this->model();

		$objects = array();

		foreach ($model->getAll() as $attributes)
		{
			/** @var shopSeofilterActiveRecord $object */
			$object = new $this;
			$object->setAttributes($attributes);
			$object->setIsNewRecord(false);

			$objects[] = $object;
		}

		return $objects;
	}

	/**
	 * @param array $fields ['field1' => 'value1', ...]
	 * @return shopSeofilterActiveRecord[]
	 */
	public function getAllByFields($fields)
	{
		$model = $this->model();
		$attributes_list = $model->getByField($fields, true);

		$objects = array();

		foreach ($attributes_list as $attributes)
		{
			/** @var shopSeofilterActiveRecord $object */
			$object = new $this;
			$object->setAttributes($attributes);
			$object->setIsNewRecord(false);

			$objects[] = $object;
		}

		return $objects;
	}

	/**
	 * @param int|int[] $ids
	 * @param $field
	 * @param $value
	 * @return bool
	 */
	public function updateFieldById($ids, $field, $value)
	{
		if (!is_array($ids))
		{
			$ids = array($ids);
		}

		$updates = array(
			$field => $value,
		);

		return $this->model()->updateByField($this->model()->getTableId(), $ids, $updates);
	}

	/**
	 * @param int|int[] $ids
	 * @param bool $with_related
	 * @return bool
	 */
	public function deleteById($ids, $with_related = true)
	{
		if (!$with_related)
		{
			return $this->model()->deleteById($ids);
		}


		$success = true;
		$ids = is_array($ids)
			? $ids
			: array($ids);

		$objects = $this->getById($ids);
		foreach ($objects as $object)
		{
			$success = $object->delete() && $success;
		}

		return $success;
	}

	public function countDistinct($fields)
	{
		if (!is_array($fields))
		{
			$fields = array($fields);
		}

		foreach ($fields as $i => $field)
		{
			$fields[$i] = '`' . ($this->model()->escape($field)) . '`';
		}

		$select = 'COUNT(DISTINCT ' . implode(', ', $fields) . ')';

		$count = (int) $this
			->model()
			->select($select)
			->fetchField();

		return $count;
	}

	public function getDistinct($fields)
	{
		if (!is_array($fields))
		{
			$fields = array($fields);
		}

		foreach ($fields as $i => $field)
		{
			$fields[$i] = '`' . ($this->model()->escape($field)) . '`';
		}
		$select = 'DISTINCT ' . implode(', ', $fields);

		return $this
			->model()
			->select($select)
			->order(array_shift($fields))
			->fetchAll();
	}

	public function countAll()
	{
		$count = (int)$this->model()->countAll();

		return $count;
	}

	function __unset($name)
	{
		$method_name = 'unset' . ucfirst($name);
		if (is_callable(array($this, $method_name) ))
		{
			call_user_func(array($this, $method_name));
		}
		elseif (array_key_exists($name, $this->_attributes))
		{
			unset($this->_attributes[$name]);
		}
		elseif ($this->relationExists($name))
		{
			unset($this->_save_relations[$name]);
		}
	}

	/**
	 * @param string $name
	 * @return mixed calls method get{$name}() or returns getAttributes()[$name]
	 * @throws waException
	 */
	function __get($name)
	{
		$method_name = 'get' . ucfirst($name);
		if (is_callable( array($this, $method_name) ))
		{
			return call_user_func(array($this, $method_name));
		}
		elseif (array_key_exists($name, $this->_attributes))
		{
			return $this->_attributes[$name];
		}
		elseif (isset(self::$_metadata[$this->_class][$name]))
		{
			$meta = self::$_metadata[$this->_class][$name];
			return isset($meta['default']) ? $meta['default'] : null;
		}
		elseif ($this->relationExists($name))
		{
			return $this->resolveRelation($name);
		}
		else
		{
			throw new waException("Cant [get] value of attribute [{$name}]: method [{$method_name}()] or key [{$name}] in attributes array doesn't exists");
		}
	}

	function __set($name, $value)
	{
		$method_name = 'set' . ucfirst($name);
		if (is_callable( array($this, $method_name) ))
		{
			call_user_func(array($this, $method_name), $value);
		}
		elseif (isset(self::$_metadata[$this->_class][$name]))
		{
			$this->_attributes[$name] = $value;
		}
		elseif ($this->relationExists($name))
		{
			$this->setRelationForSave($name, $value);
		}
		else
		{
			throw new waException("Cant [set] value of attribute [{$name}]: method [{$method_name}(\$value)] or key [{$name}] in attributes array doesn't exists");
		}

		if ($name == $this->_primary_key_field)
		{
			$this->_primary_key = $value;
		}
	}

	public final function primaryKey()
	{
		return $this->_primary_key;
	}

	public final function primaryKeyField()
	{
		return $this->_primary_key_field;
	}

	function __isset($name)
	{
		try
		{
			$val = $this->$name;
			return isset($val);
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	function __clone()
	{
		$this->id = null;
		$this->_is_new_record = true;

		$relations_clone = array();
		foreach ($this->_relations as $relation => $objects)
		{
			if (is_array($objects))
			{
				$relations_clone[$relation] = array();

				foreach ($objects as $object)
				{
					$relations_clone[$relation][] = clone $object;
				}
				unset($object);
			}
			else
			{
				$relations_clone[$relation] = clone $objects;
			}
		}

		$this->_relations = $relations_clone;


		$relations_clone = array();
		foreach ($this->_save_relations as $relation => $objects)
		{
			if (is_array($objects))
			{
				$relations_clone[$relation] = array();

				foreach ($objects as $object)
				{
					$relations_clone[$relation][] = clone $object;
				}
				unset($object);
			}
			else
			{
				$relations_clone[$relation] = clone $objects;
			}
		}
		unset($objects);

		$this->_save_relations = $relations_clone;
	}

	/**
	 * @return waModel
	 */
	public function model()
	{
		return self::$_model[$this->_class];
	}

	public function tableName()
	{
		return $this->model()->getTableName();
	}

	public function refresh()
	{
		if ($this->_is_new_record)
		{
			return false;
		}

		$row = $this->model()->getById($this->id);
		if ($row)
		{
			$this->setAttributes($row);
			return true;
		}

		return false;

		//$this->refreshRelations();
	}

	public function refreshRelations()
	{}

	public function delete($with_related = true)
	{
		$success = true;

		if ($with_related)
		{
			$success = $this->deleteRelated();
		}

		if ($success)
		{
			$success = $success && $this->model()->deleteById($this->primaryKey());

			if ($success)
			{
				$this->{$this->_primary_key_field} = null;
				$this->setIsNewRecord(true);
			}
		}

		return $success;
	}

	public function deleteRelated()
	{
		$success = true;

		foreach (self::$_relations_config[$this->_class] as $relation => $config)
		{
			$success = $this->deleteAllForRelation($relation) && $success;
		}

		return $success;
	}

	private function deleteAllForRelation($relation)
	{
		$success = true;

		$relation_config = self::$_relations_config[$this->_class][$relation];

		$relation_type = isset($relation_config[0]) ? $relation_config[0] : null;
		$relation_class = isset($relation_config[1]) ? $relation_config[1] : null;

		if (!class_exists($relation_class))
		{
			throw new waException("relation [{$relation}] error for [{$this->_class}]: relation class [{$relation_class}] doesn't exists");
		}

		if ($relation_type === self::HAS_MANY || $relation_type === self::HAS_ONE)
		{
			$relation_object = $this->fetchRelation($relation);
			if (is_array($relation_object))
			{
				/** @var shopSeofilterActiveRecord $obj */
				foreach ($relation_object as $obj)
				{
					if (!$obj->delete())
					{
						$success = false;

						if (!isset($this->_errors[$relation]))
						{
							$this->_errors[$relation] = array();
						}

						$this->_errors[$relation][$obj->primaryKey()] = $obj->errors();
					}
				}
			}
			elseif ($relation_object instanceof shopSeofilterActiveRecord)
			{
				if (!$relation_object->delete())
				{
					$success = false;
					$this->_errors[$relation] = $relation_object->errors();
				}
			}
		}

		unset($this->_relations[$relation]);

		return $success;
	}

	public function normalizeAttributes()
	{
		$this->setAttributes($this->tableOnlyAttributes());
	}

	public function attributesLength()
	{
		return count(self::$_metadata[$this->_class]);
	}

	/**
	 * @return true
	 */
	public function validate()
	{
		$valid = true;

		foreach (self::$_metadata[$this->_class] as $field => $params)
		{
			//if ($params['null'] == 0 && $this->$field === null)
			//{
			//	$this->_errors[$field] = 'Не может быть NULL';
			//	$valid = false;
			//	continue;
			//}

			if (empty($this->$field))
			{
				continue;
			}

			$value = $this->$field;
			switch ($params['type'])
			{
				case 'char':
				case 'varchar':
					if (strlen($value) > $params['params'])
					{
						$this->_errors[$field] = "Максимум {$params['params']} символов";
						$valid = false;
					}
					break;
				case 'tinyint':
				case 'smallint':
				case 'mediumint':
				case 'bigint':
				case 'int':
					if (!wa_is_int($value))
					{
						$this->_errors[$field] = "Должно быть целым";
						$valid = false;
					}
					break;
				case 'float':
				case 'double':
					if (!is_numeric($value))
					{
						$this->_errors[$field] = "Должно быть числом";
						$valid = false;
					}
					break;
				case 'enum':
					$values = array_map(array($this, 'trimQuotas'), explode(',', $params['params']));

					if (
						(!wa_is_int($value) || ($value < 1 || $value > count($values)))
						&&
						!in_array(strtoupper($value), $values)
					)
					{
						$this->_errors[$field] = 'Значение должно быть одно из перечисленных: ' . $params['params'];
						$valid = false;
					}

					break;
			}
		}

		foreach ($this->_save_relations as $name => $objects)
		{
			/** @var shopSeofilterActiveRecord[] $arr */
			$arr = is_array($objects)
				? $objects
				: array($objects);

			foreach ($arr as $obj)
			{
				if (!$obj->validate())
				{
					$valid = false;

					if (is_array($objects))
					{
						if (!isset($this->_errors[$name]))
						{
							$this->_errors[$name] = array();
						}

						$obj_id = $obj->primaryKey();
						if ($obj_id !== null)
						{
							$this->_errors[$name][$obj_id] = $obj->errors();
						}
					}
					else
					{
						$this->_errors[$name] = $obj->errors();
					}
				}
			}
		}
		unset($objects);

		return $valid;
	}

	private function trimQuotas($s)
	{
		return strtoupper(trim($s, '"\''));
	}

	public function key()
	{
		return $this->_class . '_' . $this->id;
	}

	/**
	 * @return array()
	 */
	public function errors()
	{
		return $this->_errors;
	}

	/**
	 * @param array $clone_attributes
	 * @return null|shopSeofilterActiveRecord
	 * @throws waException
	 */
	public function cloneRecord($clone_attributes = array())
	{
		/** @var shopSeofilterActiveRecord $clone */
		$clone = new $this;
		$clone->setAttributes($this->getAttributes());
		$clone->{$this->_primary_key_field} = null;

		foreach ($clone_attributes as $field => $value)
		{
			$clone->{$field} = $value;
		}

		if (!$clone->save())
		{
			return null;
		}

		foreach (self::$_relations_config[$this->_class] as $relation => $config)
		{
			$relation_type = isset($config[0]) ? $config[0] : null;
			$relation_class = isset($config[1]) ? $config[1] : null;
			$foreign_key = isset($config[2]) ? $config[2] : null;
			$relation_options = isset($config[3]) ? $config[3] : null;


			if ($relation_type === null)
			{
				throw new waException("relation [{$relation}] configuration error for [{$this->_class}]: specify relation type");
			}

			if ($relation_type === self::HAS_ONE || $relation_type === self::HAS_MANY)
			{
				$related_objects = $this->resolveRelation($relation);

				if ($related_objects === null)
				{
					continue;
				}

				if (!is_array($related_objects))
				{
					$related_objects = array($related_objects);
				}

				foreach ($related_objects as $related_object)
				{
					$related_object->cloneRecord(
						array(
							$foreign_key => $clone->primaryKey(),
						)
					);
				}
			}
		}

		return $clone;
	}

	/**
	 * @return bool
	 */
	protected function beforeSave()
	{
		return true;
	}

	/**
	 * @param bool $save_is_succeeded
	 */
	protected function afterSave($save_is_succeeded)
	{}

	protected function createModel()
	{
		$model_class = $this->_class . 'Model';

		if (!class_exists($model_class))
		{
			throw new waException("Model class [{$model_class}] doesn't exists. Probably, you should override method createModel() in class [{$this->_class}]");
		}

		return new $model_class;
	}

	protected function tableOnlyAttributes()
	{
		$table_only = array();

		foreach (array_keys(self::$_metadata[$this->_class]) as $field)
		{
			$table_only[$field] = $this->validateField($field);
		}

		return $table_only;
	}

	protected function validateField($field)
	{
		return $this->$field;
	}

	private function relationExists($name)
	{
		return isset(self::$_relations_config[$this->_class][$name]);
	}

	/**
	 * @param $name
	 * @return null|self|self[]
	 */
	private function resolveRelation($name)
	{
		if (isset($this->_save_relations[$name]))
		{
			return $this->_save_relations[$name];
		}

		if (!isset($this->_relations[$name]))
		{
			$this->_relations[$name] = $this->fetchRelation($name);
		}

		return $this->_relations[$name];
	}

	private function fetchRelation($name)
	{
		$relation_config = self::$_relations_config[$this->_class][$name];

		$relation_type = isset($relation_config[0]) ? $relation_config[0] : null;
		$relation_class = isset($relation_config[1]) ? $relation_config[1] : null;
		$foreign_key = isset($relation_config[2]) ? $relation_config[2] : null;
		$relation_options = isset($relation_config[3]) ? $relation_config[3] : null;

		if ($relation_type === null)
		{
			throw new waException("relation [{$name}] configuration error for [{$this->_class}]: specify relation type");
		}
		if ($relation_class === null)
		{
			throw new waException("relation [{$name}] configuration error for [{$this->_class}]: specify relation class");
		}
		if ($foreign_key === null)
		{
			throw new waException("relation [{$name}] configuration error for [{$this->_class}]: specify foreign key");
		}

		if (!class_exists($relation_class))
		{
			throw new waException("relation [{$name}] configuration error for [{$this->_class}]: relation class [{$relation_class}] doesn't exists");
		}

		/** @var shopSeofilterActiveRecord $object */
		$object = new $relation_class;

		switch ($relation_type)
		{
			case self::HAS_MANY:
				$result = $object->getAllByFields(array(
					$foreign_key => $this->primaryKey(),
				));

				return $result;

			case self::HAS_ONE:
				$result = $object->getAllByFields(array(
					$foreign_key => $this->primaryKey(),
				));
				if (count($result) > 1)
				{
					throw new waException("relation [{$name}] fetching error for [{$this->_class}] with id [{$this->primaryKey()}]: more than one related objects (HAS_ONE relation type)");
				}
				$obj = reset($result);

				return $obj === false ? null : $obj;

			case self::BELONGS_TO:
				$result = $object->getAllByFields(array(
					$object->_primary_key_field => $this->{$foreign_key},
				));
				$obj = reset($result);

				return $obj === false ? null : $obj;

			default:
				throw new waException("relation [{$name}] error for [{$this->_class}]: unknown relation type [{$relation_type}]");
		}
	}

	/**
	 * @param $name
	 * @param $value
	 * @throws waException
	 */
	private function setRelationForSave($name, $value)
	{
		$relation_config = self::$_relations_config[$this->_class][$name];

		/** @var shopSeofilterActiveRecord $class */
		$class = new $relation_config[1];
		$type = $relation_config[0];

		switch ($type)
		{
			case self::HAS_ONE:
				if (is_array($value))
				{
					throw new waException("Cant [set] value of relation [{$name}]: value must be an object for relation of type [HAS_ONE]");
				}

				elseif (!($value instanceof $class))
				{
					throw new waException("Cant [set] value of relation [{$name}]: value type must be instance or array of [{$class->_class}]");
				}

				break;
			case self::HAS_MANY:
				if (!is_array($value))
				{
					throw new waException("Cant [set] value of relation [{$name}]: value must be an array for relation of type [HAS_MANY]");
				}

				foreach ($value as $v)
				{
					if (!($v instanceof $class))
					{
						throw new waException("Cant [set] value of relation [{$name}]: value type must be instance or array of [{$class->_class}]");
					}
				}

				break;
			default:
				throw new waException("Cant [set] value of relation [{$name}]: invalid relation type (must be HAS_ONE or HAS_MANY)");
		}

		$this->_save_relations[$name] = $value;
	}
}