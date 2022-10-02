<?php
namespace Blueline\Helpers;

/**
 * A helper for working with method stages
 * @package Blueline
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */

class Stages
{
    /**
     * @access private
     */
    private static $_numberToStage = array(
        2  =>    'Two',
        3  =>    'Singles',
        4  =>    'Minimus',
        5  =>    'Doubles',
        6  =>    'Minor',
        7  =>    'Triples',
        8  =>    'Major',
        9  =>    'Caters',
        10 =>    'Royal',
        11 =>    'Cinques',
        12 =>    'Maximus',
        13 =>    'Sextuples',
        14 =>    'Fourteen',
        15 =>    'Septuples',
        16 =>    'Sixteen',
        17 =>    'Octuples',
        18 =>    'Eighteen',
        19 =>    'Nineteen',
        20 =>    'Twenty',
        21 =>    'Twenty-one',
        22 =>    'Twenty-two',
    );

    /**
     * Return array of all stages
     * @return array
     */
    public static function toArray()
    {
        return self::$_numberToStage;
    }

    /**
     * Converts an integer (or a valid string representation) into a string representation
     * @param  integer|string $i
     * @return string|boolean
     */
    public static function toString($i)
    {
        if (is_int($i) || intval($i) != 0) {
            $i = intval($i);
            if (isset(self::$_numberToStage[$i])) {
                return self::$_numberToStage[$i];
            }
        } elseif (is_string($i) && in_array(ucwords(strtolower($i)), self::$_numberToStage)) {
            return ucwords(strtolower($i));
        }

        return false;
    }

    /**
     * Converts a string representation (or an integer) into an integer
     * @param  string|integer  $s
     * @return integer|boolean
     */
    public static function toInt($s)
    {
        if ((is_int($s) && isset(self::$_numberToStage[$s])) || isset(self::$_numberToStage[intval($s)])) {
            return intval($s);
        } elseif (is_string($s)) {
            $s = ucwords(strtolower($s));
            if (array_search($s, self::$_numberToStage)) {
                return array_search($s, self::$_numberToStage);
            }
        }

        return false;
    }
};
