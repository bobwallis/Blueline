<?php

namespace Blueline\Helpers;

/**
 * Utilities for parsing, validating, and manipulating bell-ringing place notation.
 *
 * Place notation is a concise representation of bell-ringing changes as sequences of places made.
 * This class provides methods to:
 * - Convert between bell numbers (1-33) and bell characters ('1'-'Z')
 * - Validate and expand notation into full change sequences
 * - Permute rows according to place notation
 * - Calculate lead head codes, rounds rows, and notation equivalences
 *
 * All methods are static. Most operations assume Place Notation is in a standard format.
 *
 * @see Stages for bell number validation
 * @see LeadHeadCodes for lead head code lookups
 */
class PlaceNotation
{
    private static $_notationOrder = '1234567890ETABCDFGHJKLMNPQRSUVWYZ';

    private static $_notationOrderLength = 33;

    private static $_intToBellMap = [
        1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8',
        9 => '9', 10 => '0', 11 => 'E', 12 => 'T', 13 => 'A', 14 => 'B', 15 => 'C', 16 => 'D',
        17 => 'F', 18 => 'G', 19 => 'H', 20 => 'J', 21 => 'K', 22 => 'L', 23 => 'M', 24 => 'N',
        25 => 'P', 26 => 'Q', 27 => 'R', 28 => 'S', 29 => 'U', 30 => 'V', 31 => 'W', 32 => 'Y',
        33 => 'Z',
    ];

    private static $_bellToIntMap = [
        '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8,
        '9' => 9, '0' => 10, 'E' => 11, 'T' => 12, 'A' => 13, 'B' => 14, 'C' => 15, 'D' => 16,
        'F' => 17, 'G' => 18, 'H' => 19, 'J' => 20, 'K' => 21, 'L' => 22, 'M' => 23, 'N' => 24,
        'P' => 25, 'Q' => 26, 'R' => 27, 'S' => 28, 'U' => 29, 'V' => 30, 'W' => 31, 'Y' => 32,
        'Z' => 33,
    ];

    private static $_bellIsEvenMap = [
        '1' => false, '2' => true, '3' => false, '4' => true, '5' => false, '6' => true,
        '7' => false, '8' => true, '9' => false, '0' => true, 'E' => false, 'T' => true,
        'A' => false, 'B' => true, 'C' => false, 'D' => true, 'F' => false, 'G' => true,
        'H' => false, 'J' => true, 'K' => false, 'L' => true, 'M' => false, 'N' => true,
        'P' => false, 'Q' => true, 'R' => false, 'S' => true, 'U' => false, 'V' => true,
        'W' => false, 'Y' => true, 'Z' => false,
    ];

    /**
     * Convert an integer bell number to its character notation.
     *
     * Maps 1->1, 2->2, ..., 10->0, 11->E, 12->T, ..., 33->Z.
     * Up to 33 bells are supported.
     *
     * @param int $int The bell number (1-33)
     *
     * @return string|false The bell character on success, false if out of range
     */
    public static function intToBell(int $int): string|false
    {
        $int = intval($int);
        if ($int < 1 || $int > self::$_notationOrderLength) {
            return false;
        }

        return self::$_intToBellMap[$int];
    }

    /**
     * Convert a bell character to its numeric position.
     *
     * Reverse of intToBell().
     *
     * @param string $bell A single bell character ('1'-'Z')
     *
     * @return int|false The bell number (1-33) on success, false if invalid character or multi-char string
     */
    public static function bellToInt(string $bell): int|false
    {
        if (1 != strlen($bell)) {
            return false;
        }

        return self::$_bellToIntMap[$bell] ?? false;
    }

    /**
     * Generate a round row (all bells in order) for a given stage.
     *
     * Example: rounds(4) returns ['1', '2', '3', '4']
     *
     * @param int $stage Number of bells (typically 4-12)
     *
     * @return array Rounds row as array of bell characters
     */
    public static function rounds(int $stage): array
    {
        return array_map(['Blueline\Helpers\PlaceNotation', 'intToBell'], range(1, $stage));
    }

    /**
     * Test whether two rows are equal.
     *
     * Arrays of unequal length are considered not equal.
     *
     * @param array $a First row
     * @param array $b Second row
     *
     * @return bool True if arrays are identical
     */
    public static function rowsEqual(array $a, array $b): bool
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
     * Place notation can implicitly include places at bells 1 (start) and the stage (end).
     * This method removes explicit notation of those when they're redundant.
     *
     * @param string $piece A single piece of notation (e.g., '16' or 'x')
     * @param int    $stage Number of bells
     *
     * @return string Trimmed notation
     */
    public static function trimExternalPlaces(string $piece, int $stage): string
    {
        $stageIsEven = (0 == $stage % 2);
        $stageNotation = self::intToBell($stage);

        if ('1' == substr($piece, 0, 1) && self::isEven(substr($piece, 1, 1))) {
            $piece = substr($piece, 1);
        }
        if ((substr($piece, -1) == $stageNotation) && (($stageIsEven && !self::isEven(substr($piece, -2, 1))) || (!$stageIsEven && self::isEven(substr($piece, -2, 1))))) {
            $piece = substr($piece, 0, -1);
        }

        return $piece;
    }

    /**
     * Test whether a change contains an internal place.
     *
     * Internal places are places other than 1st's or nth's place.
     *
     * @param string $piece A single piece of notation (e.g., "12", "x", "78", etc)
     * @param int    $stage The stage (number of bells)
     *
     * @return bool True if the notation contains internal places
     */
    public static function changeHasInternalPlaces(string $piece, int $stage): bool
    {
        $n = self::intToBell($stage);

        // Strip first's and nth's places; any remainder indicates one or more internal places.
        return !('x' == $piece || 0 == strlen(preg_replace('/[1'.$n.']?/', '', $piece)));
    }

    /**
     * Apply a sequence of permutations to a starting array.
     *
     * Applies permutations iteratively: permutation[0] to start, permutation[1] to result[0], etc.
     * Returns array of all intermediate and final results.
     *
     * @param array $permutations Array of permutation arrays, or a single permutation
     * @param array $start        Initial row/array
     *
     * @return array Results of applying each permutation (size = number of permutations + 1)
     */
    public static function apply(array $permutations, array $start): array
    {
        if (!is_array($permutations[0])) {
            return self::permute($start, $permutations);
        }
        if (count($permutations[0]) != count($start)) {
            return [];
        }
        $result = [self::permute($start, $permutations[0])];
        for ($i = 1, $iLim = count($permutations); $i < $iLim; ++$i) {
            if (count($permutations[$i]) != count($result[$i - 1])) {
                return [];
            }
            $result[$i] = self::permute($result[$i - 1], $permutations[$i]);
        }

        return $result;
    }

    /**
     * Applies permutations successively to a start string.
     *
     * @param string $start
     *
     * @return array All of the permutations of $start (as strings)
     */
    public static function applyToString(array $permutations, $start)
    {
        return array_map('implode', self::apply($permutations, str_split($start)));
    }

    /**
     * Permutes an array by a permutation.
     *
     * @return array|bool The new permutation, or false if the permutation fails
     */
    public static function permute(array $start, array $permutation)
    {
        if (empty($start) || empty($permutation)) {
            return $start;
        }
        if (count($start) != count($permutation)) {
            return false;
        }
        $end = [];
        for ($i = 0, $iLim = count($permutation); $i < $iLim; ++$i) {
            $end[$i] = $start[$permutation[$i]];
        }

        return $end;
    }

    /**
     * Permutes a string by a permutation.
     *
     * @param string $start
     *
     * @return string|bool The new permutation, or false if the permutation fails
     */
    public static function permuteString($start, array $permutation)
    {
        return implode(self::permute(str_split($start), $permutation) ?: []) ?: false;
    }

    /**
     * Guess the stage from a place notation string.
     *
     * Checks for the highest bell character in the notation to infer stage.
     * Ignores brackets, FCH codes, and other markup.
     *
     * @param string $notation Place notation (may contain notation variants, brackets, FCH codes)
     *
     * @return int The inferred stage (minimum 2)
     */
    public static function guessStage(string $notation): int
    {
        // Remove anything inside brackets, or appended fch details (arise when people copy notation from somewhere with extra information at the start or end)
        $notationFull = preg_replace(['/[\[{\<].*[\]}\>]/', '/ FCH.*$/'], '', $notation);

        // Then guess
        return max(array_map(function ($c) {
            return PlaceNotation::bellToInt($c);
        }, array_filter(str_split(str_replace([' HL ', 'LE', 'LH'], '', $notationFull)), function ($c) {
            return preg_match('/[0-9A-Z]/', $c);
        })));
    }

    /**
     * Expands a shortened place notation format into a fully expanded one.
     *
     * @param string $notation
     * @param int    $stage
     *
     * @return string
     */
    public static function expand($notation, $stage = null)
    {
        // If stage isn't given, try to guess it
        if (is_null($stage) || $stage < 2) {
            $stage = self::guessStage($notation);
        }
        $stageIsEven = (0 == $stage % 2);
        $stageNotation = self::intToBell($stage);

        // Tidy up letter cases
        $notationFull = strtoupper($notation);
        $notationFull = str_replace('X', 'x', $notationFull);

        // Just in case people feel the need to use this kind of notation in their input, get rid of it here in case it causes errors later on
        $notationFull = str_replace(['.x.', 'x.', '.x'], 'x', $notationFull);
        $notationFull = str_replace(['.-.', '-.', '.-'], '-', $notationFull);

        // Remove anything inside brackets, or appended fch details (arise when people copy notation from somewhere with extra information at the start or end)
        $notationFull = preg_replace(['/[{\<].*[}\>]/', '/ FCH.*$/'], '', $notationFull);

        // Deal with notation like 'x1x1x1-2' (After checking for this form we can assume - means x)
        if (1 == preg_match('/^([^-]+)-([^-\.,x]+)$/', $notationFull, $match)) {
            // if there's only one -, and it's got one change after it...
            $notationFull = self::expandHalf($match[1]).'.'.$match[2];
        }
        $notationFull = str_replace('-', 'x', $notationFull);

        // Turn notation like ...x34 hl 16 le 12 into ...x34.16 le 12
        if (false !== strpos($notationFull, ' HL ')) {
            $notationFull = str_replace(' HL ', '.', $notationFull);
        }

        // Deal with notation like '-1-1-1LH2', or '-1-1-1 le2'. Allow a preceding ampersand
        if (1 == preg_match('/^&?(.*)(LH|LE)/', $notationFull, $match)) {
            $notationFull = str_replace($match[0], self::expandHalf($match[1]), $notationFull);
        }

        // Parse microSiril format notation
        if (false !== strpos($notationFull, ',')) {
            $splitOnComma = array_map('trim', explode(',', $notationFull));
            if (array_reduce($splitOnComma, function ($carry, $item) {
                return $carry && ('+' == $item[0] || '&' == $item[0]);
            }, true)) {
                $notationFull = array_reduce($splitOnComma, function ($carry, $item) {
                    return $carry.'.'.('&' == $item[0] ? self::expandHalf($item) : trim($item, '+'));
                }, '');
            }
        }

        // Now we've checked for proper microSiril format we'll make some assumptions about what people might actually mean.
        // Deal with notation like '-1-1-1,2' or '3,1.5.1.5.1'
        if (1 == preg_match('/^\s*&?\s*(.*),(.*)/', $notationFull, $match)) {
            $notationFull = self::expandHalf($match[1]).'.'.self::expandHalf($match[2]);
            $notationFull = str_replace(['.x.', 'x.', '.x'], 'x', $notationFull);
        }

        // Get rid of +
        $notationFull = str_replace('+', ' ', $notationFull);

        // Convert 'a &-1-1-1' type notation into '&x1x1x1 2' type
        if (1 == preg_match('/^[A-S][1-9]?\s/', $notationFull)) {
            // For even bell methods
            if ($stageIsEven) {
                // a to f is 12
                if (1 == preg_match('/^([A-F][1-9]?)\s+(.*)$/', $notationFull, $match)) {
                    $notationFull = $match[2].' 12';
                // g to m is 1n
                } elseif (1 == preg_match('/^([G-M][1-9]?)\s+(.*)$/', $notationFull, $match)) {
                    $notationFull = $match[2].' 1'.$stageNotation;
                // p, q is 3n post lead head (if 3n isn't the start of $match[2] then add it to the start)
                } elseif (1 == preg_match('/^([P-Q][1-9]?)\s+(.*)$/', $notationFull, $match)) {
                    if (0 === strpos($match[2], '3'.$stageNotation)) {
                        $notationFull = $match[2];
                    } else {
                        $notationFull = '3'.$stageNotation.' '.$match[2];
                    }
                }
            // r, s is n post lead head (similar to above)
            // I don't know why I think this... but it doesn't seem to be true looking at actual methods in the current collections.
            // Let me know if you know!
            // elseif (preg_match('/^([R-S][1-9]?)\s+(.*)$/', $notationFull, $match) == 1) {
            //    if (strpos($match[2], self::$_notationOrder[$stage-1]) === 0) {
            //        $notationFull = $match[2];
            //    } else {
            //        $notationFull = self::$_notationOrder[$stage-1].' '.$match[2];
            //    }
            // }
            // For odd bell methods
            } else {
                // a to f is 3 post lead head (if 3 isn't the start of $match[2] then add it to the start)
                if (1 == preg_match('/^([A-F][1-9]?)\s+(.*)$/', $notationFull, $match)) {
                    if (0 === strpos($match[2], '3')) {
                        $notationFull = $match[2];
                    } else {
                        $notationFull = '3 '.$match[2];
                    }
                // g to m is n post lead head (similar to above)
                } elseif (1 == preg_match('/^([G-M][1-9]?)\s+(.*)$/', $notationFull, $match)) {
                    if (0 === strpos($match[2], $stageNotation)) {
                        $notationFull = $match[2];
                    } else {
                        $notationFull = $stageNotation.' '.$match[2];
                    }
                // p, q is 12n
                } elseif (1 == preg_match('/^([P-Q][1-9]?)\s+(.*)$/', $notationFull, $match)) {
                    $notationFull = $match[2].' 12'.$stageNotation;
                // r, s is 1
                } elseif (1 == preg_match('/^([R-S][1-9]?)\s+(.*)$/', $notationFull, $match)) {
                    $notationFull = $match[2].' 1';
                }
            }
        // z is an irregular lead head
        } elseif (1 == preg_match('/^(.*)Z\s+(.*)$/', $notationFull, $match)) {
            $notationFull = $match[2].' '.$match[1];
        }

        // Deal with, '&x1x1x1 2' type notation
        if (1 == preg_match('/^&(.*)\s+([^x.]+)$/', $notationFull, $match)) {
            $notationFull = self::expandHalf($match[1]).' '.$match[2];
        }

        // Replace any remaining whitespace with dots
        $notationFull = preg_replace('/\s+/', '.', $notationFull);

        // Trim any trailing or preceding dots
        $notationFull = trim($notationFull, '.');

        // Remove any unecessary doubling up of dots and x
        $notationFull = preg_replace('/\.+/', '.', $notationFull);
        $notationFull = str_replace(['.x.', 'x.', '.x'], 'x', $notationFull);

        // Correct any ordering of bells in the notation that have arisen either from us mirroring things, or lazy input
        $notationFull = self::order($notationFull);

        // Explode notation
        $notationExploded = self::explode($notationFull);

        // Work through each piece of notation individually to do last bits of cleanup
        foreach ($notationExploded as &$split) {
            if ('x' == $split) {
                if (!$stageIsEven) {
                    // If stage odd and we have an x, change to an n
                    $split = $stageNotation;
                }
                continue;
            }
            // Work out which places are affected
            preg_match_all('/[A-Z\d]+/', $split, $pregOutput);
            $pregOutput = array_map('str_split', $pregOutput[0]);

            $affected = [];
            array_walk_recursive($pregOutput, function ($a) use (&$affected) {
                $affected[] = $a;
            });
            usort($affected, [__CLASS__, 'bellOrder']);
            // Then add missing external places
            // If the first bell is even, prepend a 1
            if (self::isEven($affected[0])) {
                $split = '1'.$split;
            }
            // If stage odd and last bell even, or stage even and last bell odd, append an n
            if ((0 != $stage % 2 && self::isEven(end($affected))) || (0 == $stage % 2 && !self::isEven(end($affected)))) {
                $split = $split.self::intToBell($stage);
            }
        }

        // Implode the exploded notation, with added external places, back into string form
        return self::implode($notationExploded);
    }

    /**
     * Converts notation into microSiril format.
     *
     * @param string $notation
     * @param int    $stage
     *
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
            if ('x' != $pn) {
                if (strlen($pn) > 1
                    && '1' == $pn[0] && 0 == PlaceNotation::bellToInt($pn[1]) % 2
                ) {
                    $pn = substr($pn, 1);
                }
                if (strlen($pn) > 1
                    && $pn[strlen($pn) - 1] == PlaceNotation::intToBell($stage)
                    && PlaceNotation::bellToInt($pn[strlen($pn) - 2]) % 2 != $stage % 2
                ) {
                    $pn = substr($pn, 0, strlen($pn) - 1);
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
            for ($palindromeLength = (0 == count($notation) % 2) ? count($notation) - 1 : count($notation); $palindromeLength >= 3; $palindromeLength -= 2) {
                for ($palindromeOffset = 0; $palindromeLength + $palindromeOffset <= count($notation); ++$palindromeOffset) {
                    $word = array_slice($notation, $palindromeOffset, $palindromeLength);
                    $reversedWord = array_reverse($word);
                    if ($word == $reversedWord) {
                        $result = '';
                        if ($palindromeOffset > 0) {
                            $result .= $sirilise(array_slice($notation, 0, $palindromeOffset)).', ';
                        }
                        $result .= '&'.PlaceNotation::implode(array_slice($word, 0, (count($word) + 1) / 2));
                        if ($palindromeOffset + $palindromeLength < count($notation)) {
                            $result .= ', '.$sirilise(array_slice($notation, $palindromeOffset + $palindromeLength));
                        }

                        return $result;
                    }
                }
            }

            return '+'.PlaceNotation::implode($notation);
        };

        return str_replace('x', '-', $sirilise($expandedAndExploded));
    }

    /**
     * Explodes (expanded) place notation into an array of single changes (as strings).
     *
     * @param string $notation
     *
     * @return array
     */
    public static function explode($notation)
    {
        return array_values(array_filter(explode('.', str_replace('x', '.x.', $notation)), 'strlen'));
    }

    /**
     * Implodes an array of place notation chunks into a single string.
     *
     * @return string
     */
    public static function implode(array $notation)
    {
        return str_replace(['.x.', 'x.', '.x'], 'x', implode('.', $notation));
    }

    /**
     * Converts exploded place notation into an array of relation notation permutations.
     *
     * @param int   $stage
     * @param array $notationExploded
     *
     * @return array
     */
    public static function explodedToPermutations($stage, $notationExploded)
    {
        // Remember that treble is 0, 10 is 9, and E is 10...
        $permutations = [];
        $i = 0;
        foreach ($notationExploded as $piece) {
            // Work through the notation piece
            for ($j = 0; $j < strlen($piece); ++$j) {
                if ('x' == $piece[$j]) {
                    break;
                }
                // A jump change (XY) sends the bell in the 1st place to the 2nd, and the bells in the span shift
                // (13) takes 1234 to 2314,
                // (14) takes 1234 to 2341,
                // (31) takes 1234 to 3124,
                // (41) takes 1234 to 4123 and so on
                elseif ('(' == $piece[$j]) {
                    $from = self::bellToInt($piece[$j + 1]) - 1;
                    $to = self::bellToInt($piece[$j + 2]) - 1;
                    $permutations[$i][$to] = $from;
                    if ($from < $to) {
                        for ($k = $from; $k < $to; ++$k) {
                            $permutations[$i][$k] = $k + 1;
                        }
                    } else {
                        for ($k = $from; $k > $to; --$k) {
                            $permutations[$i][$k] = $k - 1;
                        }
                    }
                    $j += 3;
                }
                // A jump change [ABCD] describes what happens to position between min(A,B,C,D) and max(A,B,C,D)
                // [4321] takes 1234 to 4321 and so on
                elseif ('[' == $piece[$j]) {
                    $k = strpos($piece, ']', $j);
                    $l = 0;
                    $lowestBell = min(array_map(self::class.'::bellToInt', str_split(substr($piece, $j + 1, $k - $j - 1)))) - 1;
                    ++$j;
                    while ($j < $k) {
                        $permutations[$i][$lowestBell + $l] = self::bellToInt($piece[$j]) - 1;
                        ++$j;
                        ++$l;
                    }
                }
                // Static bells
                else {
                    $pos = self::bellToInt($piece[$j]) - 1;
                    $permutations[$i][$pos] = $pos;
                }
            }
            // 'x' what's left
            for ($j = 0; $j < $stage; ++$j) {
                if (isset($permutations[$i][$j])) {
                    continue;
                }
                $permutations[$i][$j] = $j + 1;
                $permutations[$i][$j + 1] = $j;
            }
            ksort($permutations[$i]);
            ++$i;
        }

        return $permutations;
    }

    /**
     * Determines ordering for $a, $b bell characters.
     *
     * @param string $a
     * @param string $b
     *
     * @return int -1, 0 or 1 as suitable for use in various PHP sorting functions
     */
    public static function bellOrder($a, $b)
    {
        $a = self::bellToInt($a);
        $b = self::bellToInt($b);
        if ($a == $b) {
            return 0;
        } elseif ($a < $b) {
            return -1;
        }

        return 1;
    }

    /**
     * Determines whether a $place is even.
     *
     * @param int|string $place
     *
     * @return bool
     */
    public static function isEven($place)
    {
        if (is_int($place)) {
            return 0 == $place % 2;
        }

        return self::$_bellIsEvenMap[$place] ?? true;
    }

    /**
     * Takes notation, and returns the long form made by rotating it about the 'half-lead' (last change). Doesn't do any sorting to tidy up the result.
     *
     * @param string $notation
     *
     * @return string
     */
    private static function expandHalf($notation)
    {
        $notation = trim($notation, '&');
        // First attempt is just to reverse the string
        $notationReversed = strrev($notation);
        // Jump notation type 1:
        // (14) indicates that the bell in 1st's Place jumps to 4th's Place in the next Row. The bells currently in 2nd's, 3rd's and 4th's Places each move down a Place in the next Row
        // (14) would take Row 1234 to 2341.
        // (41) would take Row 2341 to 1234.
        // The reverse of (14) is (41).
        // We need to reverse the order of the bells in the brackets, but not the brackets themselves.
        // Given we have already reversed the bells using strrev above, we just need to swap the brackets back to their original positions.
        $notationReversed = preg_replace_callback('/\)(.+?)\(/', function ($m) {
            return '('.$m[1].')';
        }, $notationReversed);
        // Jump notation type 2:
        // [3412] indicates that the bells in the current Row are "read" in the order 3rd's Place, 4th's Place, 1st's Place, 2nd's Place in order to generate the next Row.
        // [3412] would take Row 2143 to 4321.
        // [3412] would also take Row 4321 to 2143.
        // It self-inverts. But this is not the case for all jump changes, so we can't just assume that the reverse of [ABCD] is [ABCD] again.
        // [2413] would take Row 2143 to 1324.
        // [3142] would take Row 1324 to 2143.
        // Initialy... let's just revert the reversal done by strrev above
        $notationReversed = preg_replace_callback('/\](.+?)\[/', function ($m) {
            return '['.strrev($m[1]).']';
        }, $notationReversed);
        // And now let's reverse properly...
        $notationReversed = preg_replace_callback('/\[(.+?)\]/', function ($m) {
            $input = str_split($m[1]); // Split into characters
            $inverse = []; // This will hold the inverse mapping of the jump change
            foreach ($input as $zeroIndex => $char) {
                // If the bell at position ($zeroIndex + 1) moves TO self::bellToInt($char),
                // then in the inverse, t$char moves TO self::intToBell($zeroIndex + 1).
                $inverse[self::bellToInt($char) - 1] = self::intToBell($zeroIndex + 1);
            }
            ksort($inverse); // Sort by keys to ensure the string is built in the order 1, 2, 3, 4...

            // And then build the string
            return '['.implode('', $inverse).']';
        }, $notationReversed);
        $firstDot = (false !== strpos($notationReversed, '.')) ? strpos($notationReversed, '.') : 99999;
        $firstX = (false !== strpos($notationReversed, 'x')) ? strpos($notationReversed, 'x') : 99999;
        $trim = 0;
        if ($firstDot < $firstX) {
            $trim = $firstDot + 1;
        } else {
            $trim = (0 == $firstX) ? 1 : $firstX;
        }
        $combined = $notation.'.'.substr($notationReversed, $trim);

        return trim(preg_replace('/\.?(x|-)\.?/', '$1', $combined), '.');
    }

    /**
     * Sorts the changes of $notation.
     *
     * @param string $notation
     *
     * @return string
     */
    private static function order($notation)
    {
        $splitNotation = self::explode($notation);
        foreach ($splitNotation as &$section) {
            if ('x' != $section) {
                // Sort the piece characters numerically
                // Since we don't want to sort inside '()' or '[]' (for jump changes), map those from '[abc]' to max(a, b, c) for sorting purposes (keeping
                // both the original value and the 'sort key').
                $section = str_replace(['(', ')', '[', ']'], ['~(', ')~', '~[', ']~'], $section);
                $sectionSplitArrays = array_map(function ($e) {
                    if ('(' == $e[0] || '[' == $e[0]) {
                        return [['sort' => max(array_filter(array_map(self::class.'::bellToInt', str_split($e)))), 'value' => $e]];
                    }

                    return array_map(function ($e) {
                        return ['sort' => self::bellToInt($e), 'value' => $e];
                    }, str_split($e));
                }, array_filter(explode('~', $section), 'strlen'));
                $sectionSplit = call_user_func_array('array_merge', $sectionSplitArrays);
                usort($sectionSplit, function ($a, $b) {
                    return $a['sort'] - $b['sort'];
                });
                $section = array_reduce($sectionSplit, function ($c, $i) {
                    return $c.$i['value'];
                }, '');
            }
        }

        return self::implode($splitNotation);
    }
}
