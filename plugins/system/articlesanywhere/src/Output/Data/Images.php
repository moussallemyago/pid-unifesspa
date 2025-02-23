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

use ContentHelperRoute;
use Joomla\CMS\Layout\LayoutHelper as JLayoutHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel as JModel;
use Joomla\CMS\Router\Route as JRoute;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\File as RL_File;
use RegularLabs\Library\HtmlTag as RL_HtmlTag;
use RegularLabs\Library\Image as RL_Image;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\Params;

class Images extends Data
{

    public static function getImageHtml($attributes)
    {
        $attributes = (object) $attributes;

        $src   = ' src="' . htmlspecialchars($attributes->src) . '"';
        $alt   = ' alt="' . htmlspecialchars($attributes->alt ?? '') . '"';
        $title = ! empty($attributes->title) ? ' title="' . htmlspecialchars($attributes->title) . '"' : '';
        $class = ! empty($attributes->class) ? ' class="' . htmlspecialchars($attributes->class) . '"' : '';

        unset($attributes->src);
        unset($attributes->alt);
        unset($attributes->title);
        unset($attributes->class);

        $tag = '<img' . $src . $alt . $title . $class . '">';

        $image = self::getImageHtmlWithAttributes($tag, $attributes);

        return $image;
    }

    public static function setAltAndTitle($type, &$attributes, $data = null)
    {
        self::crossFillAltAndTitle($attributes);

    }

    public function get($key, $attributes)
    {
        $regex = '^image(?<separator>[-_])(?<type>intro|fulltext)([-_](?<data>[a-z][a-z0-9-_]*))?$';

        RL_RegEx::match($regex, $key, $image_tag);

        if (empty($image_tag))
        {
            return null;
        }

        $data = $image_tag['data'] ?? '';

        if ($image_tag['separator'] == '_' && empty($data))
        {
            $data = 'url';
        }

        $data = str_replace(
            [
                'thumbnail',
            ],
            [
                'thumb',
            ],
            $data
        );
        $data = RL_RegEx::replace('-?(tag|img)$', '', $data);

        return $this->getByType($image_tag['type'], $data, $attributes);
    }

    public function getImageByUrl($url, &$attributes)
    {
        $image = ['url' => $url];

        $this->prepareImageUrl($image['url'], $attributes);

        $this->setResizedImage($image, $attributes);

        return $image;
    }

    protected static function crossFillAltAndTitle(&$attributes)
    {
        $params = Params::get();

        if ( ! $params->image_titles_cross_fill)
        {
            return;
        }

        if (empty($attributes->alt) && ! empty($attributes->title))
        {
            $attributes->alt = $attributes->title;
        }

        if (empty($attributes->title) && ! empty($attributes->alt))
        {
            $attributes->title = $attributes->alt;
        }
    }

    protected static function getCleanFileName($url)
    {
    }

    protected static function getCleanTitle($url)
    {
    }

    protected static function getImageHtmlWithAttributes($tag, $attributes)
    {
        if (empty($attributes))
        {
            return $tag;
        }

        $attributes = (object) $attributes;

        $outer_class = $attributes->{'outer-class'} ?? '';
        unset($attributes->{'outer-class'});

        $tag_attributes = RL_HtmlTag::getAttributes($tag);

        $tag_attributes = (object) array_merge($tag_attributes, (array) $attributes);

        $image = '<img ' . RL_HtmlTag::flattenAttributes($tag_attributes) . ' />';

        if ( ! $outer_class)
        {
            return $image;
        }

        return '<div class="' . htmlspecialchars($outer_class) . '">'
            . $image
            . '</div>';
    }

    protected static function getTitleFromArticle()
    {
    }

    protected static function getTitleFromCategory()
    {
    }

    protected static function getTitleFromCustomField($field)
    {
    }

    protected static function getTitleFromFile($url, $attributes)
    {
    }

    protected function calculateWidthHeight($image, &$attributes)
    {
    }

    protected function filterImages(&$images, $filter = '')
    {
    }

    protected function getArticleImageAttributeByType($key, $type, $attributes)
    {
        $img_tag = $this->getArticleImageTagByType($type, $attributes);

        return $this->getImageAttribute($key, $img_tag);
    }

    protected function getArticleImageDataByType($type, $data, $attributes)
    {
        $type = $type == 'fulltext' ? 'fulltext' : 'intro';

        switch ($data)
        {
            case 'url':
                return $this->getArticleImageUrlByType($type, $attributes);

            case 'caption':
                return $this->item->getFromGroup('images', 'image_' . $type . '_caption');

            case '':
                return $this->getArticleImageTagByType($type, $attributes);

            default:
                return $this->getArticleImageAttributeByType($data, $type, $attributes);
        }
    }

    protected function getArticleImageTagByType($type, $attributes)
    {
        $url = $this->getArticleImageUrlByType($type, $attributes);

        if (empty($url))
        {
            return '';
        }

        $layout = $attributes->layout ?? '';
        unset($attributes->layout);

        $float   = $this->item->getFromGroup('images', 'float_' . $type);
        $alt     = $this->item->getFromGroup('images', 'image_' . $type . '_alt');
        $caption = $this->item->getFromGroup('images', 'image_' . $type . '_caption');

        $attributes->src   = $url;
        $attributes->alt   ??= $alt;
        $attributes->title ??= $caption;
        $attributes->class ??= 'item-image-' . $type;

        self::setAltAndTitle($type, $attributes);

        if ($layout == 'true')
        {
            $layout = 'joomla.content.' . ($type == 'fulltext' ? 'full' : $type) . '_image';
        }

        if (empty($layout) || $layout == 'false')
        {
            return $this->getImageHtml($attributes);
        }

        if ( ! class_exists('ContentModelArticle'))
        {
            require_once JPATH_SITE . '/components/com_content/models/article.php';
        }

        if ( ! class_exists('ContentHelperRoute'))
        {
            require_once JPATH_SITE . '/components/com_content/helpers/route.php';
        }

        $model = JModel::getInstance('article', 'contentModel');

        if ( ! method_exists($model, 'getItem'))
        {
            return null;
        }

        $item = $model->getItem($this->item->get('id'));

        $item->slug        = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
        $item->catslug     = $item->category_alias ? ($item->catid . ':' . $item->category_alias) : $item->catid;
        $item->parent_slug = $item->parent_alias ? ($item->parent_id . ':' . $item->parent_alias) : $item->parent_id;

        if ($item->parent_alias === 'root')
        {
            $item->parent_slug = null;
        }

        $item->readmore_link = JRoute::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language));

        $item->images = json_encode(array_merge((array) $attributes, [
            'image_' . $type              => $url,
            'image_' . $type . '_alt'     => $attributes->alt,
            'image_' . $type . '_title'   => $attributes->title,
            'image_' . $type . '_caption' => $caption,
            'float_' . $type              => $float,
            'image'                       => $url,
            'image_alt'                   => $attributes->alt,
            'image_title'                 => $attributes->title,
            'image_caption'               => $caption,
            'float'                       => $float,
        ]));

        return JLayoutHelper::render($layout, $item);
    }

    protected function getArticleImageUrlByType($type, &$attributes)
    {
        $url = $this->item->getFromGroup('images', 'image_' . $type);

        if (empty($url))
        {
            return '';
        }

        $image = ['url' => $url];

        $this->prepareImageUrl($image['url'], $attributes);


        return $image['url'];
    }

    protected function getByType($type, $data, $attributes)
    {
        switch ($type)
        {
            case 'intro':
                return $this->getIntroImage($data, $attributes);

            case 'fulltext':
                return $this->getFulltextImage($data, $attributes);


            default:

                return '';
        }
    }

    protected function getCategoryImage($data, $attributes)
    {
    }

    protected function getCategoryImageAttribute($key, $params, $attributes)
    {
    }

    protected function getCategoryImageTag($params, $attributes)
    {
    }

    protected function getCategoryImageUrl($params, &$attributes)
    {
    }

    protected function getContentImage($count, $data, $attributes)
    {
    }

    protected function getContentImageAttribute($key, $image, $attributes)
    {
    }

    protected function getContentImageCount()
    {
    }

    protected function getContentImageTag($image, $attributes)
    {
    }

    protected function getContentImageThumbTag($image, $attributes)
    {
    }

    protected function getContentImageThumbUrl($image)
    {
    }

    protected function getContentImageUrl($image, &$attributes)
    {
    }

    protected function getContentImages()
    {
    }

    protected function getDimensionsFromTag($image)
    {
    }

    protected function getFulltextImage($data, $attributes)
    {
        return $this->getArticleImageDataByType('fulltext', $data, $attributes);
    }

    protected function getImageAttribute($key, $html)
    {
        $tag_attributes = RL_HtmlTag::getAttributes($html);

        if (isset($tag_attributes[$key]))
        {
            return $tag_attributes[$key];
        }

        if ( ! in_array($key, ['width', 'height']))
        {
            return '';
        }

        $url = $tag_attributes['src'];

        if (RL_File::isExternal($url))
        {
            return '';
        }

        $dimensions = RL_Image::getDimensions($url);

        return $dimensions->{$key} ?? '';
    }

    protected function getIntroImage($data, $attributes)
    {
        return $this->getArticleImageDataByType('intro', $data, $attributes);
    }

    protected function getResizeMethod()
    {
    }

    protected function isEnabledResizeFiletype($image)
    {
    }

    protected function prepareImageUrl(&$url, $attributes)
    {
        if (isset($attributes->suffix))
        {
            $url = RL_RegEx::replace(
                '\.[a-z]*$',
                $attributes->suffix . '\0',
                $url
            );
            unset($attributes->suffix);
        }
    }

    protected function setResizedImage(&$image, &$attributes)
    {
    }

    protected function setWidthHeight(&$attributes)
    {
    }

    protected function shouldResize(&$image, $attributes)
    {
    }
}
