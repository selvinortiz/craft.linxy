<?php
namespace Craft;

/**
 * Class Linxy_LinkGroupModel
 *
 * @author	Selvin Ortiz <selvin@selv.in>
 * @package	Craft
 *
 * @property int	$id
 * @property string	$name
 * @property string	$handle
 * @property int	$fieldLayoutId
 */
class Linxy_LinkGroupModel extends BaseModel
{
	/**
	 * @return string
	 */
	function __toString()
	{
		return Craft::t($this->name);
	}

	protected function defineAttributes()
	{
		return array(
			'id'            => AttributeType::Number,
			'name'          => AttributeType::String,
			'handle'        => AttributeType::Handle,
			'fieldLayoutId' => AttributeType::Number,
		);
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'fieldLayout' => new FieldLayoutBehavior('Linxy_Link'),
		);
	}
}
