<?php
namespace Blueline\MethodsBundle\Helpers;

use \SplFixedArray;

/**
 * Functions to calculate the similarity of two methods
 * @package Helpers
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class MethodSimilarity
{
	public static function calculate($rowArray1, $rowArray2, $stage, $limit = INF) {
		// Function which reduces output of PlaceNotation::apply to string
		$mapper = function($a) {
			return implode( array_map( array('Blueline\MethodsBundle\Helpers\PlaceNotation', 'intToBell'), $a ) );
		};

		// Generate the rounds row for this stage
		$rounds = PlaceNotation::rounds($stage);
		
		// Convert arrays to the right format if needed
		if (!is_array($rowArray1)) {
			$rowArray1 = array_map($mapper, PlaceNotation::apply(PlaceNotation::explodedToPermutations($stage, PlaceNotation::explode($rowArray1)), $rounds));
		}

		if (!is_array($rowArray2)) {
			$rowArray2 = array_map($mapper, PlaceNotation::apply(PlaceNotation::explodedToPermutations($stage, PlaceNotation::explode($rowArray2)), $rounds));
		}

		// Calculate arrays to compare
		$count1 = count($rowArray1);
		$count2 = count($rowArray2);

		// Calculate a matrix of size i=count($rowArray1) x j=count($rowArray2) with each element containing
		// the distance between the first i rows of 1 and the first j rows of 2.
		$d = new SplFixedArray($count1);
		for ($i = 0; $i < $count1; ++$i) {
			$d[$i] = new SplFixedArray($count2);
			$d[$i][0] = $i;
		}
		for ($j = 0; $j < $count2; ++$j) {
			$d[0][$j] = $j;
		}
		for ($j = 1; $j < $count2; ++$j) {
			for ($i = 1; $i < $count1; ++$i) {
				// Compare similarity of row i and j of array1 and array2 against each other
				$cost = 0;
				if ($d[$i-1][$j-1] < $limit) {
					foreach (str_split($rowArray1[$i]) as $k => $c) {
						$cost += abs($k - strpos($rowArray2[$j], $c));
					}
					$cost /= $stage;
				}

				// Set [i][j] to the minimum of 'delete row from array 1', 'delete row from array 2',
				// and 'change the last row in array1 to the last row in array2' (with added cost for those operations)
				$d[$i][$j] = min($limit, $d[$i-1][$j]+1, $d[$i][$j-1]+1, $d[$i-1][$j-1]+$cost );
			}
		}

		// The answer is then the value in the bottom right.
		return round($d[$i-1][$j-1], 2);
	}
}