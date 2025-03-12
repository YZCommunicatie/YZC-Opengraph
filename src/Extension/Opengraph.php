<?php
/*
 *  package: YZC - Open Graph plugin
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | YZCommunicatie
 *  license: GNU General Public License version 3 or later
 *  link: https://www.yzcommunicatie.nl
 */

namespace YZCommunicatie\Plugin\System\Opengraph\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\DispatcherInterface;
use YZCommunicatie\Plugin\System\Opengraph\Helper\OpengraphHelper;

final class Opengraph extends CMSPlugin
{
	protected $app;
	protected $db;
	protected $autoloadLanguage = true;
	protected $config;

	public function __construct(DispatcherInterface $dispatcher, array $config)
	{
		parent::__construct($dispatcher, $config);
		$this->config = json_decode($config['params']);
	}

	public function onBeforeCompileHead(): bool
	{
		$input    = $this->app->input;
		$option   = $input->get('option', '', 'cmd');
		$document = $this->app->getDocument();

		if (!$this->app->isClient('site'))
		{
			return true;
		}

		if ($input->get('format', '', 'cmd') === 'feed')
		{
			return true;
		}

		if ($option === 'com_finder')
		{
			return true;
		}

		// General MetaData
		$document->setMetaData('fb:pages', $this->config->fb_page_id, 'property');
		$document->setMetaData('fb:app_id', $this->config->fb_app_id, 'property');
		$document->setMetadata('og:locale', OpengraphHelper::getLocale(), 'property');
		$document->setMetaData('og:site_name', OpengraphHelper::getSitename(), 'property');
		$document->setMetadata('og:url', OpengraphHelper::getUrl(), 'property');
		$document->setMetaData('og:title', OpengraphHelper::getTitle(), 'property');
		$document->setMetadata('twitter:card', $this->config->twitter_type, 'name');
		if ($this->config->twitter_site)
		{
			$document->setMetadata('twitter:site', '@' . ltrim($this->config->twitter_site, '@'), 'name');
		}
		if ($this->config->twitter_author)
		{
			$document->setMetadata('twitter:creator', '@' . ltrim($this->config->twitter_author, '@'), 'name');
		}
		$document->setMetaData('twitter:title', OpengraphHelper::getTitle(), 'name');

		if (OpengraphHelper::getScope() === 'com_content.article')
		{
			$document->setMetaData('og:type', 'article', 'property');
			$document->setMetadata('article:author', OpengraphHelper::getAuthor(), 'property');
			$document->setMetadata('article:published_time', OpengraphHelper::getPublishedtime(), 'property');
			$document->setMetadata('article:expiration_time', OpengraphHelper::getExpirationTime(), 'property');
			$document->setMetadata('article:modified_time', OpengraphHelper::getModifiedtime(), 'property');
			$document->setMetadata('og:updated_time', OpengraphHelper::getModifiedtime(), 'property');
		}

		if (OpengraphHelper::getScope() === 'com_content.category')
		{
			$document->setMetaData('og:type', 'website', 'property');
		}

		if ($option === 'com_content')
		{
			$document->setMetaData('description', OpengraphHelper::getDescription(), 'property');
			$document->setMetaData('og:description', OpengraphHelper::getDescription(), 'property');
			$document->setMetaData('twitter:description', OpengraphHelper::getDescription(), 'name');
			if (OpengraphHelper::getImage($this->config))
			{
				$document->setMetadata('og:image', OpengraphHelper::getImage($this->config), 'property');
				$document->setMetadata('og:image:type ', OpengraphHelper::getImageInfo($this->config)['mime'], 'property');
				$document->setMetadata('og:image:width', OpengraphHelper::getImageInfo($this->config)['0'], 'property');
				$document->setMetadata('og:image:height', OpengraphHelper::getImageInfo($this->config)['1'], 'property');
				$document->setMetadata('og:image:alt', OpengraphHelper::getImageAlt($this->config), 'property');
				$document->setMetadata('twitter:image', OpengraphHelper::getImage($this->config), 'name');
			}
		}

		return true;
	}
}
