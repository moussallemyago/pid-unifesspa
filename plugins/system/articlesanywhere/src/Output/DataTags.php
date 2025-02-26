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

use Joomla\CMS\Language\Text as JText;
use RegularLabs\Library\PluginTag as RL_PluginTag;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\CurrentArticle;
use RegularLabs\Plugin\System\ArticlesAnywhere\Params;
use RegularLabs\Plugin\System\ArticlesAnywhere\Protect;
use RegularLabs\Plugin\System\ArticlesAnywhere\Replace;

class DataTags extends OutputObject
{
    private array $replaced = [];

    public function handle(&$content)
    {
        [$data_tag_start, $data_tag_end] = Params::getDataTagCharacters();
        $spaces = RL_PluginTag::getRegexSpaces();

        $inside_tag = RL_PluginTag::getRegexInsideTag($data_tag_start, $data_tag_end);

        $regex_datatags = RL_RegEx::quote($data_tag_start)
            . '(?<type>/?[a-z][a-z0-9-_\:\+\/\*]*)(?:' . $spaces . '(?<attributes>' . $inside_tag . '))?'
            . RL_RegEx::quote($data_tag_end);

        RL_RegEx::matchAll($regex_datatags, $content, $matches);

        if (empty($matches))
        {
            return;
        }

        $tags = RL_RegEx::quote(Params::getTagNames(), 'tag');
        [$tag_start, $tag_end] = Params::getTagCharacters();

        $regex_plugintags = RL_RegEx::quote($tag_start) . $tags
            . '.*?'
            . RL_RegEx::quote($tag_start) . '/\1' . RL_RegEx::quote($tag_end);

        foreach ($matches as $match)
        {
            if (in_array($match[0], $this->replaced))
            {
                continue;
            }

            $value = $this->getValueFromTag($match);

            if (is_null($value))
            {
                continue;
            }

            Output::protectNestedTagContent($content);

            $content = $this->replaceMatch($match, $value, $content);

            Output::unprotectNestedTagContent($content);

            if (RL_RegEx::match($regex_plugintags, $content))
            {
                $current_article = CurrentArticle::get();
                $this_article    = $this->item->getArticle();

                // Remove Articles Anywhere tags when looping occurs
                if (
                    isset($current_article->id) && isset($this_article->id)
                    && $current_article->id == $this_article->id
                    && $this->containsTextTags($content)
                )
                {
                    $content = RL_RegEx::replace(
                        Params::getRegex(),
                        Protect::getMessageCommentTag('Content removed because of looping'),
                        $content
                    );
                }

                (new Output($this->config))->unprotectNestedTagContent($content);
                Replace::replaceTags($content, 'article', '', $this_article);

                // Set current article back to previous
                CurrentArticle::set($current_article);
            }
        }
    }

    private function containsTextTags($content = '')
    {
        // Content is empty, so it will output the layout
        if (empty($content))
        {
            return true;
        }

        [$data_tag_start, $data_tag_end] = Params::getDataTagCharacters();
        $spaces = RL_PluginTag::getRegexSpaces();

        $regex_texttags = RL_RegEx::quote($data_tag_start)
            . '(?:layout|text|introtext|fulltext)(?:' . $spaces . '.*?)?'
            . RL_RegEx::quote($data_tag_end);

        return RL_RegEx::match($regex_texttags, $content);
    }

    private function getTagValues($tag)
    {
        $type       = $tag['type'];
        $attributes = $tag['attributes'] ?? '';

        $attributes = $this->getTagValuesFromString($type, $attributes);

        $key_aliases = [
            'limit'                  => ['letters', 'letter_limit', 'characters', 'character_limit'],
            'words'                  => ['word', 'word_limit'],
            'strip'                  => ['trim'],
            'paragraphs'             => ['paragraph', 'paragraph_limit'],
            'class'                  => ['classes'],
            'force_content_triggers' => ['content_triggers', 'content_plugins', 'force_content_plugins'],
        ];

        RL_PluginTag::replaceKeyAliases($attributes, $key_aliases);

        return (object) compact('type', 'attributes');
    }

    private function getTagValuesFromString($type, $attributes)
    {
        if (empty($attributes))
        {
            return (object) [];
        }

        if ($type == 'article' && strpos($attributes, '=') === false)
        {
            $attributes = 'article layout="' . trim($attributes) . '"';
        }

        return RL_PluginTag::getAttributesFromString($attributes);
    }

    private function getValueFromTag($tag)
    {
        if (RL_RegEx::match('^(?<close>(?:\/)?)(?<type>previous|next):', $tag['type'], $prevnext))
        {
            $id = $this->numbers->get('has_' . $prevnext['type']) ? $this->numbers->get($prevnext['type']) : 0;

            if ( ! $id)
            {
                return '';
            }

            return str_replace(
                $prevnext[0],
                $id . ':' . $prevnext['close'],
                $tag['0']
            );
        }

        $tag = $this->getTagValues($tag);

        $encode = ! empty($tag->attributes->htmlentities);
        unset($tag->attributes->htmlentities);

        $value = $this->values->get($tag->type, null, $tag->attributes);

        if (is_bool($value))
        {
            $value = $value ? JText::_('JYES') : JText::_('JNO');
        }

        if ($encode)
        {
            $value = htmlentities($value);
        }

        return $value;
    }

    private function replaceMatch($match, $value, $content)
    {
        // Replace random-type data tags only once
        if (strpos($match['type'], 'random') !== false)
        {
            return RL_String::replaceOnce($match[0], $value, $content);
        }

        $this->replaced[] = $match[0];

        return str_replace($match[0], $value, $content);
    }
}
