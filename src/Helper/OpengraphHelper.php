<?php
/*
 *  package: YZC - Open Graph plugin
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | YZCommunicatie
 *  license: GNU General Public License version 3 or later
 *  link: https://www.yzcommunicatie.nl
 */

namespace YZCommunicatie\Plugin\System\Opengraph\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Menu\Menu;
use Joomla\CMS\Table\Content;
use Joomla\CMS\Table\Category;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use function mb_substr;
use function phpsubstr;

class OpengraphHelper
{
	public static function getSitename()
	{
		return Factory::getApplication()->getConfig()->get('sitename');
	}

	public static function getUrl()
	{
		return Uri::getInstance();
	}

	public static function getDescription()
	{
		$description = '';
		$descText    = '';

		$menuItemId = Factory::getApplication()->input->get('Itemid', 0, 'int');
		$menu       = Factory::getApplication()->getMenu();
		$menuItem   = $menu->getItem($menuItemId);

		if ($menuItem)
		{
			$params   = $menuItem->getParams();
			$descText = $params->get('menu-meta_description');
		}

		if (self::getScope() === 'com_content.category')
		{
			$category = self::getCategory(Factory::getApplication()->input->get('id', 0, 'int'));
			if ($category->metadesc)
			{
				$descText = $category->metadesc;
			}
			elseif (!isset($descText))
			{
				$descText = $category->description;
			}
		}

		if (self::getScope() === 'com_content.article')
		{
			$article = self::getArticle(Factory::getApplication()->input->get('id', 0, 'int'));
			if ($article->metadesc)
			{
				$descText = $article->metadesc;
			}
			elseif (!isset($descText))
			{
				$descText = $article->introtext . ' ' . $article->fulltext;
			}
		}

		if ($descText)
		{
			$description = self::truncate($descText, 156, true, '...');
		}

		return $description;
	}

	public static function getScope()
	{
		$input  = Factory::getApplication()->input;
		$option = $input->get('option', '', 'cmd');
		$view   = $input->get('view', '', 'cmd');
		$scope  = $option . '.' . $view;

		return $scope;
	}

	public static function getArticle(int $id): Content
	{
		$db      = Factory::getContainer()->get(DatabaseInterface::class);
		$article = new Content($db);
		$article->load($id);

		return $article;
	}

	public static function getCategory(int $id): Category
	{
		$db      = Factory::getContainer()->get(DatabaseInterface::class);
		$article = new Category($db);
		$article->load($id);

		return $article;
	}

	public static function truncate($str, $length = 0, $strip = false, $ellipsis = '...')
	{
		$result = $str;

		if ($strip)
		{
			// {tag}text{/tag}
			$result = preg_replace('#{(.*?)}(.*?){\/(.*?)}#s', '', $result);

			// {tag}
			$result = preg_replace('#{(.*?)}#s', '', $result);

			// <script>...</script>
			$result = preg_replace('#<script\b[^>]*>(.*?)<\/script>#is', '', $result);

			// [widgetkit: xyz]
			$result = preg_replace('#\[(.*?)\]#s', '', $result);

			$result = strip_tags($result);
			$result = preg_replace('#\r|\n|\t|&nbsp;#', ' ', $result);
			$result = preg_replace('#(  )#', ' ', $result);
			$result = trim($result);
		}

		if (extension_loaded('mbstring'))
		{
			if (mb_strlen($result) > $length && $length !== 0)
			{
				if ($length > mb_strlen($ellipsis))
				{
					$length = $length - mb_strlen($ellipsis);
				}

				$result = mb_substr($result, 0, $length) . $ellipsis;
			}
		}
		else
		{
			if (strlen($result) > $length && $length !== 0)
			{
				if ($length > strlen($ellipsis))
				{
					$length = $length - strlen($ellipsis);
				}

				$result = phpsubstr($result, 0, $length) . $ellipsis;
			}
		}

		return $result;
	}

	public static function getPublishedtime()
	{
		$article = self::getArticle(Factory::getApplication()->input->get('id', 0, 'int'));

		return $article->publish_up;
	}

	public static function getExpirationTime()
	{
		$article = self::getArticle(Factory::getApplication()->input->get('id', 0, 'int'));

		return $article->publish_down;
	}

	public static function getModifiedtime()
	{
		$article = self::getArticle(Factory::getApplication()->input->get('id', 0, 'int'));

		return $article->modified;
	}

	public static function getImageInfo($config)
	{
		if (self::getImage($config))
		{
			return getimagesize(self::getImage($config));
		}
		else
		{
			return false;
		}
	}

	public static function getImage($config)
	{
		$image = '';

		if ($config->fallback_image)
		{
			$image = $config->fallback_image;
		}

		if (self::getScope() === 'com_content.category')
		{
			$category = self::getCategory(Factory::getApplication()->input->get('id', 0, 'int'));
			if ($category->params)
			{
				$params = json_decode($category->params);
				if ($params && !empty($params->image)) {
					if (strpos($params->image, '#') !== false) {
						$image = substr($params->image, 0, strpos($params->image, '#'));
					} else {
						$image = $params->image;
					}
				}
			}
		}

		if (self::getScope() === 'com_content.article')
		{
			$article = self::getArticle(Factory::getApplication()->input->get('id', 0, 'int'));
			if (json_last_error() === 0)
			{
				$articleImages = json_decode($article->images, true);
			}

			if (isset($articleImages['image_fulltext']))
			{

				if (isset($articleImages['image_fulltext']) && strpos($articleImages['image_fulltext'], '#') !== false)
				{
					$image = substr($articleImages['image_fulltext'], 0, strpos($articleImages['image_fulltext'], '#'));
				}
				else
				{
					$image = $articleImages['image_fulltext'];
				}
			}

			if (isset($articleImages['image_intro']))
			{
				if (isset($articleImages['image_intro']) && strpos($articleImages['image_intro'], '#') !== false)
				{
					$image = substr($articleImages['image_intro'], 0, strpos($articleImages['image_intro'], '#'));
				}
				else
				{
					$image = $articleImages['image_intro'];
				}
			}
		}

		$image = preg_replace('~^([\w\-./\\\]+).*$~', '$1', $image);

		if (!$image)
		{
			return;
		}

		$url = empty(Uri::root()) ? '' : rtrim(Uri::base(), '/') . '/';
		$url .= $image;

		return $url;
	}

	public static function getImageAlt($config)
	{
		// Use Article title when no Image ALT
		$alt = self::getTitle();

		if ($config->fallback_image_alt)
		{
			$alt = $config->fallback_image_alt;
		}

		if (self::getScope() === 'com_content.category')
		{
			$category = self::getCategory(Factory::getApplication()->input->get('id', 0, 'int'));
			if ($category->params)
			{
				$params = json_decode($category->params);
				if ($params && !empty($params->image_alt)) {
					$alt = $params->image_alt;
				}
			}
		}

		if (self::getScope() === 'com_content.article')
		{
			$article = self::getArticle(Factory::getApplication()->input->get('id', 0, 'int'));
			if (json_last_error() === 0)
			{
				$articleImages = json_decode($article->images, true);
			}

			if (isset($articleImages['image_fulltext']))
			{
				$alt = $articleImages['image_fulltext_alt'];
			}

			if (isset($articleImages['image_intro']))
			{
				$alt = $articleImages['image_intro_alt'];
			}
		}

		return $alt;
	}

	public static function getTitle()
	{
		$title = '';

		$document = Factory::getApplication()->getDocument();
		$title    = $document->title;

		if (self::getScope() === 'com_content.category')
		{
			$category = self::getCategory(Factory::getApplication()->input->get('id', 0, 'int'));
			if ($category->title)
			{
				$title = $category->title;
			}
		}

		if (self::getScope() === 'com_content.article')
		{
			$article = self::getArticle(Factory::getApplication()->input->get('id', 0, 'int'));
			$title   = $article->title;
		}

		return $title;
	}

	public static function getLocale()
	{
		$language = Factory::getApplication()->getLanguage()->getTag();
		$locale   = str_replace('-', '_', $language);

		return $locale;
	}

	public static function getAuthor()
	{
		$article = self::getArticle(Factory::getApplication()->input->get('id', 0, 'int'));
		$user    = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($article->created_by);
		$author  = $user->name;

		return $author;
	}
}