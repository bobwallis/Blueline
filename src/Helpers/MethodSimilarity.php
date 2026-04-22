<?php

namespace Blueline\Helpers;

/**
 * Calculate similarity scores between bell-ringing methods using Wagner-Fischer algorithm.
 *
 * Uses an optimised Levenshtein distance variant applied to method rows.
 * Similarity is measured as the minimum number of edits needed to transform
 * one method into another.
 *
 * The algorithm uses dynamic programming with row caching and early termination
 * for performance.
 *
 * It also supports setting a similarity limit for early termination when a threshold
 * is exceeded.
 *
 * @see https://en.wikipedia.org/wiki/Wagner%E2%80%93Fischer_algorithm
 */
class MethodSimilarity
{
    public static function calculate($rowArray1, $rowArray2, $stage, $limit = INF)
    {
        // Generate the rounds row for this stage
        $rounds = PlaceNotation::rounds($stage);

        // Convert arrays to the right format if needed
        if (!is_array($rowArray1)) {
            $rowArray1 = array_map('implode', PlaceNotation::apply(PlaceNotation::explodedToPermutations($stage, PlaceNotation::explode($rowArray1)), $rounds));
        }

        if (!is_array($rowArray2)) {
            $rowArray2 = array_map('implode', PlaceNotation::apply(PlaceNotation::explodedToPermutations($stage, PlaceNotation::explode($rowArray2)), $rounds));
        }

        // Get the lengths
        $count1 = count($rowArray1);
        $count2 = count($rowArray2);

        // Edge cases
        if (0 === $count1) {
            return $count2;
        }
        if (0 === $count2) {
            return $count1;
        }

        // This is doing this: https://en.wikipedia.org/wiki/Wagner%E2%80%93Fischer_algorithm
        // with some optimisations to make it run faster.
        // The Wagner-Fischer algorithm is a dynamic programming approach to calculate the Levenshtein
        // distance between two strings, which is a measure of their similarity. In this context,
        // we are treating the rows as strings and calculating how many edits (insertions, deletions,
        // substitutions) are needed to transform one row into the other.

        // Pre-compute strpos locations for rowArray2, so that we can use lookups instead of strpos() calls
        // in the inner loop.
        $row2Positions = [];
        for ($j = 0; $j < $count2; ++$j) {
            $row2Positions[$j] = array_flip(str_split($rowArray2[$j]));
        }

        // A full N x M Array takes a reasonable amount of memory and allocation time.
        // Wagner-Fischer only actually needs the "previous" row and "current" row, so just keep those
        // in memory and swap them on each iteration.
        $prevD = range(0, $count2 - 1);
        $currD = [];
        $hasFiniteLimit = is_finite($limit);

        // Cap initialization row at $limit — values beyond the limit can never
        // contribute to a below-limit path, and capping them lets the row-level
        // early-out below kick in sooner.
        if ($hasFiniteLimit) {
            for ($j = 0; $j < $count2; ++$j) {
                if ($prevD[$j] > $limit) {
                    $prevD[$j] = $limit;
                }
            }
        }

        for ($i = 1; $i < $count1; ++$i) {
            $currD[0] = min($i, $limit);
            $row1_i = $rowArray1[$i];
            $rowMin = $currD[0];

            for ($j = 1; $j < $count2; ++$j) {
                $prev_j_minus_1 = $prevD[$j - 1];

                $min_val = $limit;

                $del1 = $prevD[$j] + 1;
                if ($del1 < $min_val) {
                    $min_val = $del1;
                }

                $del2 = $currD[$j - 1] + 1;
                if ($del2 < $min_val) {
                    $min_val = $del2;
                }

                // Only calculate cost if the diagonal is under the limit
                if ($prev_j_minus_1 < $limit) {
                    $cost = 0;
                    $r2pos = $row2Positions[$j];
                    $maxCostBeforeLimit = ($limit - $prev_j_minus_1) * $stage;

                    for ($k = 0; $k < $stage; ++$k) {
                        $cost += abs($k - $r2pos[$row1_i[$k]]);

                        if ($hasFiniteLimit && $cost >= $maxCostBeforeLimit) {
                            $cost = $maxCostBeforeLimit;
                            break;
                        }
                    }

                    $sub = $prev_j_minus_1 + ($cost / $stage);
                    if ($sub < $min_val) {
                        $min_val = $sub;
                    }
                }

                $currD[$j] = $min_val;
                if ($min_val < $rowMin) {
                    $rowMin = $min_val;
                }
            }

            // Once every value in a row has hit $limit the distance can never
            // drop below $limit again, so we can return immediately.
            if ($hasFiniteLimit && $rowMin >= $limit) {
                return $limit;
            }

            // Swap arrays to move to the next row
            $temp = $prevD;
            $prevD = $currD;
            $currD = $temp;
        }

        // The answer is then the last calculated value of the final iteration
        return round($prevD[$count2 - 1], 2);
    }
}
