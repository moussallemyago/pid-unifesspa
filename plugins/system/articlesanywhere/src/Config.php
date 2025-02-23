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

use JDatabaseDriver;
use Joomla\CMS\Factory as JFactory;
use Symfony\Component\Yaml\Parser;

class Config
{
    protected $component;
    protected $config;
    protected $data;
    /* @var JDatabaseDriver */
    protected $db;
    protected $yaml;

    public function __construct($data)
    {
        $this->data      = $data;
        $this->component = $data->component;
        $this->db        = JFactory::getDbo();
        $this->yaml      = new Parser;
    }

    public function get($name = '', $quote = true, $prefix = '', $default = '')
    {
        if ( ! empty($name))
        {
            return $this->getByName($name, $quote, $prefix, $default);
        }

        if ( ! is_null($this->config))
        {
            return $this->config;
        }

        $config_file  = __DIR__ . '/config.yaml';
        $this->config = (object) $this->yaml->parse(file_get_contents($config_file));

        if ($this->component == 'default')
        {
            return $this->config;
        }

        $config_file = __DIR__ . '/Components/' . $this->getComponentName() . '/config.yaml';

        if ( ! is_file($config_file))
        {
            return $this->config;
        }

        $config = (object) $this->yaml->parse(file_get_contents($config_file));

        if (empty($config))
        {
            return $this->config;
        }

        $this->config = (object) array_merge((array) $this->config, (array) $config);

        return $this->config;
    }

    public function getAlias($table, $quote = true, $prefix = '')
    {
        return $this->getByName($table . '_alias', $quote, $prefix, 'alias');
    }

    public function getComponentName()
    {
        if ($this->component == 'default')
        {
            return '';
        }

        return ucfirst($this->component);
    }

    public function getContent()
    {
        return $this->getData('content');
    }

    public function getContext($quote = false)
    {
        return $this->get('context', $quote);
    }

    public function getData($name = '', $default = '')
    {
        if ( ! empty($name))
        {
            return $this->data->{$name} ?? $default;
        }

        return $this->data;
    }

    public function getFilters($group = '')
    {
        if (empty($group))
        {
            return $this->getData('filters', []);
        }

        $filters = $this->getFilters();

        return $filters[$group] ?? null;
    }

    public function getFiltersIncludeChildren($group = '')
    {
        return $this->getFilters($group . '_include_children') ?: false;
    }

    public function getId($table, $quote = true, $prefix = '')
    {
        return $this->getByName($table . '_id', $quote, $prefix, 'id');
    }

    public function getIgnores()
    {
        return $this->getData('ignores', []);
    }

    public function getOrdering()
    {
        return $this->getData('ordering', []);
    }

    public function getSelects()
    {
        return $this->getData('selects', []);
    }

    public function getTableCategories($quote = true)
    {
        return $this->getTableValue('categories_table', $quote);
    }

    public function getTableFeatured($quote = true)
    {
        return $this->getTableValue('featured_table', $quote);
    }

    public function getTableFields($quote = true)
    {
        return $this->getTableValue('fields_table', $quote);
    }

    public function getTableFieldsValues($quote = true)
    {
        return $this->getTableValue('fields_values_table', $quote);
    }

    public function getTableItems($quote = true)
    {
        return $this->getTableValue('items_table', $quote);
    }

    public function getTableTags($quote = true)
    {
        return $this->getTableValue('tags_table', $quote);
    }

    public function getTableValue($name, $quote = true)
    {
        $value = '#__' . $this->get($name, false);

        return $this->getValue($value, $quote);
    }

    public function getTitle($table, $quote = true, $prefix = '')
    {
        return $this->getByName($table . '_title', $quote, $prefix, 'title');
    }

    public function setContent($value)
    {
        return $this->data->content = $value;
    }

    private function getByName($name, $quote = true, $prefix = '', $default = '')
    {
        $value = isset($this->get()->{$name}) ? $this->get()->{$name} : $default;

        return $this->getValue($value, $quote, $prefix);
    }

    private function getValue($value, $quote = true, $prefix = '')
    {
        if ($value === '')
        {
            return $value;
        }

        if ($prefix)
        {
            $value = $prefix . '.' . $value;
        }

        if ( ! $quote)
        {
            return $value;
        }

        if ($quote === true)
        {
            return $this->db->quoteName($value);
        }

        return $this->db->quoteName($value, $quote);
    }
}
