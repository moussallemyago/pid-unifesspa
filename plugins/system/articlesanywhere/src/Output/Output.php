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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\Output;

defined('_JEXEC') or die;

use JEventDispatcher;
use Joomla\CMS\Plugin\PluginHelper as JPluginHelper;
use Joomla\Registry\Registry;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\Html as RL_Html;
use RegularLabs\Library\Protect as RL_Protect;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\Collection\Item;
use RegularLabs\Plugin\System\ArticlesAnywhere\Config;
use RegularLabs\Plugin\System\ArticlesAnywhere\CurrentArticle;
use RegularLabs\Plugin\System\ArticlesAnywhere\Factory;
use RegularLabs\Plugin\System\ArticlesAnywhere\Output\Data\Numbers;
use RegularLabs\Plugin\System\ArticlesAnywhere\Params;
use RegularLabs\Plugin\System\ArticlesAnywhere\PluginTags\PluginTag;
use RegularLabs\Plugin\System\ArticlesAnywhere\PluginTags\PluginTags;
use RegularLabs\Plugin\System\ArticlesAnywhere\Protect;

class Output
{
    protected $config;
    protected $content;

    protected $numbers;

    public function __construct(Config $config)
    {
        $this->config     = $config;
        $this->pagination = Factory::getPagination($config);
    }

    public static function protectIgnoreTags(&$string)
    {
        [$tag_start, $tag_end] = Params::getTagCharacters();

        if ( ! RL_String::contains($string, $tag_start . 'ignore' . $tag_end))
        {
            return;
        }

        $tag_start = RL_RegEx::quote($tag_start);
        $tag_end   = RL_RegEx::quote($tag_end);

        RL_Protect::protectByRegex(
            $string,
            $tag_start . 'ignore' . $tag_end . '.*?' . $tag_start . '/ignore' . $tag_end
        );
    }

    public static function protectNestedTagContent(&$string)
    {
        if (trim($string) == '')
        {
            return;
        }

        self::protectIgnoreTags($string);

        $pluginTags = new PluginTags;
        $tags       = $pluginTags->get($string);

        /** @var PluginTag $tag */
        foreach ($tags as $tag)
        {
            $content = RL_Protect::protectString($tag->getInnerContent());

            $full_tag = RL_String::replaceOnce(
                $tag->getInnerContent(),
                $content,
                $tag->getOriginalString()
            );

            $string = RL_String::replaceOnce($tag->getOriginalString(), $full_tag, $string);
        }
    }

    public static function unprotectNestedTagContent(&$string)
    {
        $pluginTags = new PluginTags;
        $tags       = $pluginTags->get($string);

        /** @var PluginTag $tag */
        foreach ($tags as $tag)
        {
            $content = $tag->getInnerContent();
            RL_Protect::unprotect($content);

            $full_tag = RL_String::replaceOnce(
                $tag->getInnerContent(),
                $content,
                $tag->getOriginalString()
            );

            $string = RL_String::replaceOnce($tag->getOriginalString(), $full_tag, $string);
        }

        self::removeIgnoreTags($string);
    }

    public function get($items, $total_no_limit, $total_no_pagination)
    {
        if (empty($items))
        {
            return '';
        }

        $this->numbers = new Numbers($total_no_limit, $total_no_pagination, count($items), $this->pagination);

        $html = [];

        $params = Params::get();

        /** @var Item $item */
        foreach ($items as $count => $item)
        {
            $this->numbers->setCount($count + 1)
                ->setCurrent($item->getId() == CurrentArticle::get('id', $this->config->getComponentName()));

            $item_output = $this->renderOutput($item);

            if ($item_output == '')
            {
                continue;
            }

            if ($params->force_content_triggers && strpos($item_output, '<!-- AA:CT -->') === false)
            {
                $item_output = $this->triggerContentPlugins($item_output, $item);
            }

            $html[] = $item_output;
        }

        $attributes = $this->config->getData('attributes');

        $separator      = '';
        $last_separator = null;

        $html = RL_Array::implode($html, $separator, $last_separator);


        RL_Protect::unprotect($html);
        self::removeIgnoreTags($html);

        $output = $this->pagination->render('top', $total_no_pagination)
            . $html
            . $this->pagination->render('bottom', $total_no_pagination);

        $output = str_replace('<!-- AA:CT -->', '', $output);

        $fix_html = $attributes->fixhtml ?? $params->fix_html_syntax;

        $surrounding_tags = $item->getConfigData('surrounding_tags');

        if (empty($output) || ! $fix_html)
        {
            return
                $surrounding_tags->opening
                . $output
                . $surrounding_tags->closing;
        }

        if (empty($surrounding_tags->opening) || empty($surrounding_tags->closing))
        {
            return
                $surrounding_tags->opening
                . self::fixBrokenHtmlTags($output)
                . $surrounding_tags->closing;
        }

        return self::fixBrokenHtmlTags(
            $surrounding_tags->opening
            . $output
            . $surrounding_tags->closing
        );
    }

    public function renderOutput(Item $item)
    {
        $content = $this->config->getContent();

        $this->protectNestedTagContent($content);

        // Default to full article layout if content is empty
        if ($content == '')
        {
            [$data_tag_start, $data_tag_end] = Params::getDataTagCharacters();
            $content = $data_tag_start . 'article' . $data_tag_end;
        }

        if (trim($content) == '')
        {
            return $content;
        }

        (new IfStructures($this->config, $item, $this->numbers))->handle($content);
        (new DataTags($this->config, $item, $this->numbers))->handle($content);

        $this->unprotectNestedTagContent($content);

        return $content;
    }

    private static function fixBrokenHtmlTags($string)
    {
        $params = Params::get();

        $string = RL_Html::fix($string);

        if ( ! $params->place_comments)
        {
            return $string;
        }

        return Protect::wrapInCommentTags($string);
    }

    private static function removeIgnoreTags(&$string)
    {
        [$tag_start, $tag_end] = Params::getTagCharacters();
        RL_Protect::removePluginTags($string, ['ignore'], $tag_start, $tag_end);
    }

    private function triggerContentPlugins($string, Item $item)
    {
        $item            = $item->get();
        $item->text      = $string;
        $item->slug      = '';
        $item->catslug   = '';
        $item->introtext = null;
        $item->fulltext  = null;

        $article_params = new Registry;
        $article_params->loadArray(['inline' => false]);

        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('content');

        $dispatcher->trigger('onContentPrepare', ['com_content.article', &$item, &$article_params, 0]);

        return $item->text;
    }
}
