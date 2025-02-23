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

namespace RegularLabs\Plugin\System\ArticlesAnywhere;

defined('_JEXEC') or die;

use RegularLabs\Library\Protect as RL_Protect;

class Protect
{
    static $name = 'Articles Anywhere';

    public static function _(&$string)
    {
        RL_Protect::protectHtmlCommentTags($string);
        RL_Protect::protectFields($string, Params::getTags(true));
        // Don't protect Sourcerer blocks, as you want to be able to use Articles Anywhere data tags inside Sourcerer code
        //RL_Protect::protectSourcerer($string);
    }

    /**
     * Wrap the comment in comment tags
     *
     * @param string $comment
     *
     * @return string
     */
    public static function getMessageCommentTag($comment)
    {
        return RL_Protect::getMessageCommentTag(self::$name, $comment);
    }

    public static function protectTags(&$string)
    {
        RL_Protect::protectTags($string, Params::getTags(true));
    }

    public static function unprotectTags(&$string)
    {
        RL_Protect::unprotectTags($string, Params::getTags(true));
    }

    /**
     * Wrap string in comment tags
     *
     * @param string $string
     *
     * @return string
     */
    public static function wrapInCommentTags($string)
    {
        return RL_Protect::wrapInCommentTags(self::$name, $string);
    }
}
