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

use RegularLabs\Plugin\System\ArticlesAnywhere\Collection\Item;
use RegularLabs\Plugin\System\ArticlesAnywhere\Config;
use RegularLabs\Plugin\System\ArticlesAnywhere\Output\Values;

class Data implements DataInterface
{
    static $static_item;
    var    $config;
    var    $item;
    var    $values;

    public function __construct(Config $config, Item $item, Values $values)
    {
        $this->config      = $config;
        $this->item        = $item;
        $this->values      = $values;
        self::$static_item = $item;
    }

    public function get($key, $attributes)
    {
        return null;
    }
}
