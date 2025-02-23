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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\Collection\Filters;

defined('_JEXEC') or die;

use JDatabaseQuery;
use RegularLabs\Library\RegEx as RL_RegEx;

class Items extends Filter
{
    public function getOrdering()
    {
        $filter = $this->config->getFilters('items');

        if (empty($filter))
        {
            return false;
        }

        $names_unquoted = implode(',', $filter);
        $names          = implode(',', $this->db->quote($filter));

        // $names are numeric (so assume ids)
        if (RL_RegEx::match('^[0-9,]+$', $names_unquoted))
        {
            return 'FIELD('
                . $this->config->getId('items', true, 'items') . ','
                . $names
                . ')';
        }

        // $names are lowercase (so assume aliases)
        if ( ! RL_RegEx::match('[A-Z]', $names_unquoted, $matches, 's'))
        {
            return 'FIELD('
                . $this->config->getAlias('items', true, 'items') . ','
                . $names
                . ')';
        }

        // Default to title ordering
        return 'FIELD('
            . $this->config->getTitle('items', true, 'items') . ','
            . $names
            . ')';
    }

    public function setFilter(JDatabaseQuery $query, $filters = [])
    {
        $this->setFiltersFromNames($query, 'items', $filters);
    }
}
