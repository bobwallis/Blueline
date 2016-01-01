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

        // Fields
        $searchVariables['fields'] = $request->query->get('fields')? array_filter(array_map('trim', explode(',', $request->query->get('fields'))), function($f) use ($searchable) { return in_array($f, $searchable); }) : array();

        // Conditions
        foreach (array_merge(array( 'q', 'sort', 'order' ), $searchable) as $key) {
            $value = trim($request->query->get($key));
            if (!empty($value)) {
                $searchVariables[$key] = $value;
            }
        }

        // Order
        $searchVariables['order'] = strtoupper(empty($searchVariables['order'])? 'asc' : $searchVariables['order']);
        if ($searchVariables['order'] != 'DESC') {
            $searchVariables['order'] = 'ASC';
        }

        // Offset
        $searchVariables['offset'] = intval($request->query->get('offset'));
        if (empty($searchVariables['offset']) || $searchVariables['offset'] < 0) {
            $searchVariables['offset'] = 0;
        }

        // Count
        $searchVariables['count'] = intval($request->query->get('count'));
        if (empty($searchVariables['count']) || $searchVariables['count'] <= 0) {
            $searchVariables['count'] = 15;
        }

        return $searchVariables;
    }

    public static function searchVariablesToBasicQuery($searchVariables, $entityRepository, $entityMetadata)
    {
        $query = $entityRepository->createQueryBuilder('e')->select('partial e.{'.implode(',', $searchVariables['fields']).'}');

        // Sort/Order
        $query->orderBy('e.'.$searchVariables['sort'], $searchVariables['order']);

        // Offset
        if (isset($searchVariables['offset'])) {
            $query->setFirstResult($searchVariables['offset']);
        }
        // Count
        if (isset($searchVariables['count'])) {
            $query->setMaxResults($searchVariables['count']);
        }

        // String variables
        foreach (array_keys(array_filter($entityMetadata->fieldMappings, function($f) { return in_array($f['type'], array('string', 'text')); })) as $key) {
            if (isset($searchVariables[$key])) {
                if (strpos($searchVariables[$key], '/') === 0 && strlen($searchVariables[$key]) > 1) {
                    $query->andWhere('REGEXP(e.'.$key.', :'.$key.'Regexp) = TRUE')
                        ->setParameter($key.'Regexp', trim($searchVariables[$key], '/'));
                } else {
                    $query->andWhere('LOWER(e.'.$key.') LIKE :'.$key.'Like')
                        ->setParameter($key.'Like', Search::prepareStringForLike($searchVariables[$key]));
                }
            }
        }

        // Number variables
        foreach (array_keys(array_filter($entityMetadata->fieldMappings, function($f) { return in_array($f['type'], array('smallint','integer','bigint','decimal','float')); })) as $key) {
            if (isset($searchVariables[$key])) {
                $splitValues = preg_split('/,(?![^(\[]*[)\]])/', $searchVariables[$key]);
                $splitValuesDQL = array();
                $splitValuesParams = array();
                foreach ($splitValues as $i => $v) {
                    // Interval notation (for ranges), e.g. [0,2), [3,4]
                    if ($v{0} == '[' || $v{0} == '(') {
                        $c1 = $v{0} == '['? ' >= ' : ' > ';
                        $c2 = substr($v, -1) == ']'? ' <=' : ' < ';
                        $vs = explode(',', substr($v, 1, strlen($v)-2));
                        $splitValuesDQL[] = $query->expr()->andx('e.'.$key.$c1.':'.$key.$i.'lower', 'e.'.$key.$c2.':'.$key.$i.'upper');
                        $splitValuesParams[$key.$i.'lower'] = intval($vs[0]);
                        $splitValuesParams[$key.$i.'upper'] = intval($vs[1]);
                    // Or just single numbers
                    } else {
                        $splitValuesDQL[] = 'e.'.$key.' = :'.$key.$i;
                        $splitValuesParams[$key.$i] = intval($v);
                    }
                }
                if (count($splitValuesDQL) > 0) {
                    $query->andWhere($query->expr()->orx()->addMultiple($splitValuesDQL));
                    foreach ($splitValuesParams as $k => $v) {
                        $query->setParameter($k, $v);
                    }
                }
            }
        }

        // Boolean variables
        foreach (array_keys(array_filter($entityMetadata->fieldMappings, function($f) { return in_array($f['type'], array('boolean')); })) as $key) {
            if (isset($searchVariables[$key])) {
                $query->andWhere('e.'.$key.(filter_var($searchVariables[$key], FILTER_VALIDATE_BOOLEAN)?' = TRUE':' = FALSE'));
            }
        }

        return $query;
    }

    public static function queryToCountQuery($query, $entityMetadata)
    {
        $query->select('COUNT(e.'.$entityMetadata->identifier[0].')')
            ->resetDqlPart('orderBy')
            ->setFirstResult(0)
            ->setMaxResults(null);

        return $query;
    }
};
