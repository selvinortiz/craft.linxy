<?php
namespace Craft;

/**
 * Class Linxy_LinkGroupRecord
 *
 * @author	Selvin Ortiz <selvin@selv.in>
 * @package	Craft
 *
 * @property int					$id
 * @property string					$name
 * @property string					$handle
 * @property int					$fieldLayoutId
 * @property FieldLayoutModel		$fieldLayout
 * @property array|Linxy_LinkModel	$links
 */
class Linxy_LinkGroupRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'linxy_linkgroups';
	}

	protected function defineAttributes()
	{
		return array(
			'name'			=> array(AttributeType::Name,	'required' => true),
			'handle'		=> array(AttributeType::Handle,	'required' => true),
			'fieldLayoutId'	=> AttributeType::Number,
		);
	}

	public function defineRelations()
	{
		return array(
			'fieldLayout'	=> array(static::BELONGS_TO, 'FieldLayoutRecord', 'onDelete' => static::SET_NULL),
			'links'			=> array(static::HAS_MANY, 'Linxy_LinkRecord', 'shortUrlId'),
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('name'), 'unique' => true),
			array('columns' => array('handle'), 'unique' => true),
		);
	}

	public function scopes()
	{
		return array(
			'ordered' => array('order'	=> 'name'),
		);
	}
}
