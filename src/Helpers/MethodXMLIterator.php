<?php

namespace Blueline\Helpers;

use Blueline\Helpers\Country;
use Blueline\Helpers\LongCounty;

/**
 * Parses method XML files into Method entities and implements the Iterator
 * interface for streaming parsing.
 *
 * Converts XML elements to Method entity data with automatic:
 * - Classification and abbreviation lookup
 * - Lead head code resolution
 * - Collection assignment via method ID matching
 * - Provisional method flag for naming variants
 *
 * Used by ImportMethodsCommand for bulk method import.
 *
 * @see ImportMethodsCommand for usage
 * @TODO: Consider switching from SimpleXML to XMLReader for better memory efficiency on large files
 *
 */
class MethodXMLIterator implements \Iterator, \Countable
{
    private $data;

    /**
     * Parse a methods XML file and prepare iterable rows for import.
     *
     * @param string $file Absolute path to methods XML file
     */
    public function __construct($file)
    {
        // Prepare methodCollections data for easier method sorting
        require __DIR__.'/../Resources/data/collections.php';
        $methodCollections = array();
        foreach ($collections as $collection) {
            $methodCollections[$collection['id']] = $collection['methods'];
        }

        // Flag for if we're iterating over the "provisionally named" methods collection
        $provisional = (preg_match('/provisional\.xml$/', $file) === 1);

        // Parse the XML data
        // TODO: Investigate a switch to XMLReader <http://www.php.net/manual/en/book.xmlreader.php> over SimpleXML.
        libxml_use_internal_errors(true);
        $XMLData = simplexml_load_file($file);

        // Check for errors
        if (!$XMLData) {
            foreach (libxml_get_errors() as $error) {
                trigger_error('Error in '.basename($file).': Line '.$error->line.', column '.$error->column.'. '.$error->message.'.');
                $this->data = array();

                return false;
            }
            libxml_clear_errors();
        }

        // Function to parse the SimpleXMLElements we'll encounter into an array of data
        $xmlToArray = function (\SimpleXMLElement $node, $array = array()) use ($methodCollections, $provisional) {
            // Pull out the easy ones
            $array = array_merge($array, array(
                'provisional'    => $provisional,
                'url'            => strval($node->title) ? str_replace([' ', '$', '&', '+', ',', '/', ':', ';', '=', '?', '@', '"', "'", '<', '>', '#', '%', '{', '}', '|', "\\", '^', '~', '[', ']', '.'], ['_'], iconv('UTF-8', 'ASCII//TRANSLIT', strval($node->title))) : null,
                'title'          => strval($node->title) ?: null,
                'nameMetaphone'  => metaphone($node->name ?: '') ?: null,
                'notation'       => str_replace(['-','.,'], ['x',','], strval($node->notation)) ?: null,
                ), array_filter(array(
                'stage'          => intval($node->stage) ?: null,
                'classification' => strval($node->classification) ?: null,
                'numberOfHunts'  => $node->numberOfHunts ? intval($node->numberOfHunts) : null,
                'lengthOfLead'   => intval($node->lengthOfLead) ?: null,
                'leadHead'       => strval($node->leadHead) ?: null,
                'leadHeadCode'   => strval($node->leadHeadCode) ?: null,
                'fchGroups'      => strval($node->falseness->fchGroups) ?: null,
                'cccbrId'        => strval($node->attributes()->id) ?: null,
                'extensionConstruction' => strval($node->extensionConstruction) ?: null,
            ), function ($e) {
                return !is_null($e);
            }));

            // Manually fix non-unique URLs
            if ($array['title'] == '"Northumberland" Surprise Major') {
                $array['url'] = '_Northumberland__Surprise_Major';
            }
            if ($array['title'] == '"Weybridge" Surprise Major') {
                $array['url'] = '_Weybridge__Surprise_Major';
            }
            if ($array['title'] == '"Red Kite" Surprise Major') {
                $array['url'] = '_Red Kite__Surprise_Major';
            }
            if ($array['title'] == '"Easter" Delight Major') {
                $array['url'] = '_Easter__Surprise_Major';
            }

            // Parse place notation
            if (isset($array['notation'])) {
                $array['notationExpanded'] = PlaceNotation::expand($array['notation'], $array['stage']);
            }

            // If needed, work out the lead head/lead head code
            if (!isset($array['leadHead']) && isset($array['stage'], $array['leadHeadCode'])) {
                $array['leadHead'] = LeadHeadCodes::fromCode($array['leadHeadCode'], $array['stage']);
            }
            if (isset($array['leadHead'], $array['stage'], $array['notation']) && !isset($array['leadHeadCode'])) {
                $explodedNotation = PlaceNotation::explode($array['notationExpanded']);
                $array['leadHeadCode'] = LeadHeadCodes::toCode($array['leadHead'], $array['stage'], array_pop($explodedNotation), array_shift($explodedNotation));
            }

            // Get additional classification attributes
            if (isset($node->classification)) {
                $classificationText     = trim(strval($node->classification));
                $array['little']        = ($node->classification->attributes()->little) ? true : null;
                $array['differential']  = ($node->classification->attributes()->differential) ? true : null;
                $array['plain']         = ($node->classification->attributes()->plain) ? true : null;
                $array['trebleDodging'] = ($node->classification->attributes()->trebleDodging) ? true : null;
                // Jump methods don't have an attribute, so take from the text
                $array['jump']          = (preg_match('/^Jump\b/', $classificationText) === 1) ? true : null;
            }

            // Get symmetry
            if (isset($node->symmetry)) {
                $array['palindromic'] = (strpos(strval($node->symmetry), 'palindromic') !== false) ? true : null;
                $array['doubleSym']   = (strpos(strval($node->symmetry), 'double') !== false) ? true : null;
                $array['rotational']  = (strpos(strval($node->symmetry), 'rotational') !== false) ? true : null;
            }

            // Get references
            if (isset($node->references)) {
                $refParts = [];
                foreach ($node->references->children() as $refChild) {
                    $val = strval($refChild);
                    if ($val !== '') {
                        $refParts[] = $refChild->getName().': '.$val;
                    }
                }
                $array['methodReferences'] = $refParts ? implode('; ', $refParts) : null;
            }

            // Get performance information
            if (isset($node->performances)) {
                $array['performances'] = array();
                foreach ($node->performances->children() as $perfNode) {
                    $perfType = $perfNode->getName();
                    $perf = array_filter(array(
                        'method_title' => $array['title'],
                        'type' => $perfType,
                        'date' => isset($perfNode->date) ? (new \DateTime(strval($perfNode->date)))->format('Y-m-d') : null,
                        'society' => strval($perfNode->society) ?: null,
                        'location_room' => strval($perfNode->location->room) ?: null,
                        'location_building' => strval($perfNode->location->building) ?: null,
                        'location_address' => strval($perfNode->location->address) ?: null,
                        'location_town' => strval($perfNode->location->town) ?: null,
                        'location_county' => strval($perfNode->location->county) ?: null,
                        'location_region' => strval($perfNode->location->region) ?: null,
                        'location_country' => strval($perfNode->location->country) ?: null,
                    ), function ($e) {
                        return !is_null($e);
                    });
                    if (isset($perfNode->references)) {
                        $perfRefParts = [];
                        foreach ($perfNode->references->children() as $refChild) {
                            $val = strval($refChild);
                            if ($val !== '') {
                                $perfRefParts[] = $refChild->getName().': '.$val;
                            }
                        }
                        if ($perfRefParts) {
                            $perf['reference'] = implode('; ', $perfRefParts);
                        }
                    }
                    $array['performances'][] = $perf;
                }
                // Remove abbreviations in the location data
                foreach ($array['performances'] as &$p) {
                    // Fix countries
                    if (isset($p['location_country']) && !in_array($p['location_country'], Country::$iso3166_2)) {
                        $p['location_country'] = Country::$iso3166_2[$p['location_country']];
                    }
                    // Fix regions
                    $regionSearchArray = array();
                    if (isset($p['location_region'])) {
                        $p['location_region'] = LongCounty::get($p['location_region'], isset($p['location_country']) ? $p['location_country'] : 'England');
                    }
                    // Fix counties
                    if (isset($p['location_county'])) {
                        $p['location_county'] = LongCounty::get($p['location_county'], isset($p['location_country']) ? $p['location_country'] : 'England');
                    }
                }
            }

            // Work out what the sort code should be if we're looking at a method
            if (isset($array['title'])) {
                $sort = 32000;
                // Favour certain classifications
                if (isset($array['classification'])) {
                    switch ($array['classification']) {
                        case "Surprise":
                            $sort *= 0.91;
                            break;
                        case "Bob":
                            $sort *= 0.92;
                            break;
                        case "Delight":
                            $sort *= 0.93;
                            break;
                        case "Treble Bob":
                        case "Alliance":
                            $sort *= 0.95;
                            break;
                        case "Treble Place":
                        case "Place":
                        case "Slow Course":
                            $sort *= 0.97;
                            break;
                        case "Hybrid":
                            $sort *= 0.99;
                    }
                }
                // Favour certain stages
                if ($array['stage'] <= 4) {
                    $sort *= 0.96;
                } elseif ($array['stage'] <= 6) {
                    $sort *= 0.92;
                } elseif ($array['stage'] <= 8) {
                    $sort *= 0.90;
                } elseif ($array['stage'] <= 10) {
                    $sort *= 0.92;
                } elseif ($array['stage'] <= 12) {
                    $sort *= 0.94;
                } elseif ($array['stage'] <= 16) {
                    $sort *= 0.96;
                }
                // Push methods in certain collections up the list
                if (in_array($array['title'], $methodCollections['Standard8']) || in_array($array['title'], $methodCollections['Standard41']) || in_array($array['title'], $methodCollections['Nottingham8']) || in_array($array['title'], $methodCollections['LBFG8']) || in_array($array['title'], $methodCollections['ProjectPickledEgg']) || in_array($array['title'], $methodCollections['HigherRank'])) {
                    $sort *= 0.5;
                }
                if (in_array($array['title'], $methodCollections['Smiths23']) || in_array($array['title'], $methodCollections['Pitmans9']) || in_array($array['title'], $methodCollections['CroslandsAlphabet'])) {
                    $sort *= 0.9;
                }
                if (in_array($array['title'], $methodCollections['Diagrams'])) {
                    $sort *= 0.8;
                }
                if (in_array($array['title'], $methodCollections['mostViewed200'])) {
                    $sort *= 0.95;
                }
                $array['magic'] = intval($sort);
            }

            return $array;
        };

        // Iterate over <methodSet>s
        foreach ($XMLData->methodSet as $methodSet) {
            $sharedProperties = $xmlToArray($methodSet->properties);
            // Iterate over <method>s in the method set
            foreach ($methodSet->method as $methodXML) {
                // Push data onto the array
                $this->data[] = $xmlToArray($methodXML, $sharedProperties);
            }
        }
    }

    // Implement the Iterator interface by borrowing it from the underlying array
    public function rewind(): void
    {
        reset($this->data);
    }
    public function current(): mixed
    {
        return current($this->data);
    }
    public function key(): mixed
    {
        return key($this->data);
    }
    public function next(): void
    {
        next($this->data);
    }
    public function valid(): bool
    {
        return key($this->data) !== null;
    }
    public function count(): int
    {
        return count($this->data);
    }
}
