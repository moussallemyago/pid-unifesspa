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

use RegularLabs\Plugin\System\ArticlesAnywhere\Collection\Item;
use RegularLabs\Plugin\System\ArticlesAnywhere\Config;
use RegularLabs\Plugin\System\ArticlesAnywhere\Output\Data\Numbers;

class OutputObject
{
    var $config;
    var $item;
    var $numbers;
    var $values;

    public function __construct(Config $config, Item $item, Numbers $numbers)
    {
        $this->config  = $config;
        $this->item    = $item;
        $this->numbers = $numbers;
        $this->values  = new Values($config, $item, $numbers);
    }
}
