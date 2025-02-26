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

use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Plugin\System\ArticlesAnywhere\Collection\Fields\CustomFields;
use RegularLabs\Plugin\System\ArticlesAnywhere\Collection\Fields\Fields;
use RegularLabs\Plugin\System\ArticlesAnywhere\Output\Values;
use RegularLabs\Plugin\System\ArticlesAnywhere\Params;

class Selects
{
    protected $component;
    protected $custom_fields;

    public function __construct($component, Fields $fields, CustomFields $custom_fields)
    {
        $this->component     = $component;
        $this->custom_fields = $custom_fields->getAvailableFields();
        $this->ignore_words  = array_merge($fields->getAvailableFields(), [
            'DESC', 'ASC',
        ]);
    }

    public function get($string, $ordering)
    {
        $selects = [
            'users'         => false,
            'modifiers'     => false,
            'categories'    => false,
            'parent'        => false,
            'frontpage'     => false,
            'custom_fields' => [],
        ];

        if (empty($string))
        {
            return $selects;
        }

        if ($ordering)
        {
            $this->addSelectFromOrdering($ordering, $selects);
        }

        $string = str_replace('&nbsp;', ' ', $string);

        [$tag_start, $tag_end] = Params::getTagCharacters();
        [$data_tag_start, $data_tag_end] = Params::getDataTagCharacters();

        // Check if there are any tags found in the content
        $regex = '(?:'
            . RL_RegEx::quote($tag_start) . '(?:if|else if|elseif|else) (?<ifs>[a-z].*?)' . RL_RegEx::quote($tag_end)
            . '|' . RL_RegEx::quote($data_tag_start) . '(?<tags>[a-z].*?)' . RL_RegEx::quote($data_tag_end)
            . ')';

        if ( ! RL_RegEx::matchAll($regex, $string, $matches, null, PREG_PATTERN_ORDER))
        {
            return $selects;
        }

        $keys = RL_Array::clean($matches['tags']);
        $ifs  = RL_Array::clean($matches['ifs']);

        $keys = array_map(fn($key) => RL_RegEx::replace('[ :].*', '', $key), $keys);

        foreach ($keys as $key)
        {
            $this->addSelectFromString($key, $selects);
        }

        foreach ($ifs as $if)
        {
            $this->addSelectFromString($if, $selects);
        }

        return $selects;
    }

    protected function addSelect($key, &$selects)
    {
        if (in_array($key, $this->ignore_words))
        {
            return;
        }

        if ($key == 'frontpage')
        {
            $selects['frontpage'] = true;

            return;
        }

        if (RL_RegEx::match('^(user|users|author|authors)(-|$)', $key))
        {
            $selects['users'] = true;

            return;
        }

        if (RL_RegEx::match('^(modifier|modifiers)(-|$)', $key))
        {
            $selects['modifiers'] = true;

            return;
        }

        if (RL_RegEx::match('(^|-)(category|categories)(-|$)', $key))
        {
            $selects['categories'] = true;

            return;
        }

        if (RL_RegEx::match('(^|-)parent(-|$)', $key))
        {
            $selects['categories'] = true;
            $selects['parent']     = true;

            return;
        }

    }

    protected function addSelectFromOrdering($ordering, &$selects)
    {
        if (empty($ordering->joins))
        {
            return;
        }

        foreach ($ordering->joins as $join)
        {
            $this->addSelect($join, $selects);
        }
    }

    protected function addSelectFromString($string, &$selects)
    {
        $parts = $this->getPartsFromString($string);

        foreach ($parts as $string)
        {
            $string = Values::translateKey($string);
            $this->addSelect($string, $selects);
        }
    }

    protected function getPartsFromString($string)
    {
        $string = RL_RegEx::replace('(".*?"|\'.*?\')', '', $string);
        $string = RL_RegEx::replace('[^a-z0-9-_]', ' ', $string);

        $parts = preg_split('# +#i', $string);

        $parts = array_map(fn($part) => RL_RegEx::replace('^[^a-z]*', '', $part), $parts);

        return RL_Array::clean($parts);
    }
}
