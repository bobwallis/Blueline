<?php
namespace Blueline\BluelineBundle\Helpers;

class Search
{
    public static function prepareStringForLike($string)
    {
        return preg_replace('/%+/', '%', str_replace(
            array( '*', '?', ',', '.', ' ' ),
            array( '%', '_', ' ', ' ', '%' ),
            '%'.strtolower($string).'%'
        ));
    }

    public static function requestToSearchVariables($request, $searchable)
    {
        $searchVariables = array();

        foreach (array_merge(array( 'q', 'sort', 'order' ), $searchable) as $key) {
            $value = trim($request->query->get($key));
            if (!empty($value)) {
                $searchVariables[$key] = $value;
            }
        }

        // Order
        if( isset($searchVariables['order']) ) {
            $searchVariables['order'] = strtoupper($searchVariables['order']);
            if ($searchVariables['order'] != 'DESC') {
                $searchVariables['order'] = 'ASC';
            }
        }

        // Offset
        $searchVariables['offset'] = intval($request->query->get('offset'));
        if ($searchVariables['offset'] < 0) {
            $searchVariables['offset'] = 0;
        }

        // Count
        $searchVariables['count'] = intval($request->query->get('count'));
        if ($searchVariables['count'] <= 0) {
            $searchVariables['count'] = 15;
        }

        return $searchVariables;
    }
};
