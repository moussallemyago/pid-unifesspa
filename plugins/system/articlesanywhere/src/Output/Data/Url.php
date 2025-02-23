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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\Output\Data;

defined('_JEXEC') or die;

use ContentHelperRoute;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Router\Route as JRoute;
use Joomla\CMS\Uri\Uri as JUri;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\HtmlTag as RL_HtmlTag;

class Url extends Data
{
    public function get($key, $attributes)
    {
        switch ($key)
        {

            case 'link':
                return $this->getArticleLink($attributes);

            case 'sefurl':
                return JRoute::_($this->getArticleUrl());

            default:
            case 'url':
            case 'nonsefurl':
                return $this->getArticleUrl();
        }
    }

    public function getArticleLink($attributes)
    {
        return $this->getLink($this->getArticleUrl(), $attributes);
    }

    public function getArticleUrl()
    {
        $url = $this->item->get('url');

        if ( ! is_null($url))
        {
            return $url;
        }

        $id = $this->item->getId();

        if ( ! $id)
        {
            return false;
        }

        if ( ! class_exists('ContentHelperRoute'))
        {
            require_once JPATH_SITE . '/components/com_content/helpers/route.php';
        }

        $this->item->set('url', ContentHelperRoute::getArticleRoute($id, $this->item->get('catid'), $this->item->get('language')));

        if ( ! $this->item->hasAccess())
        {
            $this->item->set('url', $this->getRestrictedUrl($this->item->get('url')));
        }

        return $this->item->get('url');
    }

    public function getCategoryLink($attributes)
    {
    }

    public function getCategoryUrl()
    {
    }

    public function getEditLink($attributes)
    {
    }

    public function getEditTag($attributes)
    {
    }

    public function getEditUrl()
    {
    }

    public function getLink($url, $attributes = [])
    {
        // Pass non-sef urls through router for feeds
        if (
            $url
            && strpos($url, 'index.php?') !== false
            && RL_Document::isFeed()
        )
        {
            $url = JRoute::_($url);
        }

        $url = $url ?: '#';

        $attributes = array_merge(
            ['href' => $url],
            (array) $attributes
        );

        return '<a ' . RL_HtmlTag::flattenAttributes($attributes) . '>';
    }

    protected function canEdit()
    {
    }

    protected function getCategoryId()
    {
    }

    protected function getRestrictedUrl($url)
    {
        $menu   = JFactory::getApplication()->getMenu();
        $active = $menu->getActive();
        $itemId = $active ? $active->id : 0;
        $link   = new JUri(JRoute::_('index.php?option=com_users&view=login&Itemid=' . $itemId, false));

        $link->setVar('return', base64_encode(JRoute::_($url, false)));

        return (string) $link;
    }
}
