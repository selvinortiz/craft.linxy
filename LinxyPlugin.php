<?php
namespace Craft;

/**
 * Linxy @v1.1.1
 *
 * Linxy allows you to manage your own short URL service within Craft
 *
 * @author         Selvin Ortiz <selvin@selv.in>
 * @package        Craft
 * @category       URL Shortening
 * @copyright      2014 Selvin Ortiz
 * @license        [MIT]
 */
class LinxyPlugin extends BasePlugin
{
	/**
	 * Returns the name of the plugin or its alias given by end user
	 *
	 * @param bool $real
	 *
	 * @return string
	 */
	public function getName($real = false)
	{
		$alias = $this->getSettings()->getAttribute('pluginAlias');

		return ($real || empty($alias)) ? 'Linxy' : $alias;
	}

	/**
	 * @return string
	 */
	public function getVersion()
	{
		return '1.1.1';
	}

	/**
	 * @return string
	 */
	public function getDeveloper()
	{
		return 'Selvin Ortiz';
	}

	/**
	 * @return string
	 */
	public function getDeveloperUrl()
	{
		return 'http://selv.in';
	}

	/**
	 * @return bool
	 */
	public function hasCpSection()
	{
		return $this->getSettings()->getAttribute('enableCpTab');
	}

	/**
	 * @return null|string
	 */
	public function getSettingsHtml()
	{
		craft()->templates->includeCssResource('linxy/css/linxy.css');

		$variables = array(
			'name'         => $this->getName(true),
			'alias'        => $this->getName(),
			'version'      => $this->getVersion(),
			'developer'    => $this->getDeveloper(),
			'developerUrl' => $this->getDeveloperUrl(),
			'settings'     => $this->getSettings(),
		);

		return craft()->templates->render('linxy/_settings', $variables);

	}

	/**
	 * @return array
	 */
	public function defineSettings()
	{
		return array(
			'redirectToken' => array(AttributeType::String, 'default' => '{linxy}'),
			'redirectRoute' => array(AttributeType::String, 'default' => '(?P<shortUrl>[a-zA-Z0-9-]+)'),
			'enableCpTab'   => array(AttributeType::Bool, 'default' => false),
			'pluginAlias'   => array(AttributeType::String, 'default' => 'Linxy'),
		);
	}

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	public function prepSettings($settings = array())
	{
		$routePattern  = '(?P<shortUrl>[a-zA-Z0-9-]+)';
		$redirectToken = isset($settings['redirectToken']) ? $settings['redirectToken'] : '{shortUrl}';

		try
		{
			$redirectToken             = str_replace('{linxy}', $routePattern, $redirectToken);
			$redirectToken             = str_replace('{shortUrl}', $routePattern, $redirectToken);
			$settings['redirectRoute'] = $redirectToken;
		}
		catch (\Exception $e)
		{
			$settings['redirectRoute'] = $routePattern;
		}

		return $settings;
	}

	/**
	 * @return array
	 */
	public function registerCpRoutes()
	{
		return array(
			'linxy'                                           => array('action' => 'linxy/linkIndex'),
			'linxy/groups'                                    => array('action' => 'linxy/groupIndex'),
			'linxy/groups/new'                                => array('action' => 'linxy/editGroup'),
			'linxy/groups/(?P<groupId>\d+)'                   => array('action' => 'linxy/editGroup'),
			'linxy/(?P<groupHandle>{handle})/new'             => array('action' => 'linxy/editLink'),
			'linxy/(?P<groupHandle>{handle})/(?P<linkId>\d+)' => array('action' => 'linxy/editLink'),
		);
	}

	public function registerSiteRoutes()
	{
		$route = $this->getSettings()->getAttribute('redirectRoute');

		return array(
			$route => array('action' => 'linxy/redirect'),
		);
	}

	public function registerUserPermissions()
	{
		return array(
			'linxyCanEditSettings' => array(
				'label' => Craft::t('Edit Settings')
			)
		);
	}

	public function onBeforeInstall()
	{
		parent::onBeforeInstall();
	}

	public function onAfterInstall()
	{
		parent::onAfterInstall();
	}

	public function onBeforeUninstall()
	{
		parent::onBeforeUninstall();
	}

	public function createTables()
	{
		parent::createTables();
	}

	public function dropTables()
	{
		parent::dropTables();
	}
}


/**
 * Run function existence check for linxy() and define it if appropriate
 */
if (function_exists('linxy'))
{
	throw new Exception(Craft::t('Linxy needs to define the linxy() function but it is already defined elsewhere.'));
}


/**
 * @return LinxyService
 */
function linxy()
{
	return craft()->linxy;
}
