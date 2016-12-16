<?php
namespace Craft;

/**
 * Class LinxyService
 *
 * @author     Selvin Ortiz <selvin@selv.in>
 * @package    Craft
 */
class LinxyService extends BaseApplicationComponent
{
	/**
	 * @var array
	 */
	protected $groupsById = array();

	/**
	 * @var array
	 */
	protected $groupsByHandle = array();

	/**
	 * @var array
	 */
	protected $allGroupIds = array();

	/**
	 * @var bool
	 */
	protected $fetchedAllGroups = false;

	/**
	 * @var LinxyPlugin
	 */
	protected $plugin;

	public function init()
	{
		$this->plugin = craft()->plugins->getPlugin('linxy');

		parent::init();
	}

	/**
	 * Redirects to the long URL assigned to the short URL if one is found
	 *
	 * @param string $shortUrl
	 */
	public function redirectToLongUrl($shortUrl)
	{
		if ($shortUrl)
		{
			$link = $this->getLinkByShortUrl($shortUrl);

			if ($link && $link->shortUrl == $shortUrl && $link->getStatus() == 'Active')
			{
				craft()->request->redirect($link->longUrl);
				craft()->end();
			}
		}
	}

	/**
	 * Returns a group model if found in the database by id
	 *
	 * @param int $groupId
	 *
	 * @return Linxy_LinkGroupModel
	 */
	public function getGroupById($groupId)
	{
		if (!isset($this->groupsById[$groupId]))
		{
			$groupRecord = Linxy_LinkGroupRecord::model()->findById($groupId);

			if ($groupRecord)
			{
				$this->groupsById[$groupId] = Linxy_LinkGroupModel::populateModel($groupRecord);
			}
			else
			{
				$this->groupsById[$groupId] = null;
			}
		}

		return $this->groupsById[$groupId];
	}

	/**
	 * Returns a group model if found in the database by handle
	 *
	 * @param string $groupHandle
	 *
	 * @return Linxy_LinkModel
	 */
	public function getGroupByHandle($groupHandle)
	{
		if (!isset($this->groupsByHandle[$groupHandle]))
		{
			$attributes  = array('handle' => $groupHandle);
			$groupRecord = Linxy_LinkGroupRecord::model()->findByAttributes($attributes);

			if ($groupRecord)
			{
				$this->groupsByHandle[$groupHandle] = Linxy_LinkGroupModel::populateModel($groupRecord);
			}
			else
			{
				$this->groupsByHandle[$groupHandle] = null;
			}
		}

		return $this->groupsByHandle[$groupHandle];
	}

	/**
	 * @param Linxy_LinkGroupModel $group
	 *
	 * @throws Exception
	 * @throws \CDbException
	 * @throws \Exception
	 * @return bool
	 */
	public function saveGroup(Linxy_LinkGroupModel $group)
	{
		$oldGroup = null;

		if ($group->id)
		{
			$groupRecord = Linxy_LinkGroupRecord::model()->findById($group->id);

			if (!$groupRecord)
			{
				throw new Exception(Craft::t('There is not group with that id “{id}”', array('id' => $group->id)));
			}

			$oldGroup   = Linxy_LinkGroupModel::populateModel($groupRecord);
			$isNewGroup = false;
		}
		else
		{
			$groupRecord = new Linxy_LinkGroupRecord;
			$isNewGroup  = true;
		}

		$groupRecord->name   = $group->name;
		$groupRecord->handle = $group->handle;

		$groupRecord->validate();
		$group->addErrors($groupRecord->getErrors());

		if (!$group->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				if (!$isNewGroup && $oldGroup->fieldLayoutId)
				{
					craft()->fields->deleteLayoutById($oldGroup->fieldLayoutId);
				}

				$fieldLayout = $group->getFieldLayout();

				craft()->fields->saveLayout($fieldLayout);

				$group->fieldLayoutId       = $fieldLayout->id;
				$groupRecord->fieldLayoutId = $fieldLayout->id;

				$groupRecord->save(false);

				if (!$group->id)
				{
					$group->id = $groupRecord->id;
				}

				$this->groupsById[$group->id] = $group;

				if ($transaction !== null)
				{
					$transaction->commit();
				}
			}
			catch (\Exception $e)
			{
				if ($transaction !== null)
				{
					$transaction->rollback();
				}

				throw $e;
			}

			return true;
		}

		return false;
	}

	/**
	 * Removes a group and related links in the database if found by id
	 *
	 * @param int $groupId
	 *
	 * @return bool
	 */
	public function deleteGroup($groupId)
	{
		$group = $this->getGroupById($groupId);

		return ($group && $group->delete());
	}

	/**
	 * @return array
	 */
	public function getAllGroupIds()
	{
		if (array() === $this->allGroupIds)
		{
			if ($this->fetchedAllGroups)
			{
				$this->allGroupIds = array_keys($this->groupsById);
			}
			else
			{
				$this->allGroupIds = craft()->db->createCommand()
					->select('id')
					->from('linxy_linkgroups')
					->queryColumn();
			}
		}

		return $this->allGroupIds;
	}

	/**
	 * @param null|string $indexBy
	 *
	 * @return array
	 */
	public function getAllGroups($indexBy = null)
	{
		if (null === $indexBy)
		{
			$indexBy = 'id';
		}

		if (!$this->fetchedAllGroups)
		{
			$groupRecord            = Linxy_LinkGroupRecord::model()->ordered()->findAll();
			$this->groupsById       = Linxy_LinkGroupModel::populateModels($groupRecord, $indexBy);
			$this->fetchedAllGroups = true;
		}

		return $this->groupsById;
	}

	/**
	 * Returns a link model if one is found in the database by id
	 *
	 * @param int $linkId
	 *
	 * @return Linxy_LinkModel|null
	 */
	public function getLinkById($linkId)
	{
		return $this->getCriteria(array('limit' => 1, 'id' => $linkId))->first();
	}

	/**
	 * Returns a link model if one is found in the database by shortUrl
	 *
	 * @param string $shortUrl
	 *
	 * @throws Exception
	 * @return Linxy_LinkModel|null
	 */
	public function getLinkByShortUrl($shortUrl)
	{
		return $this->getCriteria(array('limit' => 1, 'shortUrl' => $shortUrl))->first();
	}

	/**
	 * @param Linxy_LinkModel $link
	 *
	 * @throws Exception
	 * @throws \CDbException
	 * @throws \Exception
	 * @return bool
	 */
	public function saveLink(Linxy_LinkModel &$link)
	{
		$isNewLink = !$link->id;

		if (!$isNewLink)
		{
			$linkRecord = Linxy_LinkRecord::model()->findById($link->id);

			if (!$linkRecord)
			{
				throw new Exception(Craft::t('There is not link with that id “{id}”', array('id' => $link->id)));
			}
		}
		else
		{
			$linkRecord = new Linxy_LinkRecord();
		}

		$linkRecord->groupId    = $link->groupId;
		$linkRecord->shortUrl   = $link->shortUrl;
		$linkRecord->longUrl    = $link->longUrl;
		$linkRecord->httpCode   = $link->httpCode;
		$linkRecord->activeDate = $link->activeDate;
		$linkRecord->expiryDate = $link->expiryDate;
		$linkRecord->status     = $link->status;

		if (!$link->activeDate)
		{
			$link->activeDate = DateTimeHelper::currentUTCDateTime();
		}

		$linkRecord->validate();
		$link->addErrors($linkRecord->getErrors());

		if (!$link->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				$this->onBeforeSaveEvent(
					new Event(
						$this,
						array(
							'link'      => $link,
							'isNewLink' => $isNewLink
						)
					)
				);

				if (craft()->elements->saveElement($link))
				{
					if ($isNewLink)
					{
						$linkRecord->id = $link->id;
					}

					$linkRecord->save(false);

					$this->onSaveEvent(
						new Event(
							$this,
							array(
								'link'      => $link,
								'isNewLink' => $isNewLink
							)
						)
					);

					if ($transaction !== null)
					{
						$transaction->commit();
					}

					return true;
				}
			}
			catch (\Exception $e)
			{
				if ($transaction !== null)
				{
					$transaction->rollback();
				}

				throw $e;
			}
		}

		return false;
	}

	/**
	 * Removes a link from the database if one is found by id
	 *
	 * @param int $linkId
	 *
	 * @return bool
	 */
	public function deleteLink($linkId)
	{
		$link = Linxy_LinkRecord::model()->findById($linkId);

		return ($link && $link->delete());
	}

	/**
	 * Raises a before save event for the link element
	 *
	 * @param Event $event
	 */
	public function onBeforeSaveEvent(Event $event)
	{
		$this->raiseEvent('onBeforeSaveEvent', $event);
	}

	/**
	 * Raises a save event for the link element
	 *
	 * @param Event $event
	 */
	public function onSaveEvent(Event $event)
	{
		$this->raiseEvent('onSaveEvent', $event);
	}

	/**
	 * @param array $attributes
	 *
	 * @throws Exception
	 * @return ElementCriteriaModel
	 */
	protected function getCriteria(array $attributes = array())
	{
		return craft()->elements->getCriteria('Linxy_Link', $attributes);
	}
}
