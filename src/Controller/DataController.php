<?php
namespace Blueline\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Blueline\Helpers\PgResultIterator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
* @Cache(maxage="604800", public=true, lastModified="database_update")
*/
class DataController extends AbstractController
{
    public function table($table, Request $request, ParameterBagInterface $params)
    {
        $response = new StreamedResponse();

        // Block Dove data
        if ($table == 'towers' || $table == 'towers_associations' || $table == 'towers_oldpks') {
            throw $this->createAccessDeniedException('Data tables generated from the Dove data file cannot be exported.');
        }

        // Get database
        $db = pg_connect($params->get('database_connect'));
        if( $db === false ) {
            throw new \Exception('Failed to connect to database.');
            return;
        }

        // Set content
        $response->headers->set('Content-Type', 'text/csv');
        $response->setCallback(function () use ($table, $db) {
            switch ($table) {
                case 'associations':
                    $result = pg_query( $db,
                        'SELECT id, name, link
                          FROM associations
                         ORDER BY id ASC'
                    );
                    break;
                case 'collections':
                    $result = pg_query( $db,
                        'SELECT id, name, description
                          FROM collections
                         ORDER BY id ASC'
                    );
                    break;
                case 'methods':
                    $result = pg_query( $db,
                        'SELECT *
                          FROM methods
                         ORDER BY stage, classification, title ASC'
                    );
                    break;
                case 'methods_collections':
                    $result = pg_query( $db,
                        'SELECT collection_id as id, position, method_title
                          FROM methods_collections
                         ORDER BY id, position ASC'
                    );
                    break;
                case 'methods_similar':
                    $result = pg_query( $db,
                        'SELECT method1_title, method2_title, stage, similarity, onlydifferentoverleadend as onlyDifferentOverLeadEnd
                          FROM methods_similar
                          JOIN methods ON (title = method1_title)
                         ORDER BY stage, method1_title ASC'
                    );
                    break;
                case 'performances':
                    $result = pg_query( $db,
                        'SELECT *
                          FROM performances
                         ORDER BY method_title ASC'
                    );
                    break;
            }
            if( $result === false ) {
                throw new \Exception('Failed to query table: '.$table);
                return;
            }

            // Get data and output handle
            $data = new PgResultIterator( $result );
            $handle = fopen('php://output', 'w+');

            // Output the header row
            fputcsv($handle, array_keys($data->current()));

            // Output the rest
            $data->rewind();
            $i = 0;
            foreach ($data as $row) {
                fputcsv($handle, $row);
                if ($i++ % 50 == 0) {
                    flush();
                }
            }

            // Cleanup
            fclose($handle);
        });

        return $response;
    }
}
