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

class Fields extends Filter
{
    public function setFilter(JDatabaseQuery $query, $filters = [])
    {
        foreach ($filters as $key => $value)
        {
            $conditions = $this->getConditionsFromValues('items.' . $key, $value);

            $query->where($conditions);
        }
    }
}
