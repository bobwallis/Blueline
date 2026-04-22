<?php

namespace Blueline\Helpers;

/**
 * Utilities for bell-ringing method classifications.
 *
 * Classifications categorise methods by their distinctive structure and properties.
 * Standard classifications include: Place, Treble Bob, Surprise, Treble Place, Delight, etc.
 * Each method has exactly one classification.
 */
class Classifications
{
    private static $_classifications = [
        'Alliance',
        'Bob',
        'Delight',
        'Hybrid',
        'Place',
        'Surprise',
        'Treble Bob',
        'Treble Place',
    ];

    /**
     * Return array of all classifications.
     *
     * @return array
     */
    public static function toArray()
    {
        return self::$_classifications;
    }

    /**
     * Test whether a string corresponds to a class.
     *
     * @param string $test
     *
     * @return bool
     */
    public static function isClass($test)
    {
        return false !== array_search(ucwords(strtolower($test)), self::$_classifications);
    }
}
