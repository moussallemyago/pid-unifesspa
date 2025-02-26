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

use Joomla\CMS\Component\ComponentHelper as JComponentHelper;
use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Layout\LayoutHelper as JLayoutHelper;
use RegularLabs\Library\Language as RL_Language;

class ReadMore extends Data
{
    public function get($key, $attributes)
    {
        $link = $this->getUrl();
        if ( ! $link)
        {
            return false;
        }

        // load the content language file
        RL_Language::load('com_content', JPATH_SITE);

        if ( ! empty($attributes->class))
        {
            return '<a class="' . trim($attributes->class) . '" href="' . $link . '">' . $this->getText($attributes) . '</a>';
        }

        $config = JComponentHelper::getParams('com_content');
        $config->set('access-view', true);

        $text = $this->getCustomText($attributes);
        if ($text)
        {
            $this->item->set('alternative_readmore', $text);
            $config->set('show_readmore_title', false);
        }

        $this->item->set(
            'alternative_readmore',
            $this->item->get(
                'alternative_readmore',
                $this->item->getFromGroup(
                    'attribs',
                    'alternative_readmore'
                )
            )
        );

        return JLayoutHelper::render('joomla.content.readmore',
            [
                'item'   => $this->item->get(),
                'params' => $config,
                'link'   => $link,
            ]
        );
    }

    protected function getUrl()
    {
        return (new Url($this->config, $this->item, $this->values))->getArticleUrl();
    }

    private function getCustomText($attributes)
    {
        if (empty($attributes->text))
        {
            return '';
        }

        $title = trim($attributes->text);
        $text  = JText::sprintf($title, $this->item->get('title'));

        return $text ?: $title;
    }

    private function getText($attributes)
    {
        $text = $this->getCustomText($attributes);
        if ($text)
        {
            return $text;
        }

        $config = JComponentHelper::getParams('com_content');

        $alternative_readmore = $this->item->get('alternative_readmore');

        switch (true)
        {
            case ( ! empty($alternative_readmore)) :
                $text = $alternative_readmore;
                break;
            case ( ! $config->get('show_readmore_title', 0)) :
                $text = JText::_('COM_CONTENT_READ_MORE_TITLE');
                break;
            default:
                $text = JText::_('COM_CONTENT_READ_MORE');
                break;
        }

        if ( ! $config->get('show_readmore_title', 0))
        {
            return $text;
        }

        return $text . JHtml::_('string.truncate', ($this->item->get('title')), $config->get('readmore_limit'));
    }

}
