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

use JDatabaseQuery;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Uri\Uri as JUri;
use RegularLabs\Library\CacheNew as RL_Cache;
use RegularLabs\Plugin\System\ArticlesAnywhere\Params;

class DB
{
    static $query_time;

    public static function getResults(JDatabaseQuery $query, $method = 'loadColumn', $arguments = [], $limit = 0, $offset = 0, $allow_caching = true)
    {
        if ( ! $query)
        {
            return null;
        }

        $params = Params::get();

        $query_cache_id = $allow_caching ? self::getQueryId($query, [$method, $arguments, $limit, $offset]) : '';
        $allow_caching  = ! empty($query_cache_id);

        $cache = null;

        if ($allow_caching)
        {
            $force_caching = $params->use_query_cache == 2;

            $cache = (new RL_Cache($query_cache_id))
                ->useFiles(
                    self::getQueryTime(),
                    $force_caching
                );
        }

        if ($allow_caching && $cache->exists())
        {
            return $cache->get();
        }

        $db = JFactory::getDbo();

        // MySQL needs a limit if you want an offset
        if ($offset > 0 && $limit == 0)
        {
            $limit = 9999;
        }

        $query->setLimit($limit, $offset);

        $use_query_log_cache = $allow_caching && $params->use_query_comments && $params->use_query_log_cache;

        if (JDEBUG || $params->use_query_comments)
        {
            $backtrace = self::getQueryComment();
        }

        if ($use_query_log_cache)
        {
            $query_cache = ''
                . "\n\n" . 'QUERY:' . "\n==========\n" . trim((string) $query)
                . "\n\n" . 'METHOD: ' . "\n==========\n" . $method
                . (! empty($arguments) ? "\n\n" . 'ARGUMENTS:' . "\n==========\n" . json_encode($arguments) : '')
                . ($offset ? "\n\n" . 'OFFSET:' . "\n==========\n" . $offset : '')
                . ($limit ? "\n\n" . 'LIMIT:' . "\n==========\n" . $limit : '')
                . "\n\n" . 'BACKTRACE:' . "\n==========\n" . str_replace(' => ', "\n", $backtrace)
                . "\n\n";
        }

        if (JDEBUG || $params->use_query_comments)
        {
            $query->select(
                $db->quote($backtrace) . ' as ' . $db->quote('query_comment')
            );
        }

        $db->setQuery($query);

        $result = call_user_func_array([$db, $method], $arguments);

        if ( ! $allow_caching)
        {
            return $result;
        }

        if ($use_query_log_cache)
        {
            (new RL_Cache($query_cache_id, 'regularlabs_query'))
                ->useFiles(
                    self::getQueryTime() * 60,
                    true
                )
                ->set($query_cache);
        }

        return $cache->set($result);
    }

    private static function getQueryComment()
    {
        $callers = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

        array_shift($callers);
        array_shift($callers);

        $callers = array_reverse($callers);

        $lines = [
            JUri::getInstance()->toString(),
        ];

        foreach ($callers as $caller)
        {
            $lines[] = str_replace(
                    '\\',
                    '.',
                    substr($caller['class'], 26)
                )
                . '.' . $caller['function']
                . " : " . $caller['line'];
        }

        return '[ ' . implode(' ] => [ ', $lines) . ' ]';
    }

    private static function getQueryId(JDatabaseQuery $query, $arguments)
    {
        if ( ! Params::get()->use_query_cache)
        {
            return '';
        }

        $query = (string) $query;

        // Don't cache queries with random ordering
        if (strpos($query, 'RAND()') !== false)
        {
            return '';
        }

        return 'getResults' . md5(json_encode([$query, $arguments]));
    }

    private static function getQueryTime()
    {
        if ( ! is_null(self::$query_time))
        {
            return self::$query_time;
        }

        self::$query_time = (int) Params::get()->query_cache_time ?: JFactory::getConfig()->get('cachetime');

        return self::$query_time;
    }
}
