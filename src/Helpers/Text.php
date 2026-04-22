<?php

namespace Blueline\Helpers;

/**
 * Text formatting and manipulation utilities.
 *
 * Provides helpers for formatting lists, pluralisation, and other text operations
 * commonly needed in templates and output.
 */
class Text
{
    /**
     * Concatenate a list into a single string, optionally using different glue for the final pieces.
     *
     * @param array  $list The list of items to concatenate
     * @param string $glue Optional, defaults to ', '
     * @param string $last Optional, defaults to ' and '
     *
     * @return string
     */
    public static function toList(array $list, $glue = ', ', $last = ' and ')
    {
        $list = array_filter($list);
        if (empty($list)) {
            return '';
        }
        if (count($list) > 1) {
            return implode($glue, array_slice($list, 0, -1)).$last.array_pop($list);
        }

        return array_pop($list);
    }

    /**
     * Returns a properly pluralised string.
     *
     * @param int    $count    The number to display and determine singular/plural form
     * @param string $singular The singular form of the word
     * @param string $plural   The pluralised form of the word. Optional, defaults to $singular.'s'
     *
     * @return string
     */
    public static function pluralise($count, $singular, $plural = false)
    {
        return $count.' '.((1 == $count) ? $singular : ($plural ?: $singular.'s'));
    }
}
