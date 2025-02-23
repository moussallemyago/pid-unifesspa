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

use JDatabaseDriver;
use Joomla\CMS\Factory as JFactory;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Plugin\System\ArticlesAnywhere\Collection\Fields\CustomFields;
use RegularLabs\Plugin\System\ArticlesAnywhere\Config;
use RegularLabs\Plugin\System\ArticlesAnywhere\Params;

class Ordering
{
    /* @var Config */
    protected $config;

    /* @var JDatabaseDriver */
    private $db;

    public function __construct(Config $config, CustomFields $custom_fields)
    {
        $this->config        = $config;
        $this->db            = JFactory::getDbo();
        $this->custom_fields = $custom_fields->getAvailableFields();
    }

    public function get($attributes)
    {
        return false;
    }

    protected function getColumns()
    {
    }

    protected function getOrderings($orderings, $default_direction = 'ASC')
    {
    }

    protected function parse(&$ordering, &$joins, $ordering_direction = 'ASC')
    {
    }
}
