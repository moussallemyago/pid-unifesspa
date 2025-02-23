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

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;

class PlgSystemArticlesAnywhereInstallerScript
{
    public function postflight($install_type, $adapter)
    {
        if ( ! in_array($install_type, ['install', 'update']))
        {
            return true;
        }

        self::disableCoreEditorPlugin();

        return true;
    }

    public function uninstall($adapter)
    {
        self::enableCoreEditorPlugin();
    }

    private static function disableCoreEditorPlugin()
    {
        $db = JFactory::getDbo();

        $query = self::getCoreEditorPluginQuery()
            ->set($db->quoteName('enabled') . ' = 0')
            ->where($db->quoteName('enabled') . ' = 1');
        $db->setQuery($query);
        $db->execute();

        if ( ! $db->getAffectedRows())
        {
            return;
        }

        JFactory::getApplication()->enqueueMessage(JText::_('Joomla\'s own "Article" editor button has been disabled'), 'warning');
    }

    private static function enableCoreEditorPlugin()
    {
        $db = JFactory::getDbo();

        $query = self::getCoreEditorPluginQuery()
            ->set($db->quoteName('enabled') . ' = 1')
            ->where($db->quoteName('enabled') . ' = 0');
        $db->setQuery($query);
        $db->execute();

        if ( ! $db->getAffectedRows())
        {
            return;
        }

        JFactory::getApplication()->enqueueMessage(JText::_('Joomla\'s own "Article" editor button has been re-enabled'), 'warning');
    }

    private static function getCoreEditorPluginQuery()
    {
        $db = JFactory::getDbo();

        return $db->getQuery(true)
            ->update('#__extensions')
            ->where($db->quoteName('element') . ' = ' . $db->quote('article'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('editors-xtd'))
            ->where($db->quoteName('custom_data') . ' NOT LIKE ' . $db->quote('%articlesanywhere_ignore%'));
    }
}
