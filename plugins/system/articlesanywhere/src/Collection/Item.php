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

use ArticlesAnywhereArticleModel;
use JDatabaseDriver;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Helper\TagsHelper as JTagsHelper;
use RegularLabs\Library\DB as RL_DB;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Plugin\System\ArticlesAnywhere\Config;
use RegularLabs\Plugin\System\ArticlesAnywhere\Params;

class Item
{
    /* @var Config */
    protected $config;
    protected $data;

    /* @var JDatabaseDriver */
    protected $db;

    public function __construct(Config $config, $data)
    {
        $this->config = $config;
        $this->data   = $data;
        $this->db     = JFactory::getDbo();
    }

    public function get($key = '', $default = null)
    {
        if (empty($key))
        {
            return $this->data;
        }

        if ($key == 'is_published')
        {
            return $this->isPublished();
        }

        if ($key == 'has_access')
        {
            return $this->hasAccess();
        }

        // for articles, store the 'text' content under the 'alltext' key,
        // as 'text' is used for other stuff too.
        if (isset($this->data->introtext))
        {
            if ($key == 'text')
            {
                $key = 'alltext';
            }

            if ($key == 'alltext' && ! isset($this->data->alltext))
            {
                $this->data->alltext = $this->data->introtext
                    . ($this->data->fulltext ?? '');
            }
        }

        return $this->data->{$key} ?? $default;
    }

    public function getArticle()
    {
        require_once dirname(__FILE__, 2) . '/Helpers/article_model.php';

        $model = new ArticlesAnywhereArticleModel;

        return $model->getItem($this->getId());
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getConfigData($name = '', $quote = true, $prefix = '')
    {
        return $this->config->getData($name, $quote, $prefix);
    }

    public function getData()
    {
        return $this->data;
    }

    public function getFromGroup($group = '', $key = '', $default = null)
    {
        $values = $this->getGroupValues($group);

        if (empty($values))
        {
            return $default;
        }

        // See if the key is found
        if (isset($values->{$key}))
        {
            return $values->{$key};
        }

        // See if the key (prepended with the group name) is found
        // Like: metadata_author
        if (isset($values->{$group . '_' . $key}))
        {
            return $values->{$group . '_' . $key};
        }

        $key_no_prefix = RL_RegEx::replace('^meta-', 'metadata-', $key);
        $key_no_prefix = RL_RegEx::replace('^' . $group . '-', '', $key_no_prefix);

        // See if the key without the group name prefix is found
        // Like: metadata-author
        if (isset($values->{$key_no_prefix}))
        {
            return $values->{$key_no_prefix};
        }

        return $default;
    }

    public function getGroupValues($group = '')
    {
        if (is_null($this->get($group)))
        {
            return null;
        }

        return json_decode($this->get($group));
    }

    public function getId()
    {
        return $this->get('id', 0);
    }

    public function getTags()
    {
        $tags = new JTagsHelper;
        $tags->getItemTags('com_content.article', $this->getId());

        return $tags->itemTags ?? [];
    }

    public function hasAccess()
    {
        if ( ! $this->getId())
        {
            return true;
        }

        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('access') . ' ' . RL_DB::in(Params::getAuthorisedViewLevels()))
            ->from($this->config->getTableItems())
            ->where($this->db->quoteName('id') . ' = ' . (int) $this->getId());

        return (bool) DB::getResults($query, 'loadResult', [], 0, 0, false);
    }

    public function hit()
    {
        if ( ! Params::get()->increase_hits_on_text)
        {
            return;
        }

        require_once dirname(__FILE__, 2) . '/Helpers/article_model.php';

        $model = new ArticlesAnywhereArticleModel;

        $model->hit($this->getId());
    }

    public function isPublished()
    {
        if ( ! $this->getId())
        {
            return true;
        }

        if ($this->get('state') != 1)
        {
            return false;
        }

        $publish_up   = $this->get('publish_up');
        $publish_down = $this->get('publish_down');

        $nowDate  = JFactory::getDate()->toSql();
        $nullDate = $this->db->getNullDate();

        return $publish_up <= $nowDate
            && (
                $publish_down == $nullDate
                || $publish_down >= $nowDate
            );
    }

    public function set($key, $value)
    {
        return $this->data->{$key} = $value;
    }

    public function setContent($value)
    {
        return $this->config->setContent($value);
    }
}
