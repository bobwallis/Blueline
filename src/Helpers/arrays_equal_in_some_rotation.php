<?php

namespace Blueline\Helpers;

/**
 * Check if two arrays are equal under some cyclic rotation.
 *
 * Useful for comparing sequences that may have different start points but identical
 * cyclic structure (e.g., rotations of a lead head pattern).
 *
 * Example: [1,2,3] == [2,3,1] under rotation
 *
 * @param array $array1 First array
 * @param array $array2 Second array
 * @return bool True if arrays are identical under some rotation, false otherwise
 */
function arrays_equal_in_some_rotation(array $array1, array $array2): bool
{
    if (count($array1) != count($array2)) {
        return false;
    }

    // Fast path before rotating.
    $same = $array1 == $array2;

    // Rotate $array2 through all positions and test for equality each time.
    for ($i = 0; !$same && $i < count($array1) + 1; ++$i) {
        array_push($array2, array_shift($array2));
        $same = $array1 == $array2;
    }

    return $same;
}
