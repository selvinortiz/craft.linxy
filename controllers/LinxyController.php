<?php
namespace Craft;

/**
 * Class LinxyController
 *
 * @author     Selvin Ortiz <selvin@selv.in>
 * @package    Craft
 */
class LinxyController extends BaseController
{
	protected $allowAnonymous = array('actionRedirect');

	/**
	 * Finds a long URL associated with the short URL and redirects user to it
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 */
	public function actionRedirect(array $variables = array())
	{
		$link = isset($variables['shortUrl']) ? $variables['shortUrl'] : null;

		if ($link)
		{
			linxy()->redirectToLongUrl($link);
		}

		throw new HttpException(404);
	}

	/**
	 * Renders the link index template
	 *
	 * @throws HttpException
	 */
	public function actionLinkIndex()
	{
		$variables['groups'] = linxy()->getAllGroups();

		$this->renderTemplate('linxy/_index', $variables);
	}

	/**
	 * Renders the link edit template
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 */
	public function actionEditLink(array $variables = array())
	{
		if (!empty($variables['groupHandle']))
		{
			$variables['group'] = linxy()->getGroupByHandle($variables['groupHandle']);
		}
		else if (!empty($variables['groupId']))
		{
			$variables['group'] = linxy()->getGroupById($variables['groupId']);
		}

		if (empty($variables['group']))
		{
			throw new HttpException(404);
		}

		if (empty($variables['link']))
		{
			if (!empty($variables['linkId']))
			{
				$variables['link'] = linxy()->getLinkById($variables['linkId']);

				if (!$variables['link'])
				{
					throw new HttpException(404);
				}
			}
			else
			{
				$variables['link']          = new Linxy_LinkModel();
				$variables['link']->groupId = $variables['group']->id;
			}
		}

		$variables['tabs'] = array();

		foreach ($variables['group']->getFieldLayout()->getTabs() as $index => $tab)
		{
			// Do any of the fields on this tab have errors?
			$hasErrors = false;

			if ($variables['link']->hasErrors())
			{
				foreach ($tab->getFields() as $field)
				{
					if ($variables['link']->getErrors($field->getField()->handle))
					{
						$hasErrors = true;
						break;
					}
				}
			}

			$variables['tabs'][] = array(
				'label' => $tab->name,
				'url'   => '#tab'.($index + 1),
				'class' => ($hasErrors ? 'error' : null)
			);
		}

		if (!$variables['link']->id)
		{
			$variables['title'] = Craft::t('Create a new link');
		}
		else
		{
			$variables['title'] = $variables['link']->title;
		}

		$variables['crumbs'] = array(
			array('label' => Craft::t('Links'), 'url' => UrlHelper::getUrl('linxy')),
			array('label' => $variables['group']->name, 'url' => UrlHelper::getUrl('linxy'))
		);

		$variables['continueEditingUrl'] = 'linxy/'.$variables['group']->handle.'/{id}';

		$this->renderTemplate('linxy/_edit', $variables);
	}

	/**
	 * @throws Exception
	 * @throws HttpException
	 * @throws \Exception
	 */
	public function actionSaveLink()
	{
		$this->requirePostRequest();

		$linkId = craft()->request->getPost('linkId');

		if ($linkId)
		{
			$link = linxy()->getLinkById($linkId);

			if (!$link)
			{
				throw new Exception(Craft::t('No link exists with the ID “{id}”', array('id' => $linkId)));
			}
		}
		else
		{
			$link = new Linxy_LinkModel;
		}

		$link->groupId    = craft()->request->getPost('groupId', $link->groupId);
		$link->shortUrl   = craft()->request->getPost('shortUrl', $link->shortUrl);
		$link->longUrl    = craft()->request->getPost('longUrl', $link->longUrl);
		$link->httpCode   = craft()->request->getPost('httpCode', $link->httpCode);
		$link->activeDate = (($activeDate = craft()->request->getPost('activeDate')) ? DateTime::createFromString($activeDate, craft()->timezone) : null);
		$link->expiryDate = (($expiryDate = craft()->request->getPost('expiryDate')) ? DateTime::createFromString($expiryDate, craft()->timezone) : null);

		$link->setStatusBasedOnActiveAndExpiryDateTimes();

		/**
		 * Links do not have titles but we're saving the shortUrl as the title
		 * The title seems to be the required first value to display in the element view table
		 */
		$link->getContent()->title = craft()->request->getPost('title', $link->shortUrl);

		$link->setContentFromPost('fields');

		if (linxy()->saveLink($link))
		{
			craft()->userSession->setNotice(Craft::t('Link saved.'));
			$this->redirect('linxy');
		}
		else
		{
			craft()->userSession->setError(Craft::t('Could not save link.'));

			// Send the link back to the template
			craft()->urlManager->setRouteVariables(compact('link'));
		}
	}

	/**
	 * @throws HttpException
	 */
	public function actionDeleteLink()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		if (isset($_POST['id']) && linxy()->deleteLink($_POST['id']))
		{
			$this->returnJson('Link deleted successfully!');
		}
		else
		{
			$this->returnErrorJson('Unable to delete link.');
		}
	}

	/**
	 * Renders the group index template
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 */
	public function actionGroupIndex(array $variables = array())
	{
		$variables['groups'] = linxy()->getAllGroups();

		$this->renderTemplate('linxy/groups/_index', $variables);
	}

	/**
	 * Renders the group edit template
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 */
	public function actionEditGroup(array $variables = array())
	{
		$group   = null;
		$groupId = null;

		extract($variables);

		$variables['isNewGroup'] = false;

		if ($groupId)
		{
			if (!$group)
			{
				$group = linxy()->getGroupById($groupId);

				if ($group)
				{
					$variables['group'] = $group;
				}
				else
				{
					throw new HttpException(404);
				}
			}

			$variables['title'] = $group->name;
		}
		else
		{
			if (!$group)
			{
				$variables['group']      = new Linxy_LinkGroupModel;
				$variables['isNewGroup'] = true;
			}

			$variables['title'] = Craft::t('Create New Group');
		}

		$variables['crumbs'] = array(
			array('label' => Craft::t('Links'), 'url' => UrlHelper::getUrl('linxy')),
			array('label' => Craft::t('Groups'), 'url' => UrlHelper::getUrl('linxy/groups')),
		);

		$this->renderTemplate('linxy/groups/_edit', $variables);
	}

	/**
	 * @throws Exception
	 * @throws HttpException
	 * @throws \Exception
	 */
	public function actionSaveGroup()
	{
		$this->requirePostRequest();

		$group = new Linxy_LinkGroupModel();

		$group->id         = craft()->request->getPost('groupId');
		$group->name       = craft()->request->getPost('name');
		$group->handle     = craft()->request->getPost('handle');
		$fieldLayout       = craft()->fields->assembleLayoutFromPost();
		$fieldLayout->type = 'Linxy_Link';

		$group->setFieldLayout($fieldLayout);

		if (linxy()->saveGroup($group))
		{
			craft()->userSession->setNotice(Craft::t('Group saved.'));
			$this->redirectToPostedUrl($group);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Could not save group.'));
		}

		craft()->urlManager->setRouteVariables(array('group' => $group));
	}

	/**
	 * @throws HttpException
	 */
	public function actionDeleteGroup()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		if (linxy()->deleteGroup(craft()->request->getPost('id')))
		{
			$this->returnJson('Group deleted successfully!');
		}
		else
		{
			$this->returnErrorJson('Unable to delete Group.');
		}
	}
}
