<?php
namespace Blueline\MethodsBundle\Helpers;

/**
 * Functions to assist working with method classifications
 * @package Helpers
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class Classifications
{
    /**
     * @access private
     */
    private static $_classifications = array(
        'Alliance',
        'Bob',
        'Delight',
        'Hybrid',
        'Place',
        'Surprise',
        'Slow Course',
        'Treble Bob',
        'Treble Place',
    );

    /**
     * Return array of all classifications
     * @return array
     */
    public static function toArray()
    {
        return self::$_classifications;
    }

    /**
     * Test whether a string corresponds to a class
     * @param  string  $test
     * @return boolean
     */
    public static function isClass($test)
    {
        return (array_search(ucwords(strtolower($test)), self::$_classifications) !== false);
    }
};
