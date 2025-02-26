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

/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\MVC\Model\ItemModel as JModelItem;
use Joomla\CMS\Table\Table as JTable;
use Joomla\Registry\Registry;

/**
 * Content Component Article Model
 *
 * @since  1.5
 */
class  ArticlesAnywhereArticleModel extends JModelItem
{
    /**
     * Model context string.
     *
     * @var        string
     */
    protected $_context = 'com_content.article';

    /**
     * Method to get article data.
     *
     * @param integer $pk The id of the article.
     *
     * @return  object|boolean|JException  Menu item data object on success, boolean false or JException instance on error
     */
    public function getItem($pk = null)
    {
        $user = JFactory::getApplication()->getIdentity() ?: JFactory::getUser();

        $pk = ( ! empty($pk)) ? $pk : (int) $this->getState('article.id');

        if ($this->_item === null)
        {
            $this->_item = [];
        }

        if ( ! isset($this->_item[$pk]))
        {
            try
            {
                $db    = $this->getDbo();
                $query = $db->getQuery(true)
                    ->select(
                        $this->getState(
                            'item.select', 'a.id, a.asset_id, a.title, a.alias, a.introtext, a.fulltext, ' .
                            // If badcats is not null, this means that the article is inside an unpublished category
                            // In this case, the state is set to 0 to indicate Unpublished (even if the article state is Published)
                            'CASE WHEN badcats.id is null THEN a.state ELSE 0 END AS state, ' .
                            'a.catid, a.created, a.created_by, a.created_by_alias, ' .
                            // Use created if modified is 0
                            'CASE WHEN a.modified = ' . $db->quote($db->getNullDate()) . ' THEN a.created ELSE a.modified END as modified, ' .
                            'a.modified_by, a.checked_out, a.checked_out_time, a.publish_up, a.publish_down, ' .
                            'a.images, a.urls, a.attribs, a.version, a.ordering, ' .
                            'a.metakey, a.metadesc, a.access, a.hits, a.metadata, a.featured, a.language, a.xreference'
                        )
                    );
                $query->from('#__content AS a');

                // Join on category table.
                $query->select('c.title AS category_title, c.alias AS category_alias, c.access AS category_access')
                    ->join('LEFT', '#__categories AS c on c.id = a.catid');

                // Join on user table.
                $query->select('u.name AS author')
                    ->join('LEFT', '#__users AS u on u.id = a.created_by');

                // Join over the categories to get parent category titles
                $query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
                    ->join('LEFT', '#__categories as parent ON parent.id = c.parent_id');

                // Join on voting table
                $query->select('ROUND(v.rating_sum / v.rating_count, 0) AS rating, v.rating_count as rating_count')
                    ->join('LEFT', '#__content_rating AS v ON a.id = v.content_id')
                    ->where('a.id = ' . (int) $pk);

                // Join to check for category published state in parent categories up the tree
                // If all categories are published, badcats.id will be null, and we just use the article state
                $subquery = ' (SELECT cat.id as id FROM #__categories AS cat JOIN #__categories AS parent ';
                $subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
                $subquery .= 'WHERE parent.extension = ' . $db->quote('com_content');
                $subquery .= ' AND parent.published <= 0 GROUP BY cat.id)';
                $query->join('LEFT OUTER', $subquery . ' AS badcats ON badcats.id = c.id');

                $db->setQuery($query);

                $data = $db->loadObject();

                if (empty($data))
                {
                    throw new Exception(JText::_('COM_CONTENT_ERROR_ARTICLE_NOT_FOUND'), 404);
                }

                // Convert parameter fields to objects.
                $registry = new Registry;
                $registry->loadString($data->attribs);

                $data->params = clone $this->getState('params');
                $data->params->merge($registry);

                $registry = new Registry;
                $registry->loadString($data->metadata);
                $data->metadata = $registry;

                // Technically guest could edit an article, but lets not check that to improve performance a little.
                if ( ! $user->get('guest'))
                {
                    $userId = $user->get('id');
                    $asset  = 'com_content.article.' . $data->id;

                    // Check general edit permission first.
                    if ($user->authorise('core.edit', $asset))
                    {
                        $data->params->set('access-edit', true);
                    }

                    // Now check if edit.own is available.
                    elseif ( ! empty($userId) && $user->authorise('core.edit.own', $asset))
                    {
                        // Check for a valid user and that they are the owner.
                        if ($userId == $data->created_by)
                        {
                            $data->params->set('access-edit', true);
                        }
                    }
                }

                // Compute view access permissions.
                if ($this->getState('filter.access'))
                {
                    // If the access filter has been set, we already know this user can view.
                    $data->params->set('access-view', true);
                }
                else
                {
                    // If no access filter is set, the layout takes some responsibility for display of limited information.
                    $user   = JFactory::getApplication()->getIdentity() ?: JFactory::getUser();
                    $groups = $user->getAuthorisedViewLevels();

                    if ($data->catid == 0 || $data->category_access === null)
                    {
                        $data->params->set('access-view', in_array($data->access, $groups));
                    }
                    else
                    {
                        $data->params->set('access-view', in_array($data->access, $groups) && in_array($data->category_access, $groups));
                    }
                }

                $this->_item[$pk] = $data;
            }
            catch (Exception $e)
            {
                if ($e->getCode() == 404)
                {
                    // Need to go thru the error handler to allow Redirect to work.
                    throw new Exception($e->getMessage(), 404);
                }

                $this->setError($e);
                $this->_item[$pk] = false;
            }
        }

        return $this->_item[$pk];
    }

    /**
     * Increment the hit counter for the article.
     *
     * @param integer $pk Optional primary key of the article to increment.
     *
     * @return  boolean  True if successful; false otherwise and internal error set.
     */
    public function hit($pk = 0)
    {
        $input    = JFactory::getApplication()->input;
        $hitcount = $input->getInt('hitcount', 1);

        if ($hitcount)
        {
            $pk = ( ! empty($pk)) ? $pk : (int) $this->getState('article.id');

            $table = JTable::getInstance('Content', 'JTable');
            $table->load($pk);
            $table->hit($pk);
        }

        return true;
    }

    /**
     * Save user vote on article
     *
     * @param integer $pk   Joomla Article Id
     * @param integer $rate Voting rate
     *
     * @return  boolean          Return true on success
     */
    public function storeVote($pk = 0, $rate = 0)
    {
        if ($rate >= 1 && $rate <= 5 && $pk > 0)
        {
            $userIP = $_SERVER['REMOTE_ADDR'];

            // Initialize variables.
            $db    = $this->getDbo();
            $query = $db->getQuery(true);

            // Create the base select statement.
            $query->select('*')
                ->from($db->quoteName('#__content_rating'))
                ->where($db->quoteName('content_id') . ' = ' . (int) $pk);

            // Set the query and load the result.
            $db->setQuery($query);

            // Check for a database error.
            try
            {
                $rating = $db->loadObject();
            }
            catch (RuntimeException $e)
            {
                throw new Exception($e->getMessage(), 500);
            }

            // There are no ratings yet, so lets insert our rating
            if ( ! $rating)
            {
                $query = $db->getQuery(true);

                // Create the base insert statement.
                $query->insert($db->quoteName('#__content_rating'))
                    ->columns([$db->quoteName('content_id'), $db->quoteName('lastip'), $db->quoteName('rating_sum'), $db->quoteName('rating_count')])
                    ->values((int) $pk . ', ' . $db->quote($userIP) . ',' . (int) $rate . ', 1');

                // Set the query and execute the insert.
                $db->setQuery($query);

                try
                {
                    $db->execute();
                }
                catch (RuntimeException $e)
                {
                    throw new Exception($e->getMessage(), 500);
                }
            }
            else
            {
                if ($userIP != ($rating->lastip))
                {
                    $query = $db->getQuery(true);

                    // Create the base update statement.
                    $query->update($db->quoteName('#__content_rating'))
                        ->set($db->quoteName('rating_count') . ' = rating_count + 1')
                        ->set($db->quoteName('rating_sum') . ' = rating_sum + ' . (int) $rate)
                        ->set($db->quoteName('lastip') . ' = ' . $db->quote($userIP))
                        ->where($db->quoteName('content_id') . ' = ' . (int) $pk);

                    // Set the query and execute the update.
                    $db->setQuery($query);

                    try
                    {
                        $db->execute();
                    }
                    catch (RuntimeException $e)
                    {
                        throw new Exception($e->getMessage(), 500);
                    }
                }
                else
                {
                    return false;
                }
            }

            return true;
        }

        JError::raiseWarning('SOME_ERROR_CODE', JText::sprintf('COM_CONTENT_INVALID_RATING', $rate), "JModelArticle::storeVote($rate)");

        return false;
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @return void
     * @since   1.6
     *
     */
    protected function populateState()
    {
        $app    = JFactory::getApplication('site');
        $params = new Registry;

        if (is_a($app, 'Joomla\CMS\Application\SiteApplication'))
        {
            // Load the parameters.
            $params = $app->getParams();
        }
        else
        {
            $app = CMSApplication::getInstance('site');
        }

        // Load state from the request.
        $pk = $app->input->getInt('id');
        $this->setState('article.id', $pk);

        $offset = $app->input->getUInt('a_limitstart');
        $this->setState('list.offset', $offset);

        $this->setState('params', $params);
    }
}
