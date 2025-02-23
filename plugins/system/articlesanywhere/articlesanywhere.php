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
use Joomla\CMS\Language\Text as JText;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\Extension as RL_Extension;
use RegularLabs\Library\Html as RL_Html;
use RegularLabs\Library\Protect as RL_Protect;
use RegularLabs\Library\SystemPlugin as RL_SystemPlugin;
use RegularLabs\Plugin\System\ArticlesAnywhere\Params;
use RegularLabs\Plugin\System\ArticlesAnywhere\Protect;
use RegularLabs\Plugin\System\ArticlesAnywhere\Replace;

// Do not instantiate plugin on install pages
// to prevent installation/update breaking because of potential breaking changes
$input = JFactory::getApplication()->input;
if (in_array($input->get('option'), ['com_installer', 'com_regularlabsmanager']) && $input->get('action') != '')
{
    return;
}

if ( ! is_file(__DIR__ . '/vendor/autoload.php'))
{
    return;
}

require_once __DIR__ . '/vendor/autoload.php';

if ( ! is_file(JPATH_LIBRARIES . '/regularlabs/autoload.php')
    || ! is_file(JPATH_LIBRARIES . '/regularlabs/src/SystemPlugin.php')
)
{
    JFactory::getLanguage()->load('plg_system_articlesanywhere', __DIR__);
    JFactory::getApplication()->enqueueMessage(
        JText::sprintf('AA_EXTENSION_CAN_NOT_FUNCTION', JText::_('ARTICLESANYWHERE'))
        . ' ' . JText::_('AA_REGULAR_LABS_LIBRARY_NOT_INSTALLED'),
        'error'
    );

    return;
}

require_once JPATH_LIBRARIES . '/regularlabs/autoload.php';

if ( ! RL_Document::isJoomlaVersion(3, 'ARTICLESANYWHERE'))
{
    RL_Extension::disable('articlesanywhere', 'plugin');

    RL_Document::adminError(
        JText::sprintf('RL_PLUGIN_HAS_BEEN_DISABLED', JText::_('ARTICLESANYWHERE'))
    );

    return;
}

if (true)
{
    class PlgSystemArticlesAnywhere extends RL_SystemPlugin
    {
        public $_lang_prefix           = 'AA';
        public $_has_tags              = true;
        public $_disable_on_components = true;
        public $_jversion              = 3;

        public function processArticle(&$string, $area = 'article', $context = '', $article = null, $page = 0)
        {
            if ( ! isset($article->id) && isset($article->slug))
            {
                $slug_parts = explode(':', $article->slug);
                $article_id = array_shift($slug_parts);

                if (is_numeric($article_id))
                {
                    $article->id = $article_id;
                }
            }

            Replace::replaceTags($string, $area, $context, $article);
        }


        protected function handleOnAfterDispatch()
        {
            if ( ! $buffer = RL_Document::getComponentBuffer())
            {
                return;
            }

            if ( ! Replace::replaceTags($buffer, 'component'))
            {
                return;
            }

            RL_Document::setComponentBuffer($buffer);
        }

        protected function changeFinalHtmlOutput(&$html)
        {
            if (RL_Document::isFeed())
            {
                Replace::replaceTags($html);

                return true;
            }

            $params = Params::get();

            // only do stuff in body
            [$pre, $body, $post] = RL_Html::getBody($html);

            if ($params->handle_html_head)
            {
                Replace::replaceTags($pre, 'head');
            }

            Replace::replaceTags($body, 'body');
            $html = $pre . $body . $post;

            return true;
        }

        protected function cleanFinalHtmlOutput(&$html)
        {
            RL_Protect::removeAreaTags($html, 'ARTA');

            $params = Params::get();

            Protect::unprotectTags($html);

            RL_Protect::removeFromHtmlTagContent($html, Params::getTags(true));
            RL_Protect::removeInlineComments($html, 'Articles Anywhere');

            if ( ! $params->place_comments)
            {
                RL_Protect::removeCommentTags($html, 'Articles Anywhere');
            }
        }
    }
}
