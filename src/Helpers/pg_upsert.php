<?php
namespace Blueline\Helpers;

function pg_upsert($connection, $table_name, $data, $condition)
{
    if (($data       = pg_convert($connection, $table_name, $data)) === false)     { return false; }
    if (($condition = pg_convert($connection, $table_name, $condition)) === false) { return false; }
    $table_name = pg_escape_identifier($connection, $table_name);

    $dataAndCondition = array_merge($data, $condition);
    $insertKeys = implode(', ', array_keys($dataAndCondition));
    $insertValues = implode(', ', array_values($dataAndCondition));

    $updateSet = array();
    foreach ($data as $key => $val) {
        $updateSet[] = $key.'='.$val;
    }
    $updateSet = implode(', ', $updateSet);

    $updateConditions = array();
    foreach ($condition as $key => $val) {
        $updateConditions[] = $key.'='.$val;
    }
    $updateConditions = implode(' AND ', $updateConditions);

    $insert = 'INSERT INTO '.$table_name.' ('.$insertKeys.') SELECT '.$insertValues;
    $upsert = 'UPDATE '.$table_name.' SET '.$updateSet.' WHERE '.$updateConditions;

    return @pg_query($connection, 'WITH upsert AS ('.$upsert.' RETURNING *) '.$insert.' WHERE NOT EXISTS (SELECT * FROM upsert)');
}
