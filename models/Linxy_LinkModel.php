<?php
namespace Craft;

/**
 * Class Linxy_LinkModel
 *
 * @author     Selvin Ortiz <selvin@selv.in>
 * @package    Craft
 *
 * @property int           $id
 * @property string        $title
 * @property string        $shortUrl
 * @property string        $longUrl
 * @property int           $httpCode
 * @property int           $groupId
 * @property DateTime      $activeDate
 * @property DateTime      $expiryDate
 * @property string        $status
 */
class Linxy_LinkModel extends BaseElementModel
{
	protected $elementType = 'Linxy_Link';

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->shortUrl;
	}

	/**
	 * @return bool
	 */
	public function isEditable()
	{
		return true;
	}

	/**
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	public function defineAttributes()
	{
		$attributes = array(
			'title'      => array(AttributeType::Handle, 'required' => true),
			'shortUrl'   => array(AttributeType::Handle, 'required' => true),
			'longUrl'    => array(AttributeType::Url, 'required' => true),
			'httpCode'   => array(AttributeType::Number, 'required' => true),
			'groupId'    => array(AttributeType::Number, 'required' => true),
			'activeDate' => array(AttributeType::DateTime, 'required' => false),
			'expiryDate' => array(AttributeType::DateTime, 'required' => false),
			'status'     => array(AttributeType::String, 'required' => true),
		);

		return array_merge(parent::defineAttributes(), $attributes);
	}

	/**
	 * @return false|string
	 */
	public function getCpEditUrl()
	{
		$group = $this->getGroup();

		if ($group)
		{
			return UrlHelper::getCpUrl('linxy/'.$group->handle.'/'.$this->id);
		}
		else
		{
			LinxyPlugin::log(Craft::t('No CP edit URL was found.'));
		}

		return false;
	}

	/**
	 * @return FieldLayoutModel|null
	 */
	public function getFieldLayout()
	{
		$group = $this->getGroup();

		if ($group)
		{
			return $group->getFieldLayout();
		}
	}

	public function setStatusBasedOnActiveAndExpiryDateTimes()
	{
		$now    = DateTimeHelper::currentUTCDateTime();
		$status = 'Inactive';

		if ($now > $this->activeDate)
		{
			$status = 'Active';
		}

		if ($this->expiryDate && $now > $this->expiryDate)
		{
			$status = 'Expired';
		}

		$this->status = $status;
	}

	/**
	 * @return Linxy_LinkGroupModel
	 */
	protected function getGroup()
	{
		if ($this->groupId)
		{
			return linxy()->getGroupById($this->groupId);
		}
	}

	/**
	 * @param array $row
	 *
	 * @return Linxy_LinkModel
	 */
	public static function populateModel($row)
	{
		return parent::populateModel($row);
	}
}
