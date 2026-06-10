<?php


class shopSeoStorefrontDataCollector
{
	private $group_storefront_service;
	private $storefront_field_service;
	private $storefront_field_value_service;
	
	public function __construct(
		shopSeoGroupStorefrontService $group_storefront_service,
		shopSeoStorefrontFieldService $storefront_field_service,
		shopSeoStorefrontFieldsValuesService $storefront_field_value_service
	) {
		$this->group_storefront_service = $group_storefront_service;
		$this->storefront_field_service = $storefront_field_service;
		$this->storefront_field_value_service = $storefront_field_value_service;
	}
	
	public function collectStorefrontName($storefront, &$info)
	{
		$groups_storefronts = $this->getGroupsStorefronts($storefront);
		
		$collection = new shopSeoLayoutsCollection(array(
			'storefront_name',
		));
		
		foreach ($groups_storefronts as $group_storefront)
		{
			$collection->push(array(
				'storefront_name' => $group_storefront->getSettings()->storefront_name,
			), 1, "personal; group storefront: \"{$group_storefront->getName()}\"");
		}
		
		$info = $collection->getInfo();
		$result = $collection->getResult();
		
		return $result['storefront_name'];
	}
	
	public function collectFieldsValues($storefront, &$info)
	{
		$fields = $this->storefront_field_service->getFields();
		$fields_ids = array();
		
		foreach ($fields as $field)
		{
			$fields_ids[] = $field->getId();
		}
		
		$collection = new shopSeoLayoutsCollection($fields_ids);
		
		$groups_storefronts = $this->getGroupsStorefronts($storefront);
		
		foreach ($groups_storefronts as $group_storefront)
		{
			$fields_values = $this->storefront_field_value_service->getByGroupIdAndFields($group_storefront->getId(),
				$fields);
			$values = $fields_values->getValues();
			
			foreach ($fields_values->getFields() as $i => $field)
			{
				$collection->push(array(
					$field->getId() => $values[$i],
				), 1, "personal; group storefront: \"{$group_storefront->getName()}\"");
			}
		}
		
		$info = $collection->getInfo();
		
		$fields_values = $collection->getResult();
		$result = array();
		
		foreach ($fields as $field)
		{
			$result[$field->getId()] = array(
				'name' => $field->getName(),
				'value' => $fields_values[$field->getId()],
			);
		}
		
		return $result;
	}
	
	private function getGroupsStorefronts($storefront)
	{
		$groups_storefronts = $this->group_storefront_service->getByStorefront($storefront);
		
		foreach ($groups_storefronts as $group_storefront)
		{
			if (!$group_storefront->getSettings())
			{
				$this->group_storefront_service->loadSettings($group_storefront);
			}
		}
		
		return $groups_storefronts;
	}
}