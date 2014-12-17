<?php
namespace Craft;

/**
 * Class Linxy_LinkElementType
 *
 * @author    Selvin Ortiz <selvin@selv.in>
 * @package   Craft
 */
class Linxy_LinkElementType extends BaseElementType
{
	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Linxy Link');
	}

	/**
	 * Returns whether this element type has content.
	 *
	 * @return bool
	 */
	public function hasContent()
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function hasTitles()
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function hasStatuses()
	{
		return true;
	}

	/**
	 * Returns all of the possible statuses that elements of this type may have
	 *
	 * @return array|null
	 */
	public function getStatuses()
	{
		return array(
			'Active'   => Craft::t('Active'),
			'Inactive' => Craft::t('Inactive'),
			'Expired'  => Craft::t('Expired'),
		);
	}

	/**
	 * Returns the element query condition for a custom status criteria.
	 *
	 * @param DbCommand $query
	 * @param string    $status
	 *
	 * @return array|false
	 */


	/**
	 * Returns this element type sources
	 *
	 * @param string|null $context
	 *
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		$sources = array(
			'*' => array(
				'label' => Craft::t('All'),
			)
		);

		foreach (linxy()->getAllGroups() as $group)
		{
			$key = 'group:'.$group->id;

			$sources[$key] = array(
				'label'    => $group->name,
				'criteria' => array('groupId' => $group->id)
			);
		}

		return $sources;
	}

	/**
	 * Returns the attributes that can be shown/sorted in table views
	 *
	 * @gotcha
	 * The first column in the index table is whatever your model returns from __toString()
	 *
	 * @param string|null $source
	 *
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		return array(
			'shortUrl'   => Craft::t('Short URL'),
			'longUrl'    => Craft::t('Long URL'),
			'httpCode'   => Craft::t('Redirect Type'),
			'activeDate' => Craft::t('Active Date'),
			'expiryDate' => Craft::t('Expiry Date'),
		);
	}

	/**
	 * Returns the table view HTML for a given attribute.
	 *
	 * @param BaseElementModel $element
	 * @param string           $attribute
	 *
	 * @return string
	 */
	public function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
		switch ($attribute)
		{
			case 'activeDate':
			case 'expiryDate':
			{
				if ($attribute == 'expiryDate' && $element->expiryDate == $element->activeDate)
				{
					return false;
				}

				$date = $element->$attribute;

				if ($date)
				{
					return $date->localeDate();
				}
				else
				{
					return '';
				}
			}
			case 'httpCode':
			{
				return $element->httpCode;
			}
			case 'longUrl':
			{
				$url = $element->longUrl;

				return sprintf('<a href="%s" title="%s" target="_blank">%s</a>', $url, $url, $url);
			}
			default:
			{
				return parent::getTableAttributeHtml($element, $attribute);
			}
		}
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'id'         => AttributeType::Number,
			'status'     => AttributeType::String,
			'group'      => AttributeType::Mixed,
			'groupId'    => AttributeType::Mixed,
			'title'      => AttributeType::String,
			'shortUrl'   => AttributeType::String,
			'longUrl'    => AttributeType::String,
			'httpCode'   => AttributeType::Number,
			'activeDate' => AttributeType::Mixed,
			'expiryDate' => AttributeType::Mixed,
		);
	}

	public function defineSearchableAttributes()
	{
		return array('shortUrl', 'longUrl');
	}

	/**
	 * Modifies an element query targeting elements of this type.
	 *
	 * @param DbCommand            $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('links.id, links.groupId, links.shortUrl, links.longUrl, links.httpCode, links.status, links.activeDate, links.expiryDate')
			->join('linxy_links links', 'links.id = elements.id');

		if ($criteria->id)
		{
			$query->andWhere(DbHelper::parseParam('links.id', $criteria->id, $query->params));
		}

		if ($criteria->groupId)
		{
			$query->andWhere(DbHelper::parseParam('links.groupId', $criteria->groupId, $query->params));
		}

		if ($criteria->group)
		{
			$query->join('linxy_linkgroups groups', 'groups.id = links.groupId');
			$query->andWhere(DbHelper::parseParam('groups.handle', $criteria->group, $query->params));
		}

		if ($criteria->activeDate)
		{
			$query->andWhere(DbHelper::parseDateParam('links.activeDate', $criteria->activeDate, $query->params));
		}

		if ($criteria->expiryDate)
		{
			$query->andWhere(DbHelper::parseDateParam('links.expiryDate', $criteria->expiryDate, $query->params));
		}

		if ($criteria->status)
		{
			$query->andWhere(DbHelper::parseDateParam('links.status', $criteria->status, $query->params));
		}
	}

	/**
	 * Returns the HTML for an editor HUD for the given element
	 *
	 * @param BaseElementModel $element
	 *
	 * @return string
	 */
	public function getEditorHtml(BaseElementModel $element)
	{
		$html = craft()->templates->render('linxy/_edit', compact('element'));

		$html .= parent::getEditorHtml($element);

		return $html;
	}

	/**
	 * Populates an element model based on a query result addressing status on populate
	 *
	 * @param array $row
	 *
	 * @return array
	 */
	public function populateElementModel($row)
	{
		$now   = DateTimeHelper::currentTimeForDb();
		$model = Linxy_LinkModel::populateModel($row);

		if ($model->status == 'Enabled' && $model->expiryDate && $model->expiryDate < $now)
		{
			$model->status = 'Expired';
		}

		if ($model->status == 'Enabled' && $model->activeDate > $now)
		{
			$model->status = 'Inactive';
		}

		return $model;
	}

}
