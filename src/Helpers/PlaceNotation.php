<?php
namespace Blueline\Helpers;

/**
 * Functions to assist working with place notation
 * @package Helpers
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class PlaceNotation
{
    /**
     * @access private
     */
    private static $_notationOrder = '1234567890ETABCDFGHJKLMNPQRSUVWYZ';

    /**
     * Converts an integer into the bell character
     * @param  integer        $int
     * @return string|boolean The character on success, false if out of range
     */
    public static function intToBell($int)
    {
        if ($int < 1 || $int > strlen(self::$_notationOrder)) {
            return false;
        }

        return substr(self::$_notationOrder, intval($int) - 1, 1);
    }

    /**
     * Converts a bell character into an integer
     * @param  string          $bell
     * @return integer|boolean The integer on success, false if out of range
     */
    public static function bellToInt($bell)
    {
        if (strlen($bell) > 1 || !strpbrk(self::$_notationOrder, $bell)) {
            return false;
        }

        return strpos(self::$_notationOrder, $bell) + 1;
    }

    /**
     * Returns a row containing rounds of a given stage
     * @param  integer $stage
     * @return array
     */
    public static function rounds($stage)
    {
        return array_map(array( 'Blueline\Helpers\PlaceNotation', 'intToBell' ), range(1, $stage));
    }

    /**
     * Tests whether two rows are equal
     * @param  array   $a
     * @param  array   $b
     * @return boolean
     */
    public static function rowsEqual($a, $b)
    {
        $i = count($a);
        if ($i != count($b)) {
            return false;
        }
        while ($i--) {
            if ($a[$i] != $b[$i]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Trims external places from a piece of notation
     * @param  string  $piece The piece of notation
     * @param  integer $stage The stage that the notation will be applied on
     * @return string  The trimmed notation
     */
    public static function trimExternalPlaces($piece, $stage)
    {
        $stageIsEven = ($stage%2 == 0);
        $stageNotation = self::intToBell($stage);

        if (substr($piece, 0, 1) == '1' && self::isEven(substr($piece, 1, 1))) {
            $piece = substr($piece, 1);
        }
        if ((substr($piece, -1) == $stageNotation) && (($stageIsEven && !self::isEven(substr($piece, -2, 1))) || (!$stageIsEven && self::isEven(substr($piece, -2, 1))))) {
            $piece = substr($piece, 0, -1);
        }

        return $piece;
    }

    /**
     * Applies permutations successively to a start array
     * @param  array $permutations
     * @param  array $start
     * @return array All of the permutations of $start
     */
    public static function apply(array $permutations, array $start)
    {
        if (!is_array($permutations[0])) {
            return self::permute($start, $permutations);
        }
        if (count($permutations[0]) != count($start)) {
            return array();
        }
        $result = array( self::permute($start, $permutations[0]) );
        for ($i = 1, $iLim = count($permutations); $i < $iLim; ++$i) {
            if (count($permutations[$i]) != count($result[$i-1])) {
                return array();
            }
            $result[$i] = self::permute($result[$i-1], $permutations[$i]);
        }

        return $result;
    }

    /**
     * Applies permutations successively to a start string
     * @param  array  $permutations
     * @param  string $start
     * @return array  All of the permutations of $start (as strings)
     */
    public static function applyToString(array $permutations, $start)
    {
        return array_map('implode', self::apply($permutations, str_split($start)));
    }

    /**
     * Permutes an array by a permutation
     * @param  array         $start
     * @param  array         $permutation
     * @return array|boolean The new permutation, or false if the permutation fails
     */
    public static function permute(array $start, array $permutation)
    {
        if (empty($start) || empty($permutation)) {
            return $start;
        }
        if (count($start) != count($permutation)) {
            return false;
        }
        $end = array();
        for ($i = 0, $iLim = count($permutation); $i < $iLim; ++$i) {
            $end[$i] = $start[$permutation[$i]];
        }

        return $end;
    }

    /**
     * Permutes a string by a permutation
     * @param  string         $start
     * @param  array          $permutation
     * @return string|boolean The new permutation, or false if the permutation fails
     */
    public static function permuteString($start, array $permutation)
    {
        return implode(self::permute(str_split($start), $permutation) ?: array()) ?: false;
    }

    /**
     * Guesses stage using place notation
     * @param  string         $notation
     * @return integer        The stage
     */
    public static function guessStage($notation)
    {
        // Remove anything inside brackets, or appended fch details (arise when people copy notation from somewhere with extra information at the start or end)
        $notationFull = preg_replace(array( '/[\[{\<].*[\]}\>]/', '/ FCH.*$/' ), '', $notation);
        // Then guess
        return max(array_map(function ($c) {
            return PlaceNotation::bellToInt($c);
        }, array_filter(str_split(str_replace(array(' HL ', 'LE', 'LH'), '', $notationFull)), function ($c) {
            return preg_match('/[0-9A-Z]/', $c);
        })));
    }

    /**
     * Expands a shortened place notation format into a fully expanded one
     * @param  string  $notation
     * @param  integer $stage
     * @return string
     */
    public static function expand($notation, $stage = null)
    {
        // If stage isn't given, try to guess it
        if (is_null($stage) || $stage < 3) {
            $stage = self::guessStage($notation);
        }

        // Tidy up letter cases
        $notationFull = strtoupper($notation);
        $notationFull = str_replace('X', 'x', $notationFull);

        // Just in case people feel the need to use this kind of notation in their input, get rid of it here in case it causes errors later on
        $notationFull = str_replace(array( '.x.', 'x.', '.x'), 'x', $notationFull);
        $notationFull = str_replace(array( '.-.', '-.', '.-'), '-', $notationFull);

        // Remove anything inside brackets, or appended fch details (arise when people copy notation from somewhere with extra information at the start or end)
        $notationFull = preg_replace(array( '/[\[{\<].*[\]}\>]/', '/ FCH.*$/' ), '', $notationFull);

        // Deal with notation like 'x1x1x1-2' (After checking for this form we can assume - means x)
        if (preg_match('/^([^-]+)-([^-\.,x]+)$/', $notationFull, $match) == 1) {
            // if there's only one -, and it's got one change after it...
            $notationFull = self::expandHalf($match[1]).'.'.$match[2];
        }
        $notationFull = str_replace('-', 'x', $notationFull);

        // Turn notation like ...x34 hl 16 le 12 into ...x34.16 le 12
        if (strpos($notationFull, ' HL ') !== false) {
            $notationFull = str_replace(' HL ', '.', $notationFull);
        }

        // Deal with notation like '-1-1-1LH2', or '-1-1-1 le2'. Allow a preceding ampersand
        if (preg_match('/^&?(.*)(LH|LE)/', $notationFull, $match) == 1) {
            $notationFull = str_replace($match[0], self::expandHalf($match[1]), $notationFull);
        }

        // Parse microSiril format notation
        if (strpos($notationFull, ',') !== false) {
            $splitOnComma = array_map('trim', explode(',', $notationFull));
            if (array_reduce($splitOnComma, function ($carry, $item) {
                return ($carry && ($item[0] == '+' || $item[0] == '&'));
            }, true)) {
                $notationFull = array_reduce($splitOnComma, function ($carry, $item) {
                    return $carry . '.' . ($item[0] == '&'? self::expandHalf($item) : trim($item, '+'));
                }, '');
            }
        }

        // Now we've checked for proper microSiril format we'll make some assumptions about what people might actually mean.
        // Deal with notation like '-1-1-1,2' or '3,1.5.1.5.1'
        if (preg_match('/^\s*&?\s*(.*),(.*)/', $notationFull, $match) == 1) {
            $notationFull = self::expandHalf($match[1]).'.'.self::expandHalf($match[2]);
            $notationFull = str_replace(array( '.x.', 'x.', '.x'), 'x', $notationFull);
        }

        // Get rid of +
        $notationFull = str_replace('+', ' ', $notationFull);

        // Convert 'a &-1-1-1' type notation into '&x1x1x1 2' type
        if (preg_match('/^[A-S][1-9]?\s/', $notationFull) == 1) {
            // For even bell methods
            if ($stage % 2 == 0) {
                // a to f is 12
                if (preg_match('/^([A-F][1-9]?)\s+(.*)$/', $notationFull, $match) == 1) {
                    $notationFull = $match[2].' 12';
                // g to m is 1n
                } elseif (preg_match('/^([G-M][1-9]?)\s+(.*)$/', $notationFull, $match) == 1) {
                    $notationFull = $match[2].' 1'.self::$_notationOrder[$stage-1];
                // p, q is 3n post lead head (if 3n isn't the start of $match[2] then add it to the start)
                } elseif (preg_match('/^([P-Q][1-9]?)\s+(.*)$/', $notationFull, $match) == 1) {
                    if (strpos($match[2], '3'.self::$_notationOrder[$stage-1]) === 0) {
                        $notationFull = $match[2];
                    } else {
                        $notationFull = '3'.self::$_notationOrder[$stage-1].' '.$match[2];
                    }
                }
                // r, s is n post lead head (similar to above)
                // I don't know why I think this... but it doesn't seem to be true looking at actual methods in the current collections.
                // Let me know if you know!
                //elseif (preg_match('/^([R-S][1-9]?)\s+(.*)$/', $notationFull, $match) == 1) {
                //    if (strpos($match[2], self::$_notationOrder[$stage-1]) === 0) {
                //        $notationFull = $match[2];
                //    } else {
                //        $notationFull = self::$_notationOrder[$stage-1].' '.$match[2];
                //    }
                //}
            // For odd bell methods
            } else {
                // a to f is 3 post lead head (if 3 isn't the start of $match[2] then add it to the start)
                if (preg_match('/^([A-F][1-9]?)\s+(.*)$/', $notationFull, $match) == 1) {
                    if (strpos($match[2], '3') === 0) {
                        $notationFull = $match[2];
                    } else {
                        $notationFull = '3 '.$match[2];
                    }
                // g to m is n post lead head (similar to above)
                } elseif (preg_match('/^([G-M][1-9]?)\s+(.*)$/', $notationFull, $match) == 1) {
                    if (strpos($match[2], self::$_notationOrder[$stage-1]) === 0) {
                        $notationFull = $match[2];
                    } else {
                        $notationFull = self::$_notationOrder[$stage-1].' '.$match[2];
                    }
                // p, q is 12n
                } elseif (preg_match('/^([P-Q][1-9]?)\s+(.*)$/', $notationFull, $match) == 1) {
                    $notationFull = $match[2].' 12'.self::$_notationOrder[$stage-1];
                // r, s is 1
                } elseif (preg_match('/^([R-S][1-9]?)\s+(.*)$/', $notationFull, $match) == 1) {
                    $notationFull = $match[2].' 1';
                }
            }
        // z is an irregular lead head
        } elseif (preg_match('/^(.*)Z\s+(.*)$/', $notationFull, $match) == 1) {
            $notationFull = $match[2].' '.$match[1];
        }

        // Deal with, '&x1x1x1 2' type notation
        if (preg_match('/^&(.*)\s+([^x.]+)$/', $notationFull, $match) == 1) {
            $notationFull = self::expandHalf($match[1]).' '.$match[2];
        }

        // Replace any remaining whitespace with dots
        $notationFull = preg_replace('/\s+/', '.', $notationFull);

        // Trim any trailing or preceding dots
        $notationFull = trim($notationFull, '.');

        // Remove any unecessary doubling up of dots and x
        $notationFull = preg_replace('/\.+/', '.', $notationFull);
        $notationFull = str_replace(array( '.x.', 'x.', '.x'), 'x', $notationFull);

        // Correct any ordering of bells in the notation that have arisen either from us mirroring things, or lazy input
        $notationFull = self::order($notationFull);

        // Explode notation
        $notationExploded = self::explode($notationFull);

        // Work through each piece of notation individually to do last bits of cleanup
        foreach ($notationExploded as &$split) {
            if ($split == 'x') {
                if ($stage%2 != 0) {
                    // If stage odd and we have an x, change to an n
                    $split = self::$_notationOrder[$stage-1];
                }
                continue;
            }
            // Work out which places are affected
            preg_match_all('/[A-Z\d]+/', $split, $pregOutput);
            $pregOutput = array_map('str_split', $pregOutput[0]);

            $affected = array();
            array_walk_recursive($pregOutput, function ($a) use (&$affected) { $affected[] = $a; });
            usort($affected, array( __CLASS__, 'bellOrder' ));
            // Then add missing external places
            // If the first bell is even, prepend a 1
            if (self::isEven($affected[0])) {
                $split = '1'.$split;
            }
            // If stage odd and last bell even, or stage even and last bell odd, append an n
            if (($stage%2 != 0 && self::isEven(end($affected))) || ($stage%2 == 0 && !self::isEven(end($affected)))) {
                $split = $split.self::intToBell($stage);
            }
        }

        // Implode the exploded notation, with added external places, back into string form
        return self::implode($notationExploded);
    }

    /**
     * Converts notation into microSiril format
     * @param  string  $notation
     * @param  integer $stage
     * @return string
     */
    public static function siril($notation, $stage = null)
    {
        // If stage isn't given, try to guess it
        if (is_null($stage) || $stage < 3) {
            $stage = self::guessStage($notation);
        }

        // Expand place notation, explode, then remove external places
        $expandedAndExploded = array_map(function ($pn) use ($stage) {
            if ($pn != 'x') {
                if (strlen($pn) > 1 &&
                    $pn[0] == '1' && PlaceNotation::bellToInt($pn[1])%2 ==0
                   ) {
                    $pn = substr($pn, 1);
                }
                if (strlen($pn) > 1 &&
                    $pn[strlen($pn)-1] == PlaceNotation::intToBell($stage) &&
                    PlaceNotation::bellToInt($pn[strlen($pn)-2])%2 != $stage%2
                   ) {
                    $pn = substr($pn, 0, strlen($pn)-1);
                }

            }
            return $pn;
        }, self::explode(self::expand($notation, $stage)));

        // Then find and reduce any palindromes
        $sirilise = function ($notation) use (&$sirilise) {
            // If at the end
            if (count($notation) < 3) {
                return '+'.PlaceNotation::implode($notation);
            }
            // Otherwise hunt for palindrome
            for ($palindromeLength = (count($notation)%2 == 0)? count($notation)-1 : count($notation); $palindromeLength >= 3; $palindromeLength -= 2) {
                for ($palindromeOffset = 0; $palindromeLength+$palindromeOffset <= count($notation); ++$palindromeOffset) {
                    $word = array_slice($notation, $palindromeOffset, $palindromeLength);
                    $reversedWord = array_reverse($word);
                    if ($word == $reversedWord) {
                        $result = '';
                        if ($palindromeOffset > 0) {
                            $result .= $sirilise(array_slice($notation, 0, $palindromeOffset)).', ';
                        }
                        $result .= '&'.PlaceNotation::implode(array_slice($word, 0, (count($word)+1)/2));
                        if ($palindromeOffset + $palindromeLength < count($notation)) {
                            $result .= ', '.$sirilise(array_slice($notation, $palindromeOffset+$palindromeLength));
                        }
                        return $result;
                    }
                }
            }
            return '+'.PlaceNotation::implode($notation);
        };
        return str_replace('x', '-', $sirilise( $expandedAndExploded ));
    }

    /**
     * Explodes (expanded) place notation into an array of single changes (as strings)
     * @param  string $notation
     * @return array
     */
    public static function explode($notation)
    {
        return array_values(array_filter(explode('.', str_replace('x', '.x.', $notation)), 'strlen'));
    }

    /**
     * Implodes an array of place notation chunks into a single string
     * @param  array  $notation
     * @return string
     */
    public static function implode(array $notation)
    {
        return str_replace(array( '.x.', 'x.', '.x'), 'x', implode('.', $notation));
    }

    /**
     * Converts exploded place notation into an array of relation notation permutations
     * @param  integer $stage
     * @param  array   $notationExploded
     * @return array
     */
    public static function explodedToPermutations($stage, $notationExploded)
    {
        // Remember that treble is 0, 10 is 9, and E is 10...
        $permutations = array();
        $i = 0;
        foreach ($notationExploded as $piece) {
            // Mark all static bells
            for ($j = 0; isset($piece[$j]) && $piece[$j] != 'x'; ++$j) {
                $pos = self::bellToInt($piece[$j]) - 1;
                $permutations[$i][$pos] = $pos;
            }
            // 'x' what's left
            for ($j = 0; $j < $stage; ++$j) {
                if (isset($permutations[$i][$j])) {
                    continue;
                }
                $permutations[$i][$j] = $j+1;
                $permutations[$i][$j+1] = $j;
            }
            ksort($permutations[$i]);
            ++$i;
        }

        return $permutations;
    }

    /**
     * Determines ordering for $a, $b bell characters
     * @param  string  $a
     * @param  string  $b
     * @return integer -1, 0 or 1 as suitable for use in various PHP sorting functions
     */
    public static function bellOrder($a, $b)
    {
        $a = self::bellToInt($a);
        $b = self::bellToInt($b);
        if ($a == $b) {
            return 0;
        } elseif ($a < $b) {
            return -1;
        } else {
            return 1;
        }
    }

    /**
     * Determines whether a $place is even
     * @param  integer|string $place
     * @return boolean
     */
    public static function isEven($place)
    {
        if (is_int($place)) {
            return ($place%2 == 0);
        }
        $stop = strlen(self::$_notationOrder);
        for ($i = 0; $i < $stop; $i += 2) {
            // If odd return false
            if ($place == self::$_notationOrder[$i]) {
                return false;
            }
        }
        // If even return true
        return true;
    }

    /**
     * Takes notation, and returns the long form made by rotating it about the 'half-lead' (last change). Doesn't do any sorting to tidy up the result
     * @access private
     * @param  string $notation
     * @return string
     */
    private static function expandHalf($notation)
    {
        $notation = trim($notation, '&');
        $notationReversed = preg_replace_callback('/\)(.+?)\(/', function ($m) { return '('.strrev($m[1]).')'; }, strrev($notation) );
        $firstDot = (strpos($notationReversed, '.') !== false) ? strpos($notationReversed, '.') : 99999;
        $firstX = (strpos($notationReversed, 'x') !== false) ? strpos($notationReversed, 'x') : 99999;
        $trim = 0;
        if ($firstDot < $firstX) {
            $trim = $firstDot + 1;
        } else {
            $trim = ($firstX == 0) ? 1 : $firstX;
        }

        return trim(str_replace(array( '.x.', 'x.', '.x'), 'x', $notation.'.'.substr($notationReversed, $trim)), '.');
    }

    /**
     * Sorts the changes of $notation
     * @access private
     * @param  string $notation
     * @return string
     */
    private static function order($notation)
    {
        $splitNotation = self::explode($notation);
        foreach ($splitNotation as &$section) {
            if ($section != 'x') {
                // Sort the piece characters numerically
                // Since we don't want to sort inside '()' (for jump changes), map those from '(abc)' to max(a, b, c) for sorting purposes (keeping
                // both the original value and the 'sort key').
                $section = str_replace(array('(', ')'), array('~(', ')~'), $section);
                $sectionSplitArrays = array_map(function ($e) {
                    if ($e[0] == '(') {
                        return array( array('sort' => max(array_filter(array_map('self::bellToInt', str_split($e)))), 'value' => $e) );
                    } else {
                        return array_map(function ($e) { return array('sort' => self::bellToInt($e), 'value' => $e); }, str_split($e));
                    }
                }, array_filter(explode('~', $section), 'strlen'));
                $sectionSplit = call_user_func_array('array_merge', $sectionSplitArrays);
                usort($sectionSplit, function ($a, $b) {
                    return $a['sort'] - $b['sort'];
                });
                $section = array_reduce($sectionSplit, function ($c, $i) { return $c.$i['value']; }, '');
            }
        }

        return self::implode($splitNotation);
    }
};
