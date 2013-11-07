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
use Blueline\MethodsBundle\Helpers\Stages;

class MethodXMLIterator implements \Iterator {
	private $data;

	public function __construct( $file ) {
		// Parse the XML data
		// TODO: Investigate a switch to XMLReader <http://www.php.net/manual/en/book.xmlreader.php> over SimpleXML.
		libxml_use_internal_errors( true );
		$XMLData = simplexml_load_file( $file );

		// Check for errors
		if( !$XMLData ) {
			foreach( libxml_get_errors() as $error ) {
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
			), function( $e ) { return !is_null( $e ); } ) );

			// Parse place notation
			if( isset( $array['notation'] ) ) {
				$array['notationExpanded'] = PlaceNotation::expand( $array['stage'], $array['notation'] );
			}

			// If needed, work out the lead head/lead head code
			if( !isset( $array['leadHead'] ) && isset( $array['stage'], $array['leadHeadCode'] ) ) {
				$array['leadHead'] = LeadHeadCodes::fromCode( $array['leadHeadCode'], $array['stage'] );
			}
			if( isset( $array['leadHead'], $array['stage'], $array['numberOfHunts'], $array['notation'] ) && !isset( $array['leadHeadCode'] ) ) {
				$explodedNotation = PlaceNotation::explode( $array['notationExpanded'] );
				$array['leadHeadCode'] = LeadHeadCodes::toCode( $array['leadHead'], $array['stage'], $array['numberOfHunts'], array_pop( $explodedNotation ), array_shift( $explodedNotation ) );
			}

			// Get additional classification attributes
			if( isset( $node->classification ) ) {
				$array['little']        = ($node->classification->attributes()->little)? true : null;
				$array['differential']  = ($node->classification->attributes()->differential)? true : null;
				$array['plain']         = ($node->classification->attributes()->plain)? true : null;
				$array['trebleDodging'] = ($node->classification->attributes()->trebleDodging)? true : null;
			}

			// Get symmetry
			if( isset( $node->symmetry ) ) {
				$array['palindromic'] = (strpos( strval( $node->symmetry ) , 'palindromic' ) !== false)? true : null;
				$array['doubleSym']   = (strpos( strval( $node->symmetry ) , 'double' ) !== false)? true : null;
				$array['rotational']  = (strpos( strval( $node->symmetry ) , 'rotational' ) !== false)? true : null;
			}

			// Get references
			if( isset( $node->references ) ) {
				$array['rwRef']   = strval( $node->references->rwRef )?:null;
				$array['bnRef']   = strval( $node->references->bnRef )?:null;
				$array['tdmmRef'] = $node->references->tdmmRef? intval( $node->references->tdmmRef ) : null;
				$array['pmmRef']  = $node->references->pmmRef? intval( $node->references->pmmRef ) : null;
			}

			// Get performance information
			if( isset( $node->performances ) ) {
				if( isset( $node->performances->firstTowerbellPeal ) ) {
					$array['firstTowerbellPeal_date'] = new \DateTime( strval( $node->performances->firstTowerbellPeal->date ) );
					if( isset( $node->performances->firstTowerbellPeal->location ) ) {
						$array['firstTowerbellPeal_location'] = implode( ', ', get_object_vars( $node->performances->firstTowerbellPeal->location ) );
					}
				}
				if( isset( $node->performances->firstHandbellPeal ) ) {
					$array['firstHandbellPeal_date'] = new \DateTime( strval( $node->performances->firstHandbellPeal->date ) );
					if( isset( $node->performances->firstHandbellPeal->location ) ) {
						trigger_warning( 'There\'s locaton data for a handbell peal in "'.basename( $file ).'", method "'.$array['title'].'". The database needs a new column.' );
					}
				}
			}

			// Filter out null items, and return
			return array_filter( $array, function( $e ) { return !is_null( $e ); } );
		};

		// Iterate over <methodSet>s
		foreach( $XMLData->methodSet as $methodSet ) {
			$sharedProperties = $xmlToArray( $methodSet->properties );
			// Iterate over <method>s in the method set
			foreach( $methodSet->method as $methodXML ) {
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
}