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

use ArticlesAnywhereArticleView;
use JFolder;
use Joomla\CMS\Factory as JFactory;
use RegularLabs\Plugin\System\ArticlesAnywhere\Factory;
use RegularLabs\Plugin\System\ArticlesAnywhere\Params;

class Layout extends Data
{
    public function get($key, $attributes)
    {
        if (
            JFactory::getApplication()->input->get('option') == 'com_finder'
            && JFactory::getApplication()->input->get('format') == 'json'
        )
        {
            // Force simple layout for finder indexing, as the setParams causes errors
            $text = Factory::getOutput('Text', $this->config, $this->item, $this->values);

            return
                '<h2>' . $this->item->get('title') . '</h2>'
                . $text->get('text', $attributes);
        }

        $params = Params::get();

        if (isset($attributes->force_content_triggers))
        {
            $params->force_content_triggers = $attributes->force_content_triggers;
            unset($attributes->force_content_triggers);
        }

        [$template, $layout] = $this->getTemplateAndLayout($attributes);

        require_once dirname(__FILE__, 3) . '/Helpers/article_view.php';

        $view = new ArticlesAnywhereArticleView;

        $view->setParams($this->item->getId(), $template, $layout, $params);

        return $view->display();
    }

    private function getTemplateAndLayout($data)
    {
        if ( ! isset($data->template) && isset($data->layout) && strpos($data->layout, ':') !== false)
        {
            [$data->template, $data->layout] = explode(':', $data->layout);
        }

        $article_layout = $this->item->get('article_layout');

        $layout = ! empty($data->layout)
            ? $data->layout
            : (($article_layout ?? null) ?: 'default');

        $template = ! empty($data->template)
            ? $data->template
            : JFactory::getApplication()->getTemplate();

        if (strpos($layout, ':') !== false)
        {
            [$template, $layout] = explode(':', $layout);
        }

        jimport('joomla.filesystem.folder');

        // Layout is a template, so return default layout
        if (empty($data->template) && JFolder::exists(JPATH_THEMES . '/' . $layout))
        {
            return [$layout, 'default'];
        }

        // Value is not a template, so a layout
        return [$template, $layout];
    }
}
