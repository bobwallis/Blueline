<?php
namespace Blueline\Helpers;

function arrays_equal_in_some_rotation($array1, $array2)
{
    if (count($array1) != count($array2)) {
        return false;
    }
    $same = $array1 == $array2;
    for ($i = 0; !$same && $i < count($array1) + 1; ++$i) {
        array_push($array2, array_shift($array2));
        $same = $array1 == $array2;
    }
    return $same;
}
