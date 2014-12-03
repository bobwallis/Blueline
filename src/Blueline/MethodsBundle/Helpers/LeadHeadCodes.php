<?php
namespace Blueline\MethodsBundle\Helpers;


/**
 * Functions to assist working with lead head codes
 * @package Helpers
 * @author Robert Wallis <bob.wallis@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class LeadHeadCodes
{
    /**
     * @access private
     */
    private static $_leadHeadCodes = array(
        'a' => array(
            4 => '1342',
            5 => '12534',
            6 => '135264',
            7 => '1253746',
            8 => '13527486',
            9 => '125374968',
            10 => '1352749608',
            11 => '12537496E80',
            12 => '13527496E8T0',
            13 => '12537496E8A0T',
            14 => '13527496E8A0BT',
            15 => '12537496E8A0CTB',
            16 => '13527496E8A0CTDB',
            17 => '13527496E8A0CTFBD',
            18 => '13527496E8A0CTFBGD',
            19 => '13527496E8A0CTFBHDG',
            20 => '13527496E8A0CTFBHDJG',
            21 => '13527496E8A0CTFBHDKGJ',
            22 => '13527496E8A0CTFBHDKGLJ',
        ),
        'b' => array(
            6 => '156342',
            7 => '1275634',
            8 => '15738264',
            9 => '127593846',
            10 => '1573920486',
            11 => '127593E4068',
            12 => '157392E4T608',
            13 => '127593E4A6T80',
            14 => '157392E4A6B8T0',
            15 => '127593E4A6C8B0T',
            16 => '157392E4A6C8D0BT',
            18 => '157392E4A6C8F0GTDB',
            20 => '157392E4A6C8F0HTJBGD',
        ),
        'c' => array(
            8 => '17856342',
            9 => '129785634',
            12 => '1795E3T20486',
            13 => '1297E5A3T4068',
            14 => '1795E3A2B4T608',
            15 => '1297E5A3C4B6T80',
        ),
        'c1' => array(
            10 => '1907856342',
            11 => '12E90785634',
            12 => '19E7T5038264',
            13 => '12E9A7T503846',
            14 => '19E7A5B3T20486',
            15 => '12E9A7C5B3T4068',
            16 => '19E7A5C3D2B4T608',
        ),
        'c2' => array(
            12 => '1ET907856342',
            13 => '12AET90785634',
            14 => '1EA9B7T5038264',
            15 => '12AEC9B7T503846',
        ),
        'c3' => array(
            14 => '1ABET907856342',
            15 => '12CABET90785634',
        ),
        'c4' => array(
            16 => '1CDABET907856342',
        ),
        'd' => array(
            8 => '18674523',
            9 => '128967453',
            12 => '18604T2E3957',
            13 => '12806T4A3E597',
            14 => '18604T2B3A5E79',
            15 => '12806T4B3C5A7E9',
        ),
        'd1' => array(
            10 => '1089674523',
            11 => '120E8967453',
            12 => '108T6E492735',
            13 => '120T8A6E49375',
            14 => '108T6B4A2E3957',
            15 => '120T8B6C4A3E597',
            16 => '108T6B4D2C3A5E79',
        ),
        'd2' => array(
            12 => '1T0E89674523',
            13 => '12TA0E8967453',
            14 => '1T0B8A6E492735',
            15 => '12TB0C8A6E49375',
        ),
        'd3' => array(
            14 => '1BTA0E89674523',
            15 => '12BCTA0E8967453',
        ),
        'd4' => array(
            16 => '1DBCTA0E89674523',
        ),
        'e' => array(
            6 => '164523',
            7 => '1267453',
            8 => '16482735',
            9 => '126849375',
            10 => '1648203957',
            11 => '1268403E597',
            12 => '1648203T5E79',
            13 => '1268403T5A7E9',
            14 => '1648203T5B7A9E',
            15 => '1268403T5B7C9AE',
            16 => '1648203T5B7D9CEA',
            18 => '1648203T5B7D9GEFAC',
            20 => '1648203T5B7D9GEJAHCF',
            22 => '1648203T5B7D9GEJALCKFH',
        ),
        'f' => array(
            4 => '1423',
            5 => '12453',
            6 => '142635',
            7 => '1246375',
            8 => '14263857',
            9 => '124638597',
            10 => '1426385079',
            11 => '124638507E9',
            12 => '142638507T9E',
            13 => '124638507T9AE',
            14 => '142638507T9BEA',
            15 => '124638507T9BECA',
            16 => '142638507T9BEDAC',
            17 => '142638507T9BEDAFC',
            18 => '142638507T9BEDAGCF',
            19 => '142638507T9BEDAGCHF',
            20 => '142638507T9BEDAGCJFH',
        ),
        'p' => array(
            5 => '13524',
            6 => '125364',
            7 => '1352746',
            8 => '12537486',
            9 => '135274968',
            10 => '1253749608',
            11 => '13527496E80',
            12 => '12537496E8T0',
            13 => '13527496E8A0T',
            14 => '12537496E8A0BT',
            15 => '13527496E8A0CTB',
            16 => '12537496E8A0CTDB',
        ),
        'p1' => array(
            9 => '179583624',
            10 => '1297058364',
            11 => '1795E302846',
            12 => '1297E5T30486',
            13 => '1795E3A2T4068',
            15 => '1795E3A2C4B6T80',
            16 => '1297E5A3C4D6B8T0',
        ),
        'p2' => array(
            13 => '1EA9T70583624',
            14 => '12AEB9T7058364',
            15 => '1EA9C7B5T302846',
            16 => '12AEC9D7B5T30486',
        ),
        'q' => array(
            5 => '14253',
            6 => '124635',
            7 => '1426375',
            8 => '12463857',
            9 => '142638597',
            10 => '1246385079',
            11 => '142638507E9',
            12 => '124638507T9E',
            13 => '142638507T9AE',
            14 => '124638507T9BEA',
            15 => '142638507T9BECA',
            16 => '124638507T9BEDAC',
        ),
        'q1' => array(
            9 => '186947253',
            10 => '1280694735',
            11 => '18604E29375',
            12 => '12806T4E3957',
            13 => '18604T2A3E597',
            15 => '18604T2B3C5A7E9',
            16 => '12806T4B3D5C7A9E',
        ),
        'q2' => array(
            13 => '1T0A8E6947253',
            14 => '12TB0A8E694735',
            15 => '1T0B8C6A4E29375',
            16 => '12TB0D8C6A4E3957',
        ),
    );
    /**
     * @access private
     */
    private static $_leadHeadCodeConversion = array(
        'a' => 'g',
        'b' => 'h',
        'c' => 'j',
        'd' => 'k',
        'e' => 'l',
        'f' => 'm',
        'p' => 'r',
        'q' => 's',
    );

    /**
     * Converts a code and stage into a lead head
     * @param  string         $code
     * @param  integer|string $stage
     * @return string|boolean
     */
    public static function fromCode($code, $stage)
    {
        if (strlen($code) > 2) {
            return false;
        }
        $code = str_replace(array_values(self::$_leadHeadCodeConversion), array_keys(self::$_leadHeadCodeConversion), strtolower($code));
        $stage = Stages::toInt($stage);

        return isset(self::$_leadHeadCodes[$code][$stage]) ? self::$_leadHeadCodes[$code][$stage] : false;
    }

    /**
     * Converts a lead head, stage, and lead end notation into a lead head code
     * @param  string         $leadHead
     * @param  integer|string $stage
     * @param  string         $leadEndNotation
     * @param  string         $postLeadEndNotation
     * @return string|boolean
     */
    public static function toCode($leadHead, $stage, $numberOfHunts, $leadEndNotation, $postLeadEndNotation = '')
    {
        $leadHead = (is_array($leadHead)) ? implode('', $leadHead) : $leadHead;
        $stage = Stages::toInt($stage);
        $stageIsEven = ($stage%2 == 0);
        $stageNotation = PlaceNotation::intToBell($stage);

        $code = false;
        foreach (array_keys(self::$_leadHeadCodes) as $c) {
            if (isset(self::$_leadHeadCodes[$c][$stage]) && $leadHead == self::$_leadHeadCodes[$c][$stage]) {
                $code = $c;
                break;
            }
        }
        if ($code) {
            if (($stageIsEven && (($numberOfHunts == 1 && $leadEndNotation == '12') || ($numberOfHunts == 2 && $postLeadEndNotation == '3'.$stageNotation)))
                || (!$stageIsEven && (($numberOfHunts == 1 && $leadEndNotation == '12'.$stageNotation) || ($numberOfHunts == 2 && $postLeadEndNotation == '3')))
                ) {
                return $code;
            } elseif (($stageIsEven && (($numberOfHunts == 1 && $leadEndNotation == '1'.$stageNotation) || ($numberOfHunts == 2 && $postLeadEndNotation == 'x')))
                || (!$stageIsEven && (($numberOfHunts == 1 && $leadEndNotation == '1') || ($numberOfHunts == 2 && $postLeadEndNotation == $stageNotation)))
                ) {
                return str_replace(array_keys(self::$_leadHeadCodeConversion), array_values(self::$_leadHeadCodeConversion), $code);
            }
        }
        // Irregular lead head
        return PlaceNotation::trimExternalPlaces($leadEndNotation, $stage).'z';
    }
};
