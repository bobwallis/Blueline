<?php
namespace Blueline\BluelineBundle\Helpers;

function pg_upsert($connection, $table_name, $data, $condition) {
    $table_name = pg_escape_identifier($connection, $table_name);
    
    $dataAndCondition = array_merge($data, $condition);
    
    $insertKeys = implode(', ', array_map(function($e) use ($connection) {
        return pg_escape_identifier($connection, $e);
    }, array_keys($dataAndCondition)));

    $insertValues = implode(', ', array_map(function($e) use ($connection) {
        return pg_escape_literal($connection, $e);
    }, array_values($dataAndCondition)));

    $insert = 'INSERT INTO '.$table_name.' ('.$insertKeys.') SELECT '.$insertValues;
    $upsert = 'UPDATE '.$table_name.' SET tally=tally+1 WHERE date='today' AND spider='Googlebot'";

    return pg_query_($connection, 'WITH upsert AS ('.$upsert.' RETURNING *) '.$insert.' WHERE NOT EXISTS (SELECT * FROM upsert)' );
}
