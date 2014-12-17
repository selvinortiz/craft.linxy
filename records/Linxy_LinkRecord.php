<?php
namespace Craft;

/**
 * Class Linxy_LinkRecord
 *
 * @author	Selvin Ortiz <selvin@selv.in>
 * @package	Craft
 *
 * @property string					$title
 * @property string					$shortUrl
 * @property string					$longUrl
 * @property int					$httpCode
 * @property int					$groupId
 * @property Linxy_LinkGroupModel	$group
 * @property DateTime				$activeDate
 * @property DateTime				$expiryDate
 * @property string					$status
 */
class Linxy_LinkRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'linxy_links';
	}

	protected function defineAttributes()
	{
		return array(
			'shortUrl'		=> array(AttributeType::Handle,		'required' => true),
			'longUrl'		=> array(AttributeType::Url,		'required' => true),
			'httpCode'		=> array(AttributeType::Number,		'required' => true),
			'activeDate'	=> array(AttributeType::DateTime,	'required' => false),
			'expiryDate'	=> array(AttributeType::DateTime,	'required' => false),
			'status'		=> array(AttributeType::Enum,		'required' => true, 'values' => array('Active', 'Inactive', 'Expired'))
		);
	}

	public function defineRelations()
	{
		return array(
			'element'	=> array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'group'		=> array(static::BELONGS_TO, 'Linxy_LinkGroupRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}
}
