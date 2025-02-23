<?php
/**
 * @package         Articles Anywhere
 * @version         14.2.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2023 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

namespace RegularLabs\Plugin\System\ArticlesAnywhere;

defined('_JEXEC') or die;

use JEventDispatcher;
use Joomla\CMS\Helper\TagsHelper as JTagsHelper;
use Joomla\CMS\Plugin\PluginHelper as JPluginHelper;
use PlgSystemFields;

class CurrentArticle
{
    static $article = null;

    public static function get($key = null, $component = 'default')
    {
        $article = self::getCurrentArticle($component);

        if (is_null($key))
        {
            return $article ?: (object) [];
        }

        if (is_null($article))
        {
            return null;
        }

        if ($key == 'id' && ! isset($article->id))
        {
            return null;
        }

        if (isset($article->{$key}))
        {
            return $article->{$key};
        }

        if (isset($article->params) && $article->params->get($key))
        {
            return $article->params->get($key);
        }

        if ( ! isset($article->jcfields))
        {
            self::setCustomFields($article);
        }

        if (empty($article->jcfields) || ! is_array($article->jcfields))
        {
            return null;
        }

        foreach ($article->jcfields as $field)
        {
            if ($field->name == $key)
            {
                return $field->rawvalue ?? $field->value;
            }
        }

        return null;
    }

    public static function getTagIds($id = null)
    {
        $tags = self::getTags($id);

        if (empty($tags))
        {
            return [];
        }

        return array_map(fn($tag) => $tag->id, $tags);
    }

    public static function getTags($id = null)
    {
        $id = $id ?: self::get('id');

        if (empty($id))
        {
            return [];
        }

        $tags = new JTagsHelper;
        $tags->getItemTags('com_content.article', $id);

        return $tags->itemTags;
    }

    public static function set($article)
    {
        if ( ! isset($article->id))
        {
            return;
        }

        self::$article = $article;
    }

    public static function setCustomFields(&$article)
    {
        if ( ! JPluginHelper::importPlugin('system', 'fields'))
        {
            return;
        }

        $dispatcher = JEventDispatcher::getInstance();
        $params     = (array) JPluginHelper::getPlugin('system', 'fields');
        $plugin     = new PlgSystemFields($dispatcher, $params);
        $plugin->onContentPrepare('com_content.article', $article);
    }

    private static function getCurrentArticle($component = 'default')
    {
        if ( ! is_null(self::$article))
        {
            return self::$article;
        }

        self::set(Factory::getCurrentItem($component)->get());

        return self::$article;
    }

}
