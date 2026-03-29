<?php
namespace Blueline\Helpers;

/**
 * Functions to calculate the similarity of two methods
 * @package Helpers
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class MethodSimilarity
{
	public static function calculate($rowArray1, $rowArray2, $stage, $limit = INF) {
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
		if ($count1 === 0) return $count2;
		if ($count2 === 0) return $count1;

		// This is doing this: https://en.wikipedia.org/wiki/Wagner%E2%80%93Fischer_algorithm
		// with some optimisations to make it run faster.
		// The Wagner-Fischer algorithm is a dynamic programming approach to calculate the Levenshtein
		// distance between two strings, which is a measure of their similarity. In this context,
		// we are treating the rows as strings and calculating how many edits (insertions, deletions,
		// substitutions) are needed to transform one row into the other.

		// Pre-compute strpos locations for rowArray2, so that we can use lookups instead of strpos() calls
		// in the inner loop.
		$row2Positions =[];
		for ($j = 0; $j < $count2; ++$j) {
			$row2Positions[$j] = array_flip(str_split($rowArray2[$j]));
		}

		// A full N x M Array takes a reasonable amount of memory and allocation time.
		// Wagner-Fischer only actually needs the "previous" row and "current" row, so just keep those
		// in memory and swap them on each iteration.
		$prevD = range(0, $count2 - 1);
		$currD =[];

		for ($i = 1; $i < $count1; ++$i) {
			$currD[0] = $i;
			$row1_i = $rowArray1[$i];

			for ($j = 1; $j < $count2; ++$j) {
				$prev_j_minus_1 = $prevD[$j-1];

				$min_val = $limit;

				$del1 = $prevD[$j] + 1;
				if ($del1 < $min_val) $min_val = $del1;

				$del2 = $currD[$j-1] + 1;
				if ($del2 < $min_val) $min_val = $del2;

				// Only calculate cost if we are under the limit
				if ($prev_j_minus_1 < $limit) {
					$cost = 0;
					$r2pos = $row2Positions[$j];

					for ($k = 0; $k < $stage; ++$k) {
						// Use array-like string access
						// $row1_i[$k] grabs the character without needing str_split()
						// $r2pos[...] gets the precomputed index without needing strpos()
						$cost += abs($k - $r2pos[$row1_i[$k]]);
					}

					$sub = $prev_j_minus_1 + ($cost / $stage);
					if ($sub < $min_val) $min_val = $sub;
				} else {
					if ($prev_j_minus_1 < $min_val) $min_val = $prev_j_minus_1;
				}

				$currD[$j] = $min_val;
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
