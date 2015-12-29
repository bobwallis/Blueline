<?php
namespace Blueline\BluelineBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
* @Cache(maxage="604800", public=true, lastModified="database_update")
*/
class DataController extends Controller
{
    public function tableAction($table, Request $request)
    {
        $container = $this->container;
        $response = new StreamedResponse();

        // Block Dove data
        if ($table == 'towers' || $table == 'towers_associations' || $table == 'towers_oldpks') {
            throw $this->createAccessDeniedException('Data tables generated from the Dove data file cannot be exported.');
        }

        // Set content
        $response->headers->set('Content-Type', 'text/csv');
        $response->setCallback(function () use ($table, $container) {
            $em = $container->get('doctrine')->getManager();
            switch ($table) {
                case 'associations':
                    $query  = $em->createQuery('SELECT partial a.{id,name,link} FROM Blueline\AssociationsBundle\Entity\Association a ORDER BY a.id ASC');
                    break;
                case 'collections':
                    $query  = $em->createQuery('SELECT c FROM Blueline\MethodsBundle\Entity\Collection c ORDER BY c.id ASC');
                    break;
                case 'methods':
                    $query  = $em->createQuery('SELECT m FROM Blueline\MethodsBundle\Entity\Method m ORDER BY m.title ASC');
                    break;
                case 'methods_collections':
                    $query  = $em->createQuery('SELECT partial m.{title}, mc, partial c.{id} FROM Blueline\MethodsBundle\Entity\Method m JOIN m.collections mc JOIN mc.collection c ORDER BY c.id, mc.position ASC');
                    break;
                case 'performances':
                    $query  = $em->createQuery('SELECT p, partial m.{title}, partial t.{id} FROM Blueline\MethodsBundle\Entity\Performance p JOIN p.method m JOIN p.location_tower t ORDER BY p.id ASC');
                    break;
                case 'towers':
                    $query  = $em->createQuery('SELECT t FROM Blueline\TowersBundle\Entity\Tower t ORDER BY t.id ASC');
                    break;
                case 'towers_associations':
                    $query  = $em->createQuery('SELECT partial t.{id}, partial a.{id} FROM Blueline\TowersBundle\Entity\Tower t JOIN t.associations a ORDER BY t.id ASC');
                    break;
                case 'towers_oldpks':
                    $query  = $em->createQuery('SELECT partial t.{id}, partial o.{oldpk} FROM Blueline\TowersBundle\Entity\Tower t JOIN t.oldpks o ORDER BY t.id ASC');
                    break;
            }
            $result = $query->getArrayResult();
            $i = 0;
            if (isset($result[0])) {
                $handle = fopen('php://output', 'r+');
                switch ($table) {
                    case 'associations':
                    case 'collections':
                    case 'towers':
                        fputcsv($handle, array_keys($result[0]));
                        do {
                            fputcsv($handle, $result[$i]);
                            if( $i % 50 == 0 ) { flush(); }
                        } while (isset($result[++$i]));
                        break;
                    case 'methods':
                        fputcsv($handle, array_keys($result[0]));
                        do {
                            array_walk($result[$i], function (&$v, $k) use ($table) {
                                switch ($k) {
                                    case 'calls':
                                    case 'ruleOffs':
                                    case 'callingPositions':
                                        $v = json_encode($v)?: '';
                                        break;
                                }
                            });
                            if( $i % 50 == 0 ) { flush(); }
                            fputcsv($handle, $result[$i]);
                        } while (isset($result[++$i]));
                        break;
                    case 'methods_collections':
                        fputcsv($handle, array('collection_id', 'position', 'method_title'));
                        do {
                            foreach ($result[$i]['collections'] as $methodInCollection) {
                                fputcsv($handle, array($methodInCollection['collection']['id'], $methodInCollection['position'], $result[$i]['title']));
                            }
                        } while (isset($result[++$i]));
                        break;
                    case 'performances':
                        fputcsv($handle, array_keys($result[0]));
                        do {
                            array_walk($result[$i], function (&$v, $k) use ($table) {
                                switch ($k) {
                                    case 'date':
                                        $v = $v->format('Y-m-d');
                                        break;
                                    case 'method':
                                        $v = $v['title'];
                                        break;
                                    case 'location_tower':
                                        $v = $v['id'];
                                        break;
                                }
                            });
                            fputcsv($handle, $result[$i]);
                            if( $i % 50 == 0 ) { flush(); }
                        } while (isset($result[++$i]));
                        break;
                    case 'towers_associations':
                        fputcsv($handle, array('tower_id', 'association_id'));
                        do {
                            foreach ($result[$i]['associations'] as $association) {
                                fputcsv($handle, array($result[$i]['id'], $association['id']));
                            }
                            if( $i % 50 == 0 ) { flush(); }
                        } while (isset($result[++$i]));
                        break;
                    case 'towers_oldpks':
                        fputcsv($handle, array('tower_id', 'oldpk'));
                        do {
                            foreach ($result[$i]['oldpks'] as $oldpk) {
                                fputcsv($handle, array($result[$i]['id'], $oldpk['oldpk']));
                            }
                            if( $i % 50 == 0 ) { flush(); }
                        } while (isset($result[++$i]));
                        break;
                }
                fclose($handle);
            }
        });

        return $response;
    }
}