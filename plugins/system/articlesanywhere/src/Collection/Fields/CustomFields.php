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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\Collection\Fields;

defined('_JEXEC') or die;

use PlgFieldsArticlesHelper;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\DB as RL_DB;
use RegularLabs\Plugin\System\ArticlesAnywhere\Collection\DB;
use RegularLabs\Plugin\System\ArticlesAnywhere\CurrentArticle;

class CustomFields extends Fields
{
    public static function getByName($fields, $name, $item = null)
    {
        foreach ($fields as $field)
        {
            if ($field->name != $name)
            {
                continue;
            }

            if (empty($field->categories) || is_null($item))
            {
                return $field;
            }

            if ( ! in_array($item->get('catid'), $field->categories))
            {
                return false;
            }

            return $field;
        }

        return false;
    }

    public function getAvailableFields()
    {
        $id = $this->config->getContext();

        if (isset(self::$available_fields[$id]))
        {
            return self::$available_fields[$id];
        }

        if ( ! RL_DB::tableExists($this->config->getTableFields(false)))
        {
            return [];
        }

        $fields = $this->getAvailableFieldsFromDB();

        foreach ($fields as &$field)
        {
            $field->categories = $this->getCategoriesByFieldId($field->id);
        }

        self::$available_fields[$id] = $fields;

        return self::$available_fields[$id];
    }

    public function getAvailableFieldsFromDB()
    {
        $query = $this->db->getQuery(true)
            ->select($this->config->get('fields_id'))
            ->select($this->config->get('fields_name'))
            ->select($this->config->get('fields_type'))
            ->from($this->config->getTableFields())
            ->where($this->db->quoteName('context') . ' = ' . $this->db->quote($this->config->getContext()))
            ->where($this->config->get('fields_state') . ' = 1');

        return DB::getResults($query, 'loadObjectList', ['id']) ?: [];
    }

    public function getFieldByKey($key, $item = null)
    {
        $custom_fields = $this->getAvailableFields();

        $field = self::getByName($custom_fields, $key, $item);

        if ( ! $field)
        {
            return ! empty(self::getByName($custom_fields, $key));
        }

        return $this->getFieldFromDatabase($field->id, ! is_null($item) ? $item->getId() : 0);
    }

    public function getFieldValue($key, $value)
    {
        $current_value = $this->getFieldValueByKey($key);

        return $this->getValue($key, $value, $current_value);
    }

    public function getFieldValueByKey($key, $item = null)
    {
        $custom_fields = $this->getAvailableFields();

        $field = self::getByName($custom_fields, $key, $item);

        if ( ! $field)
        {
            return false;
        }

        return $this->getFieldValueFromDatabase($field, ! is_null($item) ? $item->getId() : 0);
    }

    protected function applyOrdering(&$values, $field)
    {
        if (empty($values))
        {
            return;
        }

        // if value is a json string, don't try to apply ordering
        if (is_string($values) && $values[0] == '{')
        {
            return;
        }

        if (empty($field->fieldparams))
        {
            return;
        }

        $fieldparams = json_decode($field->fieldparams);

        // check if field type = articles and apply ordering based on database query
        if ($field->type == 'articles')
        {
            self::applyOrderingFromArticlesField($values, $fieldparams);
        }

        // check if field contains options (in fieldparams) and order based on those values
        if ( ! empty($fieldparams->options))
        {
            self::applyOrderingFromFieldOptions($values, $fieldparams->options);
        }
    }

    protected function applyOrderingFromArticlesField(&$values, $fieldparams)
    {
        if ( ! is_file(JPATH_PLUGINS . '/fields/articles/helper.php'))
        {
            return;
        }

        require_once JPATH_PLUGINS . '/fields/articles/helper.php';

        if ( ! method_exists('\PlgFieldsArticlesHelper', 'getFullOrdering'))
        {
            return;
        }

        $primary_ordering    = ($fieldparams->articles_ordering ?? null) ?: 'title';
        $primary_direction   = ($fieldparams->articles_ordering_direction ?? null) ?: 'ASC';
        $secondary_ordering  = ($fieldparams->articles_ordering_2 ?? null) ?: 'created';
        $secondary_direction = ($fieldparams->articles_ordering_direction_2 ?? null) ?: 'DESC';

        $ordering = PlgFieldsArticlesHelper::getFullOrdering($primary_ordering, $primary_direction, $secondary_ordering, $secondary_direction);

        $query = $this->db->getQuery(true)
            ->from($this->db->quoteName('#__content', 'a'))
            ->select('a.id')
            ->where($this->db->quoteName('a.id') . RL_DB::in($values))
            ->join('LEFT', $this->db->quoteName('#__categories', 'c') . ' ON c.id = a.catid')
            ->order($ordering);

        $this->db->setQuery($query);

        $values = $this->db->loadColumn();
    }

    protected function applyOrderingFromFieldOptions(&$values, $options)
    {
        $ordered = [];

        $values = RL_Array::toArray($values);

        foreach ($options as $option)
        {
            if ( ! isset($option->value))
            {
                continue;
            }

            if ( ! in_array($option->value, $values))
            {
                continue;
            }

            $ordered[] = $option->value;
        }

        $values = $ordered;
    }

    protected function getFieldFromDatabase($field_id, $item_id)
    {
        if ( ! RL_DB::tableExists($this->config->getTableFieldsValues(false)))
        {
            return false;
        }

        if (empty($field_id))
        {
            return false;
        }

        $field = $this->getFieldObjectFromDatabase($field_id);

        $values = $this->getFieldValuesFromDatabase($field_id, $item_id);

        self::applyOrdering($values, $field);

        if (
            (is_string($values) && $values == '')
            || ( ! is_string($values) && empty($values))
        )
        {
            $field->value = $field->default;

            return [$field];
        }

        $fields = [];

        if ( ! is_array($values))
        {
            $values = [$values];
        }

        foreach ($values as $value)
        {
            $field->value = $value;
            $fields[]     = clone $field;
        }

        return $fields;
    }

    protected function getFieldObjectFromDatabase($field_id)
    {
        if (isset(self::$fields[$field_id]))
        {
            return self::$fields[$field_id];
        }

        $query = $this->db->getQuery(true)
            ->select(
                [
                    $this->db->quoteName('id'),
                    $this->config->get('fields_label', 'label'),
                    $this->config->get('fields_type', 'type'),
                    $this->config->get('fields_params', 'params'),
                    $this->config->get('fields_field_params', 'fieldparams'),
                    $this->config->get('fields_default', 'default'),
                ])
            ->from($this->config->getTableFields('fields'))
            ->where($this->db->quoteName('id') . ' = ' . (int) $field_id);
        $this->db->setQuery($query);
        self::$fields[$field_id] = DB::getResults($query, 'loadObject');

        return self::$fields[$field_id];
    }

    protected function getFieldType($field_id)
    {
        if (isset(self::$field_types[$field_id]))
        {
            return self::$field_types[$field_id];
        }

        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('type'))
            ->from($this->config->getTableFields())
            ->where($this->db->quoteName('id') . ' = ' . (int) $field_id);
        $this->db->setQuery($query);

        self::$field_types[$field_id] = DB::getResults($query, 'loadResult');

        return self::$field_types[$field_id] ?: '';
    }

    protected function getFieldValueFromDatabase($field, $item_id)
    {
        if ( ! RL_DB::tableExists($this->config->getTableFieldsValues(false)))
        {
            return false;
        }

        if (empty($field->id))
        {
            return false;
        }

        $values = $this->getFieldValuesFromDatabase($field->id, $item_id);

        self::applyOrdering($values, $field);

        return $values;
    }

    protected function getFieldValuesFromDatabase($field_id, $item_id)
    {
        if ( ! RL_DB::tableExists($this->config->getTableFieldsValues(false)))
        {
            return false;
        }

        if (empty($field_id))
        {
            return false;
        }

        $id = $item_id . '.' . $field_id;

        if (isset(self::$field_values[$id]))
        {
            return self::$field_values[$id];
        }

        $item_id = $item_id ?: CurrentArticle::get('id', $this->config->getComponentName());

        $query = $this->db->getQuery(true)
            ->select($this->config->get('fields_values_value', 'value'))
            ->from($this->config->getTableFieldsValues('values'))
            ->where($this->config->get('fields_values_id') . ' = ' . (int) $field_id)
            ->where($this->config->get('fields_values_item_id') . ' = ' . $this->db->quote($item_id));

        $this->db->setQuery($query);

        $result = DB::getResults($query);

        self::$field_values[$id] = $this->normalizeFieldValue($result);

        return self::$field_values[$id];
    }

    protected function normalizeFieldValue($value)
    {
        if (empty($value))
        {
            return '';
        }

        if (is_array($value) && count($value) == 1)
        {
            return $value[0];
        }

        return $value;
    }

    private function getCategoriesByFieldId($field_id)
    {
        $query = $this->db->getQuery(true)
            ->select('a.category_id')
            ->from($this->db->quoteName('#__fields_categories', 'a'))
            ->where('a.field_id = ' . (int) $field_id);

        $this->db->setQuery($query);

        $categories       = $this->db->loadColumn();
        $child_categories = $this->getChildCategories($categories);

        return array_merge($categories, $child_categories);
    }

    private function getChildCategories($categories)
    {
        if (empty($categories))
        {
            return [];
        }

        $query = $this->db->getQuery(true)
            ->select('a.id')
            ->from($this->db->quoteName('#__categories', 'a'))
            ->where('a.parent_id' . RL_DB::in($categories));

        $this->db->setQuery($query);

        $child_categories = $this->db->loadColumn();

        if (empty($child_categories))
        {
            return [];
        }

        $sub_child_categories = $this->getChildCategories($child_categories);

        return array_merge($child_categories, $sub_child_categories);
    }
}
