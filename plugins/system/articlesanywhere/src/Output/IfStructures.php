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

use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\Condition\Php as RL_Php;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\Collection\Item;
use RegularLabs\Plugin\System\ArticlesAnywhere\Config;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\ValueHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\Output\Data\Numbers;
use RegularLabs\Plugin\System\ArticlesAnywhere\Params;

class IfStructures extends OutputObject
{
    private $php;

    public function __construct(Config $config, Item $item, Numbers $numbers)
    {
        parent::__construct($config, $item, $numbers);

        $this->php = new RL_Php;
    }

    public function handle(&$content)
    {
        [$tag_start, $tag_end] = Params::getTagCharacters();

        $regex_if_structure = RL_RegEx::quote($tag_start) . 'if[\: ].*?' . RL_RegEx::quote($tag_start) . '/if' . RL_RegEx::quote($tag_end);

        RL_RegEx::matchAll($regex_if_structure, $content, $structures);

        if (empty($structures))
        {
            return;
        }

        foreach ($structures as $structure)
        {
            $output = $this->getStructureOutput($structure);

            if (is_null($output))
            {
                continue;
            }

            // replace if block with the IF output
            $content = RL_String::replaceOnce($structure[0], $output, $content);
        }
    }

    protected function calculate($string)
    {
        if ( ! RL_RegEx::match('[0-9]+\s*[\-\+\/\*\%]', $string))
        {
            return $string;
        }

        ob_start();
        $result = (new RL_Php)->execute('return ' . $string);
        ob_end_clean();

        return 0 + $result;
    }

    protected function getCalculatedValue($string)
    {
        $date = ValueHelper::placeholderToDate($string);

        if ($date)
        {
            return $date;
        }

        if (RL_RegEx::match('^[a-z0-9_\-]+\:[a-z0-9_\-\:]+$', $string))
        {
            return $this->values->get($string, '', (object) ['output' => 'raw']);
        }

        if (strpos($string, ' ') === false)
        {
            return $string;
        }

        $values = explode(' ', $string);

        if (count($values) < 3)
        {
            return $string;
        }

        foreach ($values as &$value)
        {
            if ( ! RL_String::is_key($value))
            {
                continue;
            }

            $value = $this->values->get($value, $value, (object) ['output' => 'raw']);
        }

        $value = implode(' ', $values);

        return $this->calculate($value);
    }

    protected function getResult(&$statements)
    {
        foreach ($statements as $statement)
        {
            if ( ! $this->pass($statement))
            {
                continue;
            }

            return $statement['content'];
        }

        return '';
    }

    protected function pass($statement)
    {
        $keyword    = trim($statement['keyword']);
        $expression = trim($statement['expression']);

        if ($keyword == 'else' && $expression == '')
        {
            return true;
        }

        if ($expression == '')
        {
            return false;
        }

        $expression = RL_String::html_entity_decoder($expression);
        $expression = str_replace(
            [' AND ', ' OR '],
            [' && ', ' || '],
            $expression
        );

        $pass = false;

        $ands = explode(' && ', $expression);

        foreach ($ands as $and_part)
        {
            $ors = explode(' || ', $and_part);
            foreach ($ors as $condition)
            {
                $pass = $this->passCondition($condition);

                if ($pass)
                {
                    break;
                }
            }

            if ( ! $pass)
            {
                break;
            }
        }

        return $pass;
    }

    protected function passArray($haystack, $needle, $reverse = 0)
    {
        if (is_null($haystack))
        {
            return false;
        }

        if ( ! is_array($haystack))
        {
            $haystack = explode(',', str_replace(', ', ',', $haystack));
        }

        if ( ! is_array($haystack))
        {
            return false;
        }

        $pass = false;
        foreach ($haystack as $string)
        {
            $pass = $this->passString($string, $needle);

            if ($pass)
            {
                break;
            }
        }

        return $reverse ? ! $pass : $pass;
    }

    protected function passCompare($haystack, $needle, $operator)
    {
        switch ($operator)
        {
            case '<':
                return $haystack < $needle;

            case '<=':
                return $haystack <= $needle;

            case '>':
                return $haystack > $needle;

            case '>=':
                return $haystack >= $needle;

            default:
                return false;
        }
    }

    protected function passCondition($condition)
    {
        $condition = trim($condition);

        /*
        * In array syntax
        * 'bar' IN foo
        * 'bar' !IN foo
        * 'bar' NOT IN foo
        */
        if (RL_RegEx::match('^(?<quotes>[\'"]?)(?<val>.*?)[\'"]?\s+(?<operator>(?:NOT\s+)?\!?IN)\s+(?<key>[a-zA-Z0-9-_\:]+)$', $condition, $match))
        {
            $key     = $this->values->get($match['key'], null, (object) ['output' => 'raw']);
            $value   = $match['val'];
            $reverse = ($match['operator'] == 'NOT IN' || $match['operator'] == '!NOT');

            if (empty($match['quotes']))
            {
                $value = $this->values->get($match['val'], $match['val'], (object) ['output' => 'raw']);
            }

            return $this->passArray($key, $value, $reverse);
        }

        /*
        * In array syntax
        * foo IN ['bar', 'baz']
        * foo NOT IN ['bar', 'baz']
        */
        if (RL_RegEx::match('^(?<key>[a-zA-Z0-9-_\:]+)\s+(?<operator>(?:NOT\s+)?\!?IN)\s+\[(?<val>[^\]]*)\]$', $condition, $match))
        {
            $key         = $this->values->get($match['key'], null, (object) ['output' => 'raw']);
            $orig_values = RL_Array::toArray($match['val']);

            $reverse = ($match['operator'] == 'NOT IN' || $match['operator'] == '!NOT');

            $values = [];
            foreach ($orig_values as $value)
            {
                if (empty($value))
                {
                    continue;
                }

                if ($value[0] == '"' || $value[0] == "'")
                {
                    $values[] = trim($value, '\'"');
                    continue;
                }

                $value = $this->values->get($value, $value, (object) ['output' => 'raw']);
                if ( ! is_array($value))
                {
                    $value = [$value];
                }

                $values = array_merge($values, $value);
            }

            foreach ($values as $value)
            {
                if ($this->passArray($key, $value))
                {
                    return ! $reverse;
                }
            }

            return $reverse;
        }

        /*
        * String comparison syntax:
        * foo = 'bar'
        * foo != 'bar'
        */
        if (RL_RegEx::match('^(?<key>[a-z0-9-_\:]+)\s*(?<operator>\!?=)=*\s*(?<quotes>[\'"]?)(?<val>.*?)[\'"]?$', $condition, $match))
        {
            $key     = $this->values->get($match['key'], null, (object) ['output' => 'raw']);
            $value   = $match['val'];
            $reverse = ($match['operator'] == '!=');

            if (empty($match['quotes']))
            {
                $value = $this->values->get($match['val'], $match['val'], (object) ['output' => 'raw']);
            }

            return $this->passArray($key, $value, $reverse);
        }

        /*
        * Lesser/Greater than comparison syntax:
        * foo < bar
        * foo > bar
        * foo <= bar
        * foo >= bar
        */
        if (RL_RegEx::match('^(?<key>[a-z0-9-_\:]+)\s*(?<operator>>=?|<=?)=*\s*[\'"]?(?<val>.*?)[\'"]?$', $condition, $match))
        {
            $key   = $this->values->get($match['key'], null, (object) ['output' => 'raw']);
            $value = $this->getCalculatedValue($match['val']);

            return $this->passCompare($key, $value, $match['operator']);
        }

        /*
        * Variable check syntax:
        * foo (= not empty)
        * !foo (= empty)
        */
        if (RL_RegEx::match('^(?<operator>\!?)(?<key>[a-z0-9-_\:]+)$', $condition, $match))
        {
            $reverse = ($match['operator'] == '!');

            return $this->passSimple(
                $this->values->get($match['key'], null, (object) ['output' => 'raw']),
                $reverse
            );
        }

        return $this->passPHP($condition);
    }

    protected function passPHP($statement)
    {
        $php = RL_String::html_entity_decoder($statement);
        $php = RL_RegEx::replace('([^<>])=([^<>])', '\1==\2', $php);

        // replace keys with $article->key
        $php = '$article->' . RL_RegEx::replace('\s*(&&|&&|\|\|)\s*', ' \1 $article->', $php);

        // fix negative keys from $article->!key to !$article->key
        $php = str_replace('$article->!', '!$article->', $php);

        $numbers = $this->numbers->getAll();

        // replace back data variables
        foreach ($numbers as $key => $val)
        {
            $php = str_replace('$article->' . $key, (int) $val, $php);
        }

        $php = str_replace('$article->empty', (int) ($this->numbers->get('count') > 0), $php);

        // Place statement in return check
        $php = 'return ( ' . $php . ' ) ? true : false;';

        // Trim the text that needs to be checked and replace weird spaces
        $php = RL_RegEx::replace(
            '(\$article->[a-z0-9-_]*)',
            'trim(str_replace(chr(194) . chr(160), " ", \1))',
            $php
        );

        // Fix extra-1 field syntax: $article->extra-1 to $article->{'extra-1'}
        $php = RL_RegEx::replace(
            '->(extra-[a-z0-9]+)',
            '->{\'\1\'}',
            $php
        );

        return $this->php->execute($php);
    }

    protected function passSimple($haystack, $reverse = 0)
    {
        if (is_null($haystack))
        {
            return false;
        }

        $pass = ! empty($haystack);

        return $reverse ? ! $pass : $pass;
    }

    protected function passString($haystack, $needle)
    {
        if ( ! is_string($haystack) && ! is_string($needle)
            && ! is_numeric($haystack)
            && ! is_numeric($needle)
        )
        {
            return false;
        }

        // Simple string comparison
        if (strpos($needle, '*') === false && strpos($needle, '+') === false)
        {
            return strtolower($haystack) == strtolower($needle);
        }

        // Using wildcards
        $needle = RL_RegEx::quote($needle);
        $needle = str_replace(
            ['\\\\\\*', '\\*', '[:asterisk:]', '\\\\\\+', '\\+', '[:plus:]'],
            ['[:asterisk:]', '.*', '\\*', '[:plus:]', '.+', '\\+'],
            $needle
        );

        return RL_RegEx::match($needle, $haystack);
    }

    private function getStructureOutput($structure)
    {
        [$tag_start, $tag_end] = Params::getTagCharacters();

        RL_RegEx::matchAll(
            $tag_start
            . '(?<keyword>if|else ?if|else)'
            . '(?:[\: ](?<expression>.+?))?'
            . $tag_end
            . '(?<content>.*?)'
            . '(?=' . $tag_start . '(?:else|\/if))',
            $structure[0],
            $statements
        );

        if (empty($statements))
        {
            return null;
        }

        return $this->getResult($statements);
    }

}
