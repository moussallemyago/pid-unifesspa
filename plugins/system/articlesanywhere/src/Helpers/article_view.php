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

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Helper\TagsHelper as JTagsHelper;
use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Layout\FileLayout as JLayoutFile;
use Joomla\CMS\Plugin\PluginHelper as JPluginHelper;
use Joomla\CMS\Router\Route as JRoute;

if ( ! class_exists('ContentViewArticle'))
{
    require_once JPATH_SITE . '/components/com_content/views/article/view.html.php';
}

class ArticlesAnywhereArticleView extends ContentViewArticle
{
    public function display($tpl = null)
    {
        $app  = JFactory::getApplication();
        $user = JFactory::getApplication()->getIdentity() ?: JFactory::getUser();

        $this->print = $app->input->getBool('print');
        $this->user  = $user;

        // Create a shortcut for $item.
        $item = $this->item;

        if (empty($item))
        {
            return false;
        }

        $item->tagLayout = new JLayoutFile('joomla.content.tags');

        // Add router helpers.
        $item->slug        = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
        $item->catslug     = $item->category_alias ? ($item->catid . ':' . $item->category_alias) : $item->catid;
        $item->parent_slug = $item->parent_alias ? ($item->parent_id . ':' . $item->parent_alias) : $item->parent_id;

        // No link for ROOT category
        if ($item->parent_alias == 'root')
        {
            $item->parent_slug = null;
        }

        // TODO: Change based on shownoauth
        if ( ! class_exists('ContentHelperRoute'))
        {
            require_once JPATH_SITE . '/components/com_content/helpers/route.php';
        }

        $item->readmore_link = JRoute::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language));

        // Merge article params. If this is single-article view, menu params override article params
        // Otherwise, article params override menu item params
        $this->params = $this->state->get('params');

        $item->text = $item->fulltext ?: $item->introtext;

        if ($item->params->get('show_intro', '1') == '1')
        {
            $item->text = $item->introtext . ' ' . $item->fulltext;
        }

        $item->text .= '<!-- AA:CT -->';

        $item->tags = new JTagsHelper;
        $item->tags->getItemTags('com_content.article', $this->item->id);

        $item->event                       = (object) [];
        $item->event->afterDisplayTitle    = '';
        $item->event->beforeDisplayContent = '';
        $item->event->afterDisplayContent  = '';

        if ($this->plugin_params->force_content_triggers)
        {
            // Process the content plugins.
            $dispatcher = JEventDispatcher::getInstance();
            JPluginHelper::importPlugin('content');

            $dispatcher->trigger('onContentPrepare', ['com_content.article', &$item, &$item->params, 0]);

            $results                        = $dispatcher->trigger('onContentAfterTitle', ['com_content.article', &$item, &$item->params, 0]);
            $item->event->afterDisplayTitle = trim(implode("\n", $results));

            $results                           = $dispatcher->trigger('onContentBeforeDisplay', ['com_content.article', &$item, &$item->params, 0]);
            $item->event->beforeDisplayContent = trim(implode("\n", $results));

            $results                          = $dispatcher->trigger('onContentAfterDisplay', ['com_content.article', &$item, &$item->params, 0]);
            $item->event->afterDisplayContent = trim(implode("\n", $results));
        }

        // Escape strings for HTML output
        $this->pageclass_sfx = htmlspecialchars($this->item->params->get('pageclass_sfx'));

        return $this->loadTemplate($tpl);
    }

    public function setParams($id, $template, $layout, $params)
    {
        require_once __DIR__ . '/article_model.php';

        $model = new ArticlesAnywhereArticleModel;

        $this->plugin_params = $params;

        $this->item  = $model->getItem($id);
        $this->state = $model->getState();

        $this->setLayout($template . ':' . $layout);

        $this->item->article_layout = $template . ':' . $layout;

        $this->_addPath('template', JPATH_SITE . '/components/com_content/views/article/tmpl');
        $this->_addPath('template', JPATH_SITE . '/templates/' . $template . '/html/com_content/article');

        JHTML::addIncludePath(JPATH_SITE . '/components/com_content/helpers');
        JHTML::addIncludePath(JPATH_SITE . '/templates/' . $template . '/html/com_content/helpers');
    }
}
