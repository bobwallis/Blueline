<?php
namespace Blueline\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;

class DataController extends AbstractController
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[Cache(maxage: 604800, public: true, lastModified: 'request.attributes.get("database_update")')]
    public function table($table, Request $request)
    {
        $response = new StreamedResponse();

        // Set content
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$table.'.csv"');
        $response->setCallback(function () use ($table) {
            switch ($table) {
                case 'collections':
                    $sql = 'SELECT id, name, description
                              FROM collections
                             ORDER BY id ASC';
                    break;
                case 'methods':
                    $sql = 'SELECT *
                              FROM methods
                             ORDER BY stage, classification, title ASC';
                    break;
                case 'methods_collections':
                    $sql = 'SELECT collection_id as id, position, method_title
                              FROM methods_collections
                             ORDER BY id, position ASC';
                    break;
                case 'methods_similar':
                    $sql = 'SELECT method1_title, method2_title, stage, similarity, onlydifferentoverleadend as onlyDifferentOverLeadEnd
                              FROM methods_similar
                              JOIN methods ON (title = method1_title)
                             ORDER BY stage, method1_title ASC';
                    break;
                case 'performances':
                    $sql = 'SELECT *
                              FROM performances
                             ORDER BY method_title ASC';
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown table: '.$table);
            }

            try {
                $result = $this->connection->executeQuery($sql);
            }
            catch (Exception $exception) {
                throw new \RuntimeException('Failed to query table: '.$table, 0, $exception);
            }

            $firstRow = $result->fetchAssociative();
            if ($firstRow === false) {
                return;
            }

            $handle = fopen('php://output', 'w+');

            // Output the header row
            fputcsv($handle, array_keys($firstRow), ',', '"', '\\', "\n");
            fputcsv($handle, $firstRow, ',', '"', '\\', "\n");

            // Output the rest
            $i = 1;
            while (($row = $result->fetchAssociative()) !== false) {
                fputcsv($handle, $row, ',', '"', '\\', "\n");
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
