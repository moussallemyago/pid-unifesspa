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

class Extra extends Data
{
    var $groups = ['attribs', 'urls', 'images', 'metadata'];

    public function get($key, $attributes)
    {
        foreach ($this->groups as $group)
        {
            $value = $this->item->getFromGroup($group, $key);

            if (is_null($value))
            {
                continue;
            }

            return $value;
        }

        return null;
    }

}
