<?php
namespace Blueline\CCCBRDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Blueline\CCCBRDataBundle\Entity\Methods
 *
 * @ORM\Table(name="methods")
 * @ORM\Entity(repositoryClass="Blueline\CCCBRDataBundle\Repository\MethodsRepository")
 */
class Methods {

	public function __construct() {
		$this->firstTowerbellPealTower = new \Doctrine\Common\Collections\ArrayCollection();
		$this->duplicates = new \Doctrine\Common\Collections\ArrayCollection();
	}

	public function toArray() {
		return array_filter( array(
			'title' => $this->getTitle(),
			'stage' => $this->getStage(),
			'classification' => $this->getClassification(),
			'nameMetaphone' => $this->getNameMetaphone(),
			'notation' => $this->getNotation(),
			'notationExpanded' => $this->getNotationExpanded(),
			'leadHeadCode' => $this->getLeadHeadCode(),
			'leadHead' => $this->getLeadHead(),
			'fchGroups' => $this->getFchGroups(),
			'rwRef' => $this->getRwRef(),
			'bnRef' => $this->getBnRef(),
			'tdmmRef' => $this->getTdmmRef(),
			'pmmRef' => $this->getPmmRef(),
			'lengthOfLead' => $this->getLengthOfLead(),
			'numberOfHunts' => $this->getNumberOfHunts(),
			'hunts' => $this->getHunts(),
			'little' => $this->getLittle(),
			'differential' => $this->getDifferential(),
			'plain' => $this->getPlain(),
			'trebleDodging' => $this->getTrebleDodging(),
			'palindromic' => $this->getPalindromic(),
			'double' => $this->getDoubleSym(),
			'rotational' => $this->getRotational(),
			'firstTowerbellPealDate' => $this->getFirstTowerbellPealDate()? $this->getFirstTowerbellPealDate()->format( 'r' ) : null,
			'firstTowerbellPealLocation' => $this->getFirstTowerbellPealLocation(),
			'firstHandbellPealDate' => $this->getFirstHandbellPealDate()? $this->getFirstHandbellPealDate()->format( 'r' ) : null,
//			'firstTowerbellPealTower' => $this->getFirstTowerbellPealTower()->toArray(),
		), function( $x ) { return !empty( $x ); } );
	}
	
	/**
	 * Generate a string for safe use in HTML id
	 */
	public function getID() {
		return  preg_replace( '/\s*/', '', preg_replace( '/[^a-z0-9]/', '', strtolower( $this->getTitle() ) ) );
	}
	
	/**
	 * @var string $title
	 *
	 * @ORM\Column(name="title", type="string", length=255, nullable=false)
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $title;
	public function getTitle() { return $this->title; }

	/**
	 * @var smallint $stage
	 *
	 * @ORM\Column(name="stage", type="smallint", nullable=false)
	 */
	private $stage;
	public function setStage( $stage ) { $this->stage = $stage; }
	public function getStage() { return $this->stage; }
	public function getStageText() { return \Blueline\Helpers\Stages::toString( $this->getStage() ); }

	/**
	 * @var string $classification
	 *
	 * @ORM\Column(name="classification", type="string", length=15, nullable=true)
	 */
	private $classification;
	public function setClassification( $classification ) { $this->classification = $classification; }
	public function getClassification() { return $this->classification; }

	/**
	 * @var string $nameMetaphone
	 *
	 * @ORM\Column(name="nameMetaphone", type="string", length=255, nullable=true)
	 */
	private $nameMetaphone;
	public function getNameMetaphone() { return $this->nameMetaphone; }

	/**
	 * @var string $notation
	 *
	 * @ORM\Column(name="notation", type="string", length=300, nullable=true)
	 */
	private $notation;
	public function setNotation( $notation ) { $this->notation = $notation; }
	public function getNotation() { return $this->notation; }

	/**
	 * @var text $notationExpanded
	 *
	 * @ORM\Column(name="notationExpanded", type="text", nullable=true)
	 */
	private $notationExpanded;
	public function setNotationExpanded( $notationExpanded ) {
		$this->notationExpanded = $notationExpanded;
		$this->notationExploded = false;
		$this->notationPermutations = false;
	}
	public function getNotationExpanded() { return $this->notationExpanded; }
	
	private $notationExploded;
	public function getNotationExploded() {
		if( !$this->notationExploded ) {
			$this->notationExploded = \Blueline\Helpers\PlaceNotation::explode( $this->getNotationExpanded() );
		}
		return $this->notationExploded;
	}
	private $notationPermutations;
	public function getNotationPermutations() {
		if( !$this->notationPermutations ) {
			$this->notationPermutations = \Blueline\Helpers\PlaceNotation::explodedToPermutations( $this->getStage(), $this->getNotationExploded() );
		}
		return $this->notationPermutations;
	}
	private $firstLead;
	public function getFirstLead() {
		if( !$this->firstLead ) {
			$this->firstLead = \Blueline\Helpers\PlaceNotation::apply( $this->getNotationPermutations(), \Blueline\Helpers\PlaceNotation::rounds( $this->getStage() ) );
		}
		return $this->firstLead;
	}

	/**
	 * @var string $leadHeadCode
	 *
	 * @ORM\Column(name="leadHeadCode", type="string", length=3, nullable=true)
	 */
	private $leadHeadCode;
	public function setLeadHeadCode( $leadHeadCode ) {
		$this->leadHeadCode = $leadHeadCode;
		$this->leadHead = \Blueline\Helpers\LeadHeadCodes::fromCode( $leadHeadCode, $this->getStage() )? : '';
		$this->leadHeads = false;
	}
	public function getLeadHeadCode() {
		return $this->leadHeadCode;
	}

	/**
	 * @var string $leadHead
	 *
	 * @ORM\Column(name="leadHead", type="string", length=25, nullable=true)
	 */
	private $leadHead;
	public function setLeadHead( $leadHead ) {
		$this->leadhead = $leadhead;
		$placeNotation = $this->getNotationExploded();
		$this->leadHeadCode = \Blueline\Helpers\LeadHeadCodes::toCode( $leadHead(), $this->getStage(), $this->getNumberOfHunts(), array_pop( $placeNotation ), array_shift( $placeNotation ) );
		$leadHeads = false;
	}
	public function getLeadHead() {
		return $this->leadHead;
	}
	private $leadHeads;
	public function getLeadHeads() {
		if( !$this->leadHeads ) {
			$rounds = \Blueline\Helpers\PlaceNotation::rounds( $this->getStage() );
			$tmp = str_split( $this->getLeadHead() );
			$leadHeadPermutation = array_map( function( $b ) { return \Blueline\Helpers\PlaceNotation::bellToInt( $b ) - 1; }, $tmp );
			$leadHeads = array( $tmp );
			while( !\Blueline\Helpers\PlaceNotation::rowsEqual( $rounds, $tmp ) ) {
				$tmp = \Blueline\Helpers\PlaceNotation::permute( $tmp, $leadHeadPermutation );
				array_push( $leadHeads, $tmp );
			}
			$this->leadHeads = $leadHeads;
		}
		return $this->leadHeads;
	}

	/**
	 * @var string $fchGroups
	 *
	 * @ORM\Column(name="fchGroups", type="string", length=25, nullable=true)
	 */
	private $fchGroups;
	public function setFchGroups( $fchgroups ) { $this->fchGroups = $fchGroups; }
	public function getFchGroups() { return $this->fchGroups; }

	/**
	 * @var string $rwRef
	 *
	 * @ORM\Column(name="rwRef", type="string", length=30, nullable=true)
	 */
	private $rwRef;
	public function setRwRef( $rwRef ) { $this->rwRef = $rwRef; }
	public function getRwRef() { return $this->rwRef; }

	/**
	 * @var string $bnRef
	 *
	 * @ORM\Column(name="bnRef", type="string", length=20, nullable=true)
	 */
	private $bnRef;
	public function setBnRef( $bnRef ) { $this->bnRef = $bnRef; }
	public function getBnRef() { return $this->bnRef; }

	/**
	 * @var smallint $tdmmRef
	 *
	 * @ORM\Column(name="tdmmRef", type="smallint", nullable=true)
	 */
	private $tdmmRef;
	public function setTdmmRef( $tdmmRef ) { $this->tdmmRef = $tdmmRef; }
	public function getTdmmRef() { return $this->tdmmRef; }

	/**
	 * @var smallint $pmmRef
	 *
	 * @ORM\Column(name="pmmRef", type="smallint", nullable=true)
	 */
	private $pmmRef;
	public function setPmmRef( $pmmRef ) { $this->pmmRef = $pmmRef; }
	public function getPmmRef() { return $this->pmmRef; }

	/**
	 * @var smallint $lengthOfLead
	 *
	 * @ORM\Column(name="lengthOfLead", type="smallint", nullable=true)
	 */
	private $lengthOfLead;
	public function setLengthOfLead( $lengthOfLead ) { $this->lengthOfLead = $lengthOfLead; }
	public function getLengthOfLead() { return $this->lengthOfLead; }

	/**
	 * @var boolean $numberOfHunts
	 *
	 * @ORM\Column(name="numberOfHunts", type="smallint", nullable=true)
	 */
	private $numberOfHunts;
	public function setNumberOfHunts( $numberOfHunts ) { $this->numberOfHunts = $numberOfHunts; }
	public function getNumberOfHunts() { return $this->numberOfHunts; }
	private $hunts;
	public function getHunts() {
		if( !$this->hunts ) {
			$hunts = array();
			$leadHead = array_map( function( $n ) { return \Blueline\Helpers\PlaceNotation::bellToInt( $n ); }, str_split( $this->getLeadHead() ) );
			for( $i = 0, $iLim = count( $leadHead ); $i < $iLim; ++$i ) {
				if( ($i+1) == $leadHead[$i] ) { array_push( $hunts, $leadHead[$i] ); }
			}
			$this->hunts = $hunts;
		}
		return $this->hunts;
	}

	/**
	 * @var boolean $little
	 *
	 * @ORM\Column(name="little", type="boolean", nullable=true)
	 */
	private $little;
	public function setLittle( $little ) { $this->little = $little; }
	public function getLittle() { return $this->little; }

	/**
	 * @var boolean $differential
	 *
	 * @ORM\Column(name="differential", type="boolean", nullable=true)
	 */
	private $differential;
	public function setDifferential( $differential ) { $this->differential = $differential; }
	public function getDifferential() { return $this->differential; }

	/**
	 * @var boolean $plain
	 *
	 * @ORM\Column(name="plain", type="boolean", nullable=true)
	 */
	private $plain;
	public function setPlain( $plain ) { $this->plain = $plain; }
	public function getPlain() { return $this->plain; }

	/**
	 * @var boolean $trebleDodging
	 *
	 * @ORM\Column(name="trebleDodging", type="boolean", nullable=true)
	 */
	private $trebleDodging;
	public function setTrebleDodging( $trebleDodging ) { $this->trebleDodging = $trebleDodging; }
	public function getTrebleDodging() { return $this->trebleDodging; }

	/**
	 * @var boolean $palindromic
	 *
	 * @ORM\Column(name="palindromic", type="boolean", nullable=true)
	 */
	private $palindromic;
	public function setPalindromic( $palindromic ) { $this->palindromic = $palindromic; }
	public function getPalindromic() { return $this->palindromic; }

	/**
	 * @var boolean $doubleSym
	 *
	 * @ORM\Column(name="doubleSym", type="boolean", nullable=true)
	 */
	private $doubleSym;
	public function setDoubleSym( $doubleSym ) { $this->doubleSym = $doubleSym; }
	public function getDoubleSym() { return $this->doubleSym; }

	/**
	 * @var boolean $rotational
	 *
	 * @ORM\Column(name="rotational", type="boolean", nullable=true)
	 */
	private $rotational;
	public function setRotational( $rotational ) { $this->rotational = $rotational; }
	public function getRotational() { return $this->rotational; }
	
	public function getSymmetryText() {
		return ucfirst( \Blueline\Helpers\Text::toList( array_filter( array( ($this->getPalindromic()?'palindromic':''), ($this->getDoublesym()?'double':''), ($this->getRotational()?'rotational':'') ) ) ) );
	}

	/**
	 * @var date $firstTowerbellPealDate
	 *
	 * @ORM\Column(name="firstTowerbellPeal_date", type="date", nullable=true)
	 */
	private $firstTowerbellPealDate;
	public function setFirstTowerbellPealDate( $firstTowerbellPealDate ) { $this->firstTowerbellPealDate = $firstTowerbellPealDate; }
	public function getFirstTowerbellPealDate() { return $this->firstTowerbellPealDate; }

	/**
	 * @var string $firstTowerbellPealLocation
	 *
	 * @ORM\Column(name="firstTowerbellPeal_location", type="string", length=255, nullable=true)
	 */
	private $firstTowerbellPealLocation;
	public function setFirstTowerbellPealLocation( $firstTowerbellPealLocation ) { $this->firstTowerbellPealLocation = $firstTowerbellPealLocation; }
	public function getFirstTowerbellPealLocation() { return $this->firstTowerbellPealLocation; }

	/**
	 * @var date $firstHandbellPealDate
	 *
	 * @ORM\Column(name="firstHandbellPeal_date", type="date", nullable=true)
	 */
	private $firstHandbellPealDate;
	public function setFirstHandbellPealDate( $firstHandbellPealDate ) { $this->firstHandbellPealDate = $firstHandbellPealDate; }
	public function getFirstHandbellPealDate() { return $this->firstHandbellPealDate; }

	/**
	 * @var Towers
	 *
	 * @ORM\ManyToMany(targetEntity="Towers", inversedBy="firstPealedMethods")
	 * @ORM\JoinTable(name="methods_towers",
	 *   joinColumns={
	 *     @ORM\JoinColumn(name="method_title", referencedColumnName="title")
	 *   },
	 *   inverseJoinColumns={
	 *     @ORM\JoinColumn(name="tower_doveid", referencedColumnName="doveid")
	 *   }
	 * )
	 */
	private $firstTowerbellPealTower;
	public function setFirstTowerbellPealTower( \Blueline\CCCBRDataBundle\Entity\Towers $firstTowerbellPealTower ) { $this->firstTowerbellPealTower = array( $firstTowerbellPealTower ); }
	public function getFirstTowerbellPealTower() { return $this->firstTowerbellPealTower[0]; }
	
	/**
	 * @ORM\OneToOne(targetEntity="MethodsExtras")
	 * @ORM\JoinColumn(name="title", referencedColumnName="method_title")
	 */
	private $extras;
	
	private $ruleOffs;
	public function getRuleOffs() {
		// Get rule offs from extras
		if( empty( $this->ruleOffs ) && is_a( $this->extras, 'Blueline\CCCBRDataBundle\Entity\MethodsExtras' ) && is_string( $this->extras->getRuleOffs() ) ) {
			if( preg_match( '/^([^:]*):([^:]*)$/', $this->extras->getRuleOffs(), $matches ) && isset( $matches[1], $matches[2] ) ) {
				$this->ruleOffs = array( 'every' => intval( $matches[1] ), 'from' => intval( $matches[2] ) );
			}
		}
		return $this->ruleOffs? : array( 'from' => 0, 'every' => $this->getLengthOfLead() );
	}

	private $calls;
	public function getCalls() {
		// Get calls from extras
		if( is_a( $this->extras, 'Blueline\CCCBRDataBundle\Entity\MethodsExtras' ) ) {
			$this->calls = $this->extras->getCalls();
		}
		
		// Set default calls
		if( empty( $this->calls ) ) {
			$stage = $this->getStage();
			$notationExploded = $this->getNotationExploded();
			if( !$this->getDifferential() && $stage > 4 ) {
				$leadEndChange = array_pop( $notationExploded );
				$postLeadEndChange = array_shift( $notationExploded );
				unset( $notationExploded ); // Prevent naive reuse
				$n = \Blueline\Helpers\PlaceNotation::intToBell( $stage );
				$n_1 = \Blueline\Helpers\PlaceNotation::intToBell( $stage - 1 );
				$n_2 = \Blueline\Helpers\PlaceNotation::intToBell( $stage - 2 );
				switch( $this->getNumberOfHunts() ) {
				case 0:
					if( $stage % 2 == 0 ) {
						if( $leadEndChange == '1'.$n ) {
							$this->calls = array( 'Bob' => '1'.$n_2.'::', 'Single' => '1'.$n_2.$n_1.$n.'::' );
						}
					}
					else {
					
					}
					break;
				case 1:
					if( $stage % 2 == 0 ) {
						if( $leadEndChange == '12' ) {
							$this->calls = array( 'Bob' => '14::', 'Single' => '1234::' );
						}
						elseif( $leadEndChange == '1'.$n ) {
							if( $this->getLeadHeadCode() == 'm' ) {
								$this->calls = array( 'Bob' => '14::', 'Single' => '1234::' );
							}
							else {
								$this->calls = array( 'Bob' => '1'.$n_2.'::', 'Single' => '1'.$n_2.$n_1.$n.'::' );
							}
						}
					}
					else {
						if( $leadEndChange == '12'.$n || $leadEndChange == '1' ) {
							$this->calls = array( 'Bob' => '14'.$n.'::', 'Single' => (($stage<6)?'123':'1234'.$n).'::' );
						}
						elseif( $leadEndChange == '123' ) {
							$this->calls = array( 'Bob' => '12'.$n.'::' );
						}
					}
					break;
				case 2:
					// Bobs and singles for Grandsire and Single Court like lead ends
					if( $leadEndChange == '1' && ( $postLeadEndChange == '3' || $postLeadEndChange == $n ) ) {
						$this->calls = array( 'Bob' => '3.1::-1', 'Single' => '3.23::-1' );
					}
					break;
				default:
					$this->calls = array();
				}
			}
		}
		
		// Parse the format
		$calls = $this->calls;
		if( is_array( $calls ) && count( $calls ) > 0 ) {
			foreach( $calls as $title => &$call ) {
				if( is_string( $call ) ) {
					if( preg_match( '/^([^:]*):([^:]*):([^:]*)$/', $call, $matches ) && isset( $matches[1] ) ) {
						$call = array( 'notation' => $matches[1], 'every' => intval( $matches[2]?:$this->getLengthOfLead() ), 'from' => intval( $matches[3] )?:0 );
					}
					else {
						unset( $calls[$title] );
					}
				}
			}
			$this->calls = $calls;
		}
		return $this->calls? : array();
	}

	private $callingPositions;
	public function getCallingPositions() {
		if( !$this->callingPositions ) {
			$stage = $this->getStage();
			$calls = $this->getCalls();
			$lengthOfLead = $this->getLengthOfLead();
			if( $stage < 6 || empty( $calls ) || !isset( $calls['Bob'] ) ) {
				$this->callingPositions = array();
			}
			else {
				// Calling positions for calls at lead ends (Home, Wrong and so forth)
				$bobNotation = \Blueline\Helpers\PlaceNotation::explodedToPermutations( $stage, \Blueline\Helpers\PlaceNotation::explode( $calls['Bob']['notation'] ), $stage );
				if( $calls['Bob']['every'] == $lengthOfLead && $calls['Bob']['from'] == 0 && count( $bobNotation ) == 1 ) {
					$leadHeads = $this->getLeadHeads();
					// Work out what the lead end of a bobbed lead looks like
					$notation = $this->getNotationPermutations();
					$notation[$lengthOfLead-1] = $bobNotation[0];
					$bobbedLead = \Blueline\Helpers\PlaceNotation::apply( $notation, \Blueline\Helpers\PlaceNotation::rounds( $stage ) );
					$bobbedLeadHeadPermutation = array_map( function( $b ) { return \Blueline\Helpers\PlaceNotation::bellToInt( $b ) - 1; }, array_pop( $bobbedLead ) );
					// Collect an array of what happens at each lead if a bob is called
					$bobbedLeadHeads = array( \Blueline\Helpers\PlaceNotation::permute( \Blueline\Helpers\PlaceNotation::rounds( $stage ), $bobbedLeadHeadPermutation ) );
					for( $i = 1; $i < count( $leadHeads ); $i++ ) {
						array_push( $bobbedLeadHeads, \Blueline\Helpers\PlaceNotation::permute( $leadHeads[$i-1], $bobbedLeadHeadPermutation ) );
					}
					// Convert the array of lead heads into calling position names
					$this->callingPositions = array( 'from' => 0, 'every' => $lengthOfLead, 'titles' => array_map( function( $leadEnd ) use( $stage ) {
						$position = array_search( \Blueline\Helpers\PlaceNotation::intToBell( $stage ), $leadEnd );
						switch( $position+1 ) {
							case 2:
								return 'I';
							case 3:
								return 'B';
							case 4:
								return 'F';
							case $stage-2:
								return 'M';
							case $stage-1:
								return 'W';
							case $stage:
								return 'H';
							case 5:
								return 'V';
						}
						return null;
					}, $bobbedLeadHeads ) );
				}
			}
		}
		return $this->callingPositions? : array();
	}
	
	/**
	 * @ORM\OneToMany(targetEntity="MethodsDuplicates", mappedBy="method")
	 */
	private $duplicates;
	public function addDuplicate( \Blueline\CCCBRDataBundle\Entity\MethodsDuplicates $duplicate ) { $this->duplicates[] = $duplicate; }
	public function getDuplicates() { return $this->duplicates; }
	
}
