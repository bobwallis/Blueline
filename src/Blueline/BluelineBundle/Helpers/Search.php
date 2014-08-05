<?php
namespace Blueline\BluelineBundle\Helpers;

class Search
{
    public static function prepareStringForLike($string)
    {
        return preg_replace( '/%+/', '%', str_replace(
            array( '*', '?', ',', '.', ' ' ),
            array( '%', '_', ' ', ' ', '%' ),
            '%'.strtolower( $string ).'%'
        ) );
    }

    public static function requestToSearchVariables($request, $searchable)
    {
        $searchVariables = array();

        foreach ( array_merge( array( 'q' ), $searchable ) as $key ) {
            $value = trim( $request->query->get( $key ) );
            if ( !empty( $value ) ) { $searchVariables[$key] = $value; }
        }

        $searchVariables['offset'] = intval( $request->query->get( 'offset' ) );
        $searchVariables['count'] = intval( $request->query->get( 'count' ) );
        if ($searchVariables['offset'] < 0) { $searchVariables['offset'] = 0; }
        if ($searchVariables['count'] <= 0) { $searchVariables['count'] = 15; }

        return $searchVariables;
    }
};
