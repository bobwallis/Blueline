<?php
namespace Blueline\Helpers;

/**
 * Compare two indexed arrays for equality under cyclic rotation.
 *
 * This is used where representations can have different start points but the
 * same relative ordering (e.g. rotations of a cycle).
 */
function arrays_equal_in_some_rotation($array1, $array2)
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
