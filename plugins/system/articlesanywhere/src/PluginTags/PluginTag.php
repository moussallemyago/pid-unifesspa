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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\PluginTags;

defined('_JEXEC') or die;

use RegularLabs\Library\Html as RL_Html;
use RegularLabs\Library\PluginTag as RL_PluginTag;
use RegularLabs\Library\Protect as RL_Protect;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\Factory;
use RegularLabs\Plugin\System\ArticlesAnywhere\Output\Values;
use RegularLabs\Plugin\System\ArticlesAnywhere\Params;

class PluginTag
{
    private $match_data;

    public function __construct($match, $ignores = null, $filters = null)
    {
        $this->match_data = $match;
    }

    public function get()
    {
        return $this;
    }

    public function getInnerContent()
    {
        return $this->match_data['content'];
    }

    public function getOriginalString()
    {
        return $this->match_data[0];
    }

    public function getOutput()
    {
        $sets = self::getSets();

        if (empty($sets))
        {
            return '';
        }

        $ids = [];

        foreach ($sets as $set)
        {
            $ids = array_merge($ids, $this->getIdsBySet($set));
        }

        $config = Factory::getConfig($set);

        $default = $set->attributes->empty ?? '';

        return Factory::getCollection($config)->getOutputByIds($ids, $default);
    }

    public function getTagType()
    {
        return $this->match_data['tag'];
    }

    private function convertOldToNewSyntax($string, $tag_type)
    {
        RL_PluginTag::protectSpecialChars($string);

        if (strpos($string, '|') == false && strpos($string, ':') == false)
        {
            RL_PluginTag::unprotectSpecialChars($string);

            return $string;
        }

        RL_PluginTag::protectSpecialChars($string);

        $sets = explode('|', $string);

        $article_tag = Params::get()->article_tag;

        foreach ($sets as &$set)
        {
            if (strpos($set, ':') == false)
            {
                continue;
            }

            $parts = explode(':', $set);

            $id         = array_pop($parts);
            $attributes = [];
            $id_name    = 'id';

            foreach ($parts as $part)
            {
                switch (true)
                {

                    case ($tag_type == $article_tag):
                        $id = $part . ':' . $id;
                        break;

                    default:
                        $attributes[] = 'ordering="' . trim($part) . '"';
                        break;
                }
            }

            array_unshift($attributes, $id_name . '="' . $id . '"');

            $set = implode(' ', $attributes);
        }

        $string = implode(' && ', $sets);

        return $string;
    }

//    private function getOutputBySet($set)
//    {
//        $config = Factory::getConfig($set);
//
//        $default = $set->attributes->empty ?? '';
//
//        return Factory::getCollection($config)->get($default);
//    }

    private function getIdsBySet($set)
    {
        $config = Factory::getConfig($set);

        return Factory::getCollection($config)->getOnlyIds();
    }

    private function getSet($attributes)
    {
        $set = $this->initSet($attributes);

        $this->setLimits($set, $attributes);

        $config = Factory::getConfig($set);

        $fields        = Factory::getFields('Fields', $config);
        $custom_fields = Factory::getFields('CustomFields', $config);

        $set->filters  = (new Filters($set->component, $this, $fields, $custom_fields))
            ->get($attributes);
        $set->ignores  = (new Ignores($set->component))
            ->get($attributes);
        $set->ordering = (new Ordering($config, $custom_fields))
            ->get($attributes);
        $set->selects  = (new Selects($set->component, $fields, $custom_fields))
            ->get($this->getInnerContent(), $set->ordering);

        return $set;
    }

    private function getSets()
    {
        $parts = $this->getTagStringParts();

        $known_boolean_keys = [
            'ignore_language', 'ignore_access', 'ignore_state', 'fixhtml',
        ];

        $sets = [];

        foreach ($parts as $string)
        {
            // Get the values from the tag
            $attributes = RL_PluginTag::getAttributesFromString($string, 'id', $known_boolean_keys);

            $key_aliases = [
                'items'                    => ['id', 'ids', 'article', 'articles', 'item', 'title', 'alias'],
                'fixhtml'                  => ['fix_html', 'html_fix', 'htmlfix'],
            ];

            RL_PluginTag::replaceKeyAliases($attributes, $key_aliases);

            $set = $this->getSet($attributes);

            $sets[] = $set;
        }

        return $sets;
    }

    private function getTagString()
    {
        $string = RL_String::html_entity_decoder($this->match_data['id']);

        if ( ! empty($string) && strpos($string, '="') == false && strpos($string, '=\'') == false && strpos($string, '=\'') == false)
        {
            return $this->convertOldToNewSyntax($string, $this->match_data['tag']);
        }

        // protect comma's inside date() functions
        $string = RL_RegEx::replace(
            '(date\(\s*\'.*?\'),(\s*\'.*?\'\s*\))',
            '\1\\,\2',
            $string
        );

        return $string;
    }

    private function getTagStringParts()
    {
        $string = $this->getTagString();

        return [$string];
    }

    private function initSet($attributes)
    {
        $opening_tags_main = RL_Html::removeEmptyTagPairs(
            $this->match_data['opening_tags_before_open']
            . $this->match_data['closing_tags_after_open']
        );

        $opening_tags_item = $this->match_data['opening_tags_before_content'];
        $closing_tags_item = $this->match_data['closing_tags_after_content'];

        $closing_tags_main = RL_Html::removeEmptyTagPairs(
            $this->match_data['opening_tags_before_close']
            . $this->match_data['closing_tags_after_close']
        );

        return (object) [
            'component'        => 'default',
            'type'             => $this->getTagType(),
            'limit'            => 1,
            'offset'           => 0,
            'ignores'          => [],
            'filters'          => [],
            'attributes'       => $attributes,
            'content'          => $opening_tags_item . $this->getInnerContent() . $closing_tags_item,
            'surrounding_tags' => (object) [
                'opening' => $opening_tags_main,
                'closing' => $closing_tags_main,
            ],
        ];
    }

    private function setComponentType(&$set, &$attributes)
    {
    }

    private function setLimits(&$set, &$attributes)
    {
        if ( ! empty($attributes->limit))
        {
            $attributes->limit = Values::getValueFromInput($attributes->limit);
        }

        if ( ! empty($attributes->offset))
        {
            $attributes->offset = Values::getValueFromInput($attributes->offset);
        }

        $set->offset = (int) ($attributes->offset ?? 0);
        unset($attributes->offset);

            $set->limit  = 1;
            $set->offset = 0;
            unset($attributes->limit);

    }
}
