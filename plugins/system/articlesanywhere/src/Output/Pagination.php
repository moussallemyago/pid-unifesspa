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

use Joomla\CMS\Factory as JFactory;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Plugin\System\ArticlesAnywhere\Config;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Pagination as PaginationHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\Params;

class Pagination
{
    /* @var Config */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->params = $this->getParams();
    }

    public function render($position, $total)
    {


        return '';
    }

    private function getParams()
    {
        return (object) [
            'enable'         => false,
            'limit'          => 1,
            'total_limit'    => 1,
            'total_no_limit' => 1,
            'page'           => 1,
            'offset'         => 0,
            'offset_start'   => 0,
            'position'       => [],
            'show_results'   => false,
        ];
    }

}
