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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\Collection;

defined('_JEXEC') or die;

use RegularLabs\Library\DB as RL_DB;
use RegularLabs\Plugin\System\ArticlesAnywhere\Collection\Filters;
use RegularLabs\Plugin\System\ArticlesAnywhere\Config;
use RegularLabs\Plugin\System\ArticlesAnywhere\Factory;
use RegularLabs\Plugin\System\ArticlesAnywhere\Output\Output;
use RegularLabs\Plugin\System\ArticlesAnywhere\Output\Pagination;
use RegularLabs\Plugin\System\ArticlesAnywhere\Params;

class Collection extends CollectionObject
{
    /* @var Filters\Categories $categories */
    protected $categories;
    /* @var Filters\CustomFields $custom_fields */
    protected $custom_fields;
    /* @var Filters\Fields $fields */
    protected $fields;
    /* @var Filters\Items $items */
    protected $items;
    /* @var Pagination $pagination */
    protected $pagination;
    /* @var Filters\Tags $tags */
    protected $tags;

    public function __construct(Config $config)
    {
        parent::__construct($config);

        $this->items      = Factory::getFilter('Items', $config);
        $this->fields     = Factory::getFilter('Fields', $config);
        $this->pagination = Factory::getPagination($config);

    }

    public function getOnlyIds()
    {
        return $this->getIds();
    }

    public function getOutputByIds($total_ids = [], $default = '')
    {

        if (empty($total_ids))
        {
            $surrounding_tags = $this->config->getData('surrounding_tags');

            return $surrounding_tags->opening
                . $default
                . $surrounding_tags->closing;
        }

        $ids = [$total_ids[0]];

        // Now get Item data for found ids
        $items = $this->getData($ids);

        $items = array_map(fn($item) => Factory::getItem($this->config, $item), $items);

        return $this->getOutput(
            $items,
            count($total_ids),
            count($ids)
        );
    }

    protected function applyLimit($ids)
    {
    }

    protected function applyOrdering(&$query, $prefix = 'items')
    {
    }

    protected function getData($ids)
    {
        $query = $this->getDataQuery($ids);

        if ( ! $query)
        {
            return [];
        }

        $query->select('items.*');

        return DB::getResults($query,
            'loadObjectList',
            [],
            $this->pagination->params->limit ?: 100,
            $this->pagination->params->offset - $this->pagination->params->offset_start
        );
    }

    protected function getDataQuery($ids = [], $prefix = 'items')
    {
        if (empty($ids))
        {
            return false;
        }

        $selects = $this->config->getSelects();

        $query = $this->db->getQuery(true)
            ->select($prefix . '.id')
            ->from($this->config->getTableItems($prefix))
            ->where($this->db->quoteName($prefix . '.id') . RL_DB::in($ids))
            ->group($this->db->quoteName($prefix . '.id'));

        if ($selects['frontpage'])
        {
            $query->select([
                $this->db->quoteName('frontpage.ordering', 'featured-ordering'),
            ])
                ->join('LEFT', $this->config->getTableFeatured('frontpage')
                    . ' ON ' . $this->db->quoteName('frontpage.content_id') . ' = ' . $this->db->quoteName($prefix . '.id'));
        }

        if ($selects['categories'])
        {
            $query->select([
                $this->config->getId('categories', 'category-id', 'categories'),
                $this->config->getTitle('categories', 'category-title', 'categories'),
                $this->config->getAlias('categories', 'category-alias', 'categories'),
                $this->config->get('description', 'category-description', 'categories', 'description'),
                $this->db->quoteName('categories.params', 'category-params'),
                //$this->db->quoteName('categories.metadata', 'category-metadata'),
            ])
                ->join('LEFT', $this->config->getTableCategories('categories')
                    . ' ON ' . $this->db->quoteName('categories.id') . ' = ' . $this->db->quoteName($prefix . '.catid'));
        }

        if ($selects['parent'])
        {
            $query->select([
                $this->config->getId('categories', 'parent-id', 'parent'),
                $this->config->getTitle('categories', 'parent-title', 'parent'),
                $this->config->getAlias('categories', 'parent-alias', 'parent'),
                $this->config->get('description', 'parent-description', 'parent', 'description'),
                $this->db->quoteName('parent.params', 'parent-params'),
                //$this->db->quoteName('categories.metadata', 'category-metadata'),
            ])
                ->join('LEFT', $this->config->getTableCategories('parent')
                    . ' ON ' . $this->db->quoteName('parent.id') . ' = ' . $this->db->quoteName('categories.parent_id'));
        }

        if ($selects['users'])
        {
            $query->select([
                $this->db->quoteName('user.id', 'author-id'),
                $this->db->quoteName('user.name', 'author-name'),
                $this->db->quoteName('user.username', 'author-username'),
            ])
                ->join('LEFT', $this->db->quoteName('#__users', 'user')
                    . ' ON ' . $this->db->quoteName('user.id') . ' = ' . $this->db->quoteName($prefix . '.created_by'));
        }

        if ($selects['modifiers'])
        {
            $query->select([
                $this->db->quoteName('modifier.id', 'modifier-id'),
                $this->db->quoteName('modifier.name', 'modifier-name'),
                $this->db->quoteName('modifier.username', 'modifier-username'),
            ])
                ->join('LEFT', $this->db->quoteName('#__users', 'modifier')
                    . ' ON ' . $this->db->quoteName('modifier.id') . ' = ' . $this->db->quoteName($prefix . '.modified_by'));
        }

        if ($selects['custom_fields'])
        {
            foreach ($selects['custom_fields'] as $custom_field)
            {
                $table_as = 'custom_field_' . $custom_field->id;

                $query->select($this->db->quoteName($table_as . '.value', 'custom_field_' . $custom_field->name))
                    ->join('LEFT', $this->config->getTableFieldsValues($table_as)
                        . "\n" . ' ON ' . $this->db->quoteName($table_as . '.item_id') . ' = ' . $this->db->quoteName($prefix . '.id')
                        . "\n" . ' AND ' . $this->db->quoteName($table_as . '.field_id') . ' = ' . $this->db->quote($custom_field->id));
            }
        }


        return $query;
    }

    protected function getIds()
    {
        $query = $this->getIdsQuery();

        // Don't cache this query because of the publish up date being checked against the now date
        // making every query unique
        return DB::getResults($query, 'loadColumn', [], 0, 0, false) ?: [];
    }

    protected function getIdsQuery()
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('items.id'))
            ->from($this->config->getTableItems('items'))
            ->join('LEFT', $this->config->getTableCategories('categories')
                . ' ON ' . $this->db->quoteName('categories.id') . ' = ' . $this->db->quoteName('items.catid'))
            ->group($this->db->quoteName('items.id'));

        $this->items->set($query);
        $this->fields->set($query);

        $this->setIgnores($query, 'categories');
        $this->setIgnores($query);

        if ($this->config->getFilters('one_per_category'))
        {
            $query->select($this->db->quoteName('items.catid'));

            $ids = [];

            foreach (DB::getResults($query, 'loadObjectList') as $item)
            {
                if (isset($ids[$item->catid]))
                {
                    continue;
                }

                $ids[$item->catid] = $item->id;
            }

            $query = $this->db->getQuery(true)
                ->select($this->db->quoteName('items.id'))
                ->from($this->config->getTableItems('items'))
                ->where($this->db->quoteName('items.id') . RL_DB::in($ids));
        }
        /* <<< [PRO] <<< */

        return $query;
    }

    protected function getOutput($items, $total_no_limit, $total_no_pagination)
    {
        return (new Output($this->config))->get($items, $total_no_limit, $total_no_pagination);
    }

}
