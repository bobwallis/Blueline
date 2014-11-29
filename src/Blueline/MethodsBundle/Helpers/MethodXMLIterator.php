<?php
/*
 * This file is part of Blueline.
 * It parses the method XML files from http://methods.org.uk into Method entities and implements
 * the Iterator interface. Refer to ../Command/ImportMethodsCommand for usage.
 *
 * (c) Bob Wallis <bob.wallis@gmail.com>
 *
 */

namespace Blueline\MethodsBundle\Helpers;

use Blueline\MethodsBundle\Helpers\LeadHeadCodes;
use Blueline\MethodsBundle\Helpers\PlaceNotation;

class MethodXMLIterator implements \Iterator, \Countable
{
    private $data;

    public function __construct($file)
    {
        // Parse the XML data
        // TODO: Investigate a switch to XMLReader <http://www.php.net/manual/en/book.xmlreader.php> over SimpleXML.
        libxml_use_internal_errors( true );
        $XMLData = simplexml_load_file( $file );

        // Check for errors
        if (!$XMLData) {
            foreach ( libxml_get_errors() as $error ) {
                trigger_error( 'Error in '.basename( $file ).': Line '.$error->line.', column '.$error->column.'. '.$error->message.'.' );
                $this->data = array();

                return false;
            }
            libxml_clear_errors();
        }

        // Function to parse the SimpleXMLElements we'll encounter into an array of data
        $xmlToArray = function( \SimpleXMLElement $node, $array = array() ) {
            // Pull out the easy ones
            $array = array_merge( $array, array_filter( array(
                'url'            => strval( $node->title )? str_replace( [' ','$','&','+',',','/',':',';','=','?','@','"','<','>','#','%','{','}','|',"\\",'^','~','[',']','.'], ['_'], iconv( 'UTF-8', 'ASCII//TRANSLIT', strval( $node->title ) ) ):null,
                'title'          => strval( $node->title )?:null,
                'nameMetaphone'  => metaphone( $node->name?:'' )?:null,
                'notation'       => strval( $node->notation )?:null,
                'stage'          => intval( $node->stage )?:null,
                'classification' => strval( $node->classification )?:null,
                'numberOfHunts'  => $node->numberOfHunts? intval( $node->numberOfHunts ) : null,
                'lengthOfLead'   => intval( $node->lengthOfLead )?:null,
                'leadHead'       => strval( $node->leadHead )?:null,
                'leadHeadCode'   => strval( $node->leadHeadCode )?:null,
                'fchGroups'      => strval( $node->falseness->fchGroups )?:null
            ), function ($e) { return !is_null( $e ); } ) );

            // Parse place notation
            if ( isset( $array['notation'] ) ) {
                $array['notationExpanded'] = PlaceNotation::expand( $array['notation'], $array['stage'] );
            }

            // If needed, work out the lead head/lead head code
            if ( !isset( $array['leadHead'] ) && isset( $array['stage'], $array['leadHeadCode'] ) ) {
                $array['leadHead'] = LeadHeadCodes::fromCode( $array['leadHeadCode'], $array['stage'] );
            }
            if ( isset( $array['leadHead'], $array['stage'], $array['numberOfHunts'], $array['notation'] ) && !isset( $array['leadHeadCode'] ) ) {
                $explodedNotation = PlaceNotation::explode( $array['notationExpanded'] );
                $array['leadHeadCode'] = LeadHeadCodes::toCode( $array['leadHead'], $array['stage'], $array['numberOfHunts'], array_pop( $explodedNotation ), array_shift( $explodedNotation ) );
            }

            // Get additional classification attributes
            if ( isset( $node->classification ) ) {
                $array['little']        = ($node->classification->attributes()->little)? true : null;
                $array['differential']  = ($node->classification->attributes()->differential)? true : null;
                $array['plain']         = ($node->classification->attributes()->plain)? true : null;
                $array['trebleDodging'] = ($node->classification->attributes()->trebleDodging)? true : null;
            }

            // Get symmetry
            if ( isset( $node->symmetry ) ) {
                $array['palindromic'] = (strpos( strval( $node->symmetry ) , 'palindromic' ) !== false)? true : null;
                $array['doubleSym']   = (strpos( strval( $node->symmetry ) , 'double' ) !== false)? true : null;
                $array['rotational']  = (strpos( strval( $node->symmetry ) , 'rotational' ) !== false)? true : null;
            }

            // Get references
            if ( isset( $node->references ) ) {
                $array['rwRef']   = strval( $node->references->rwRef )?:null;
                $array['bnRef']   = strval( $node->references->bnRef )?:null;
                $array['tdmmRef'] = strval( $node->references->tdmmRef )?:null;
                $array['pmmRef']  = strval( $node->references->pmmRef )?:null;
            }

            // Get performance information
            if ( isset( $node->performances ) ) {
                $array['performances'] = array();
                if ( isset( $node->performances->firstTowerbellPeal ) ) {
                    $array['performances'][] = array_filter( array(
                        'type' => 'firstTowerbellPeal',
                        'date' => new \DateTime( $node->performances->firstTowerbellPeal->date ),
                        'location_room' => strval( $node->performances->firstTowerbellPeal->location->room )?:null,
                        'location_building' => strval( $node->performances->firstTowerbellPeal->location->building )?:null,
                        'location_address' => strval( $node->performances->firstTowerbellPeal->location->address )?:null,
                        'location_town' => strval( $node->performances->firstTowerbellPeal->location->town )?:null,
                        'location_county' => strval( $node->performances->firstTowerbellPeal->location->county )?:null,
                        'location_region' => strval( $node->performances->firstTowerbellPeal->location->region )?:null,
                        'location_country' => strval( $node->performances->firstTowerbellPeal->location->country )?:null
                    ), function ($e) { return !is_null( $e ); } );
                }
                if ( isset( $node->performances->firstHandbellPeal ) ) {
                    $array['performances'][] = array_filter( array(
                        'type' => 'firstHandbellPeal',
                        'date' => new \DateTime( $node->performances->firstHandbellPeal->date ),
                        'location_room' => strval( $node->performances->firstHandbellPeal->location->room )?:null,
                        'location_building' => strval( $node->performances->firstHandbellPeal->location->building )?:null,
                        'location_address' => strval( $node->performances->firstHandbellPeal->location->address )?:null,
                        'location_town' => strval( $node->performances->firstHandbellPeal->location->town )?:null,
                        'location_county' => strval( $node->performances->firstHandbellPeal->location->county )?:null,
                        'location_region' => strval( $node->performances->firstHandbellPeal->location->region )?:null,
                        'location_country' => strval( $node->performances->firstHandbellPeal->location->country )?:null
                    ), function ($e) { return !is_null( $e ); } );
                }
                require( __DIR__.'/../../TowersBundle/Resources/data/abbreviations.php' );
                // Remove abbreviations in the location data
                foreach( $array['performances'] as &$p ) {
                    // Fix countries
                    if( isset( $p['location_country'] ) && !in_array( $p['location_country'], $countries )) {
                        if( array_key_exists( $p['location_country'], $countries ) ) {
                            $p['location_country'] = $countries[$p['location_country']];
                        }
                    }
                    // Fix regions
                    $regionSearchArray = array();
                    if( isset( $p['location_region'], $p['location_country'] ) ) {
                        switch( $p['location_country'] ) {
                            case 'Australia':
                                $regionSearchArray = $australianAreas;
                                break;
                            case 'Canada':
                                $regionSearchArray = $canadianStates;
                                break;
                            case 'USA':
                                $regionSearchArray = $states;
                                break;
                        }
                        if( !in_array( $p['location_region'], $regionSearchArray ) ) {
                            if( array_key_exists( $p['location_region'], $regionSearchArray ) ) {
                                $p['location_region'] = $regionSearchArray[$p['location_region']];
                            }
                        }
                    }
                    // Fix counties
                    if( isset( $p['location_county'] ) && !in_array( $p['location_county'], $counties ) ) {
                        if( array_key_exists( $p['location_county'], $counties ) ) {
                            $p['location_county'] = $counties[$p['location_county']];
                        }
                    }
                }
            }

            // Filter out null items, and return
            return array_filter( $array, function ($e) { return !is_null( $e ); } );
        };

        // Iterate over <methodSet>s
        foreach ($XMLData->methodSet as $methodSet) {
            $sharedProperties = $xmlToArray( $methodSet->properties );
            // Iterate over <method>s in the method set
            foreach ($methodSet->method as $methodXML) {
                // Push data onto the array
                $this->data[] = $xmlToArray( $methodXML, $sharedProperties );
            }
        }
    }

    // Implement the Iterator interface by borrowing it from the underlying array
    public function rewind() { return reset( $this->data ); }
    public function current() { return current( $this->data ); }
    public function key() { return key( $this->data ); }
    public function next() { return next( $this->data ); }
    public function valid() { return key( $this->data ) !== null; }
    public function count() { return count( $this->data ); }
}
