<?php

namespace Joomill\Plugin\System\Opengraph\Helper\;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Document\Document;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Table\Content;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

class Helper
{
    protected $app;
    protected $db;
    protected $autoloadLanguage = true;
    protected $config;

    public function getSitename()
    {
        return Factory::getConfig()->get('sitename');
    }

    public function getUrl()
    {
        return Uri::getInstance();
    }

    private function getTitle()
    {
        $title = '';

        $document = $this->app->getDocument();
        $title = $document->title;

        if ($this->getScope() === 'com_content.category') {
            $category = Categories::getInstance('Content')->get($this->app->input->get('id', 0, 'int'));
            if ($category->title) {
                $title = $category->title;
            }
        }

        if ($this->getScope() === 'com_content.article') {
            $article = $this->getArticle($this->app->input->get('id', 0, 'int'));
            $title = $article->title;
        }

        return $title;
    }

    private function getDescription()
    {
        $description = '';
        $descriptiontext = '';

        if ($this->getScope() === 'com_content.category') {
            $category = Categories::getInstance('Content')->get($this->app->input->get('id', 0, 'int'));
            if ($category->metadesc) {
                $descriptiontext = $category->metadesc;
            } else {
                $descriptiontext = $category->description;
            }
        }

        if ($this->getScope() === 'com_content.article') {
            $article = $this->getArticle($this->app->input->get('id', 0, 'int'));
            if ($article->metadesc) {
                $descriptiontext = $article->metadesc;
            } else {
                $descriptiontext = $article->introtext . ' ' . $article->fulltext;
            }
        }

        if ($descriptiontext) {
            $description = $this->truncate($descriptiontext, 156, true, '...');
        }

        return $description;
    }

    private function getPublishedtime()
    {
        $article = $this->getArticle($this->app->input->get('id', 0, 'int'));

        return $article->publish_up;
    }

    private function getExpirationTime()
    {
        $article = $this->getArticle($this->app->input->get('id', 0, 'int'));

        return $article->publish_down;
    }

    private function getModifiedtime()
    {
        $article = $this->getArticle($this->app->input->get('id', 0, 'int'));

        return $article->modified;
    }

    private function getArticle(int $id): Content
    {
        $article = new Content($this->db);
        $article->load($id);

        return $article;
    }

    private function getImage()
    {
        $image = '';

        if ($this->config->fallback_image)
        {
            $image = $this->config->fallback_image;
        }

        if ($this->getScope() === 'com_content.category') {
            $category = Categories::getInstance('Content')->get($this->app->input->get('id', 0, 'int'));
            if ($category->getParams()->get('image')) {
                $image = $category->getParams()->get('image');
            }
        }

        if ($this->getScope() === 'com_content.article') {
            $article = $this->getArticle($this->app->input->get('id', 0, 'int'));
            if (json_last_error() === 0) {
                $articleImages = json_decode($article->images, true);
            }

            if ($articleImages['image_fulltext']) {

                if (strpos($articleImages['image_fulltext'], '#') !== false) {
                    $image = substr($articleImages['image_fulltext'], 0, strpos($articleImages['image_fulltext'], '#'));
                } else {
                    $image = $articleImages['image_fulltext'];
                }
            }

            if ($articleImages['image_intro']) {
                if (strpos($articleImages['image_intro'], '#') !== false) {
                    $image = substr($articleImages['image_intro'], 0, strpos($articleImages['image_intro'], '#'));
                } else {
                    $image = $articleImages['image_intro'];
                }

            }
        }

        $image = preg_replace('~^([\w\-./\\\]+).*$~', '$1', $image);

        if (!$image)
        {
            return;
        }

        $url = empty(Uri::root()) ? '' : rtrim(Uri::base(), '/') . 'Opengraph.php/';
        $url .= $image;

        return $url;
    }
    private function getImageInfo()
    {
        if ($this->getImage()) {
            return getimagesize($this->getImage());
        } else {
            return false;
        }
    }

    private function getImageAlt()
    {
        // Use Article title when no Image ALT
        $alt = $this->getTitle();

        if ($this->config->fallback_image_alt) {
            $alt = $this->config->fallback_image_alt;
        }

        if ($this->getScope() === 'com_content.category') {
            $category = Categories::getInstance('Content')->get($this->app->input->get('id', 0, 'int'));
            if ($category->getParams()->get('image_alt')) {
                $alt = $category->getParams()->get('image_alt');
            }
        }

        if ($this->getScope() === 'com_content.article') {
            $article = $this->getArticle($this->app->input->get('id', 0, 'int'));
            if (json_last_error() === 0) {
                $articleImages = json_decode($article->images, true);
            }

            if ($articleImages['image_fulltext']) {
                $alt = $articleImages['image_fulltext_alt'];
            }

            if ($articleImages['image_intro']) {
                $alt = $articleImages['image_intro_alt'];
            }
        }

        return $alt;
    }

    private function getLocale()
    {
        $language = Factory::getLanguage()->getTag();
        $locale = str_replace('-', '_', $language);

        return $locale;
    }
    private function getAuthor()
    {
        $article = $this->getArticle($this->app->input->get('id', 0, 'int'));
        $user = Factory::getUser($article->created_by);
        $author = $user->name;

        return $author;
    }

    private function getScope()
    {
        $input = $this->app->input;
        $option = $input->get('option', '', 'cmd');
        $view = $input->get('view', '', 'cmd');
        $scope = $option . '.' . $view;

        return $scope;
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

                $result = opengraph . phpmb_substr($result, 0, $length) . $ellipsis;
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

                $result = opengraph . phpsubstr($result, 0, $length) . $ellipsis;
            }
        }

        return $result;
    }
}