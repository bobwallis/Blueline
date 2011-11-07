<?php

namespace Blueline\CCCBRDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Blueline\CCCBRDataBundle\Entity\Towers
 *
 * @ORM\Table(name="towers")
 * @ORM\Entity(repositoryClass="Blueline\CCCBRDataBundle\Repository\TowersRepository")
 */
class Towers {

	public function __construct() {
		$this->affiliations = new \Doctrine\Common\Collections\ArrayCollection();
		$this->firstPealedMethods = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
	public function toArray() {
		return array_filter( array(
			'doveid' => $this->getDoveid(),
			'gridReference' => $this->getGridReference(),
			'latitude' => $this->getLatitude(),
			'longitude' => $this->getLongitude(),
			'latitudeSatNav' => $this->getLatitudeSatNav(),
			'longitudeSatNav' => $this->getLongitudeSatNav(),
			'postcode' => $this->getPostcode(),
			'country' => $this->getCountry(),
			'county' => $this->getCounty(),
			'diocese' => $this->getDiocese(),
			'place' => $this->getPlace(),
			'altName' => $this->getAltName(),
			'dedication' => $this->getDedication(),
			'bells' => $this->getBells(),
			'weight' => $this->getWeight(),
			'weightApprox' => $this->getWeightApprox(),
			'weightText' => $this->getWeightText(),
			'note' => $this->getNote(),
			'hz' => $this->getHz(),
			'practiceNight' => $this->getPracticeNight(),
			'practiceStart' => $this->getPracticeStart(),
			'practiceNotes' => $this->getPracticeNotes(),
			'groundFloor' => $this->getGroundFloor(),
			'toilet' => $this->getToilet(),
			'unringable' => $this->getUnringable(),
			'simulator' => $this->getSimulator(),
			'overhaulYear' => $this->getOverhaulYear(),
			'contractor' => $this->getContractor(),
			'tuned' => $this->getTuned(),
			'extraInfo' => $this->getExtraInfo(),
			'webPage' => $this->getWebPage(),
//			'affiliations' => array_map( function( $a ) { return $a->toArray(); }, $this->getAffiliations()->toArray() ),
//			'firstPealedMethods' => array_map( function( $m ) { return $m->toArray(); }, $this->getFirstPealedMethods()->toArray() )
		), function( $x ) { return !empty( $x ); } );
	}
	
	/**
	 * Generate a string for safe use in HTML id
	 */
	public function getID() {
		return  preg_replace( '/\s*/', '', preg_replace( '/[^a-z0-9]/', '', strtolower( $this->getDoveid() ) ) );
	}

	/**
	 * @var string $doveid
	 *
	 * @ORM\Column(name="doveid", type="string", length=10, nullable=false)
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $doveid;
	public function getDoveid() { return $this->doveid; }

	/**
	 * @var string $gridReference
	 *
	 * @ORM\Column(name="gridReference", type="string", length=10, nullable=true)
	 */
	private $gridReference;

	public function setGridReference( $gridReference ) { $this->gridReference = $gridReference; }
	public function getGridReference() { return $this->gridReference; }

	/**
	 * @var decimal $latitude
	 *
	 * @ORM\Column(name="latitude", type="decimal", nullable=true)
	 */
	private $latitude;
	public function setLatitude( $latitude ) { $this->latitude = $latitude; }
	public function getLatitude() { return $this->latitude; }

	/**
	 * @var decimal $longitude
	 *
	 * @ORM\Column(name="longitude", type="decimal", nullable=true)
	 */
	private $longitude;
	public function setLongitude( $longitude ) { $this->longitude = $longitude; }
	public function getLongitude() { return $this->longitude; }

	/**
	 * @var decimal $latitudeSatNav
	 *
	 * @ORM\Column(name="latitudeSatNav", type="decimal", nullable=true)
	 */
	private $latitudeSatNav;
	public function setLatitudeSatNav( $latitudeSatNav ) { $this->latitudeSatNav = $latitudeSatNav; }
	public function getLatitudeSatNav() { return $this->latitudeSatNav; }

	/**
	 * @var decimal $longitudeSatNav
	 *
	 * @ORM\Column(name="longitudeSatNav", type="decimal", nullable=true)
	 */
	private $longitudeSatNav;
	public function setLongitudeSatNav( $longitudeSatNav ) { $this->longitudeSatNav = $longitudeSatNav; }
	public function getLongitudeSatNav() { return $this->longitudeSatNav; }

	/**
	 * @var string $postcode
	 *
	 * @ORM\Column(name="postcode", type="string", length=10, nullable=true)
	 */
	private $postcode;
	public function setPostcode( $postcode ) { $this->postcode = $postcode; }
	public function getPostcode() { return $this->postcode; }

	/**
	 * @var string $country
	 *
	 * @ORM\Column(name="country", type="string", length=255, nullable=false)
	 */
	private $country;
	public function setCountry( $country ) { $this->country = $country; }
	public function getCountry() { return $this->country; }

	/**
	 * @var string $county
	 *
	 * @ORM\Column(name="county", type="string", length=255, nullable=true)
	 */
	private $county;
	public function setCounty( $county ) { $this->county = $county; }
	public function getCounty() { return $this->county; }

	/**
	 * @var string $diocese
	 *
	 * @ORM\Column(name="diocese", type="string", length=255, nullable=true)
	 */
	private $diocese;
	public function setDiocese( $diocese ) { $this->diocese = $diocese; }
	public function getDiocese() { return $this->diocese; }

	/**
	 * @var string $place
	 *
	 * @ORM\Column(name="place", type="string", length=255, nullable=false)
	 */
	private $place;
	public function setPlace( $place ) { $this->place = $place; }
	public function getPlace() { return $this->place; }

	/**
	 * @var string $altName
	 *
	 * @ORM\Column(name="altName", type="string", length=255, nullable=true)
	 */
	private $altName;
	public function setAltName( $altName ) { $this->altName = $altName; }
	public function getAltName() { return $this->altName; }

	/**
	 * @var string $dedication
	 *
	 * @ORM\Column(name="dedication", type="string", length=255, nullable=true)
	 */
	private $dedication;
	public function setDedication( $dedication ) { $this->dedication = $dedication; }
	public function getDedication() { return $this->dedication; }

	/**
	 * @var smallint $bells
	 *
	 * @ORM\Column(name="bells", type="smallint", nullable=false)
	 */
	private $bells;
	public function setBells( $bells ) { $this->bells = $bells; }
	public function getBells() { return $this->bells; }

	/**
	 * @var smallint $weight
	 *
	 * @ORM\Column(name="weight", type="smallint", nullable=true)
	 */
	private $weight;
	public function setWeight( $weight ) { $this->weight = $weight; }
	public function getWeight() { return $this->weight; }

	/**
	 * @var boolean $weightApprox
	 *
	 * @ORM\Column(name="weightApprox", type="boolean", nullable=true)
	 */
	private $weightApprox;
	public function setWeightApprox( $weightApprox ) { $this->weightApprox = $weightApprox; }
	public function getWeightApprox() { return $this->weightApprox; }

	/**
	 * @var string $weightText
	 *
	 * @ORM\Column(name="weightText", type="string", length=20, nullable=true)
	 */
	private $weightText;
	public function setWeightText($weightText) { $this->weighttext = $weightText; }
	public function getWeightText() { return $this->weightText; }

	/**
	 * @var string $note
	 *
	 * @ORM\Column(name="note", type="string", length=2, nullable=true)
	 */
	private $note;
	public function setNote( $note ) { $this->note = $note; }
	public function getNote() { return $this->note; }

	/**
	 * @var decimal $hz
	 *
	 * @ORM\Column(name="hz", type="decimal", nullable=true)
	 */
	private $hz;
	public function setHz( $hz ) { $this->hz = $hz; }
	public function getHz() { return $this->hz; }

	/**
	 * @var smallint $practiceNight
	 *
	 * @ORM\Column(name="practiceNight", type="smallint", nullable=true)
	 */
	private $practiceNight;
	public function setPracticeNight( $practiceNight ) { $this->practiceNight = $practiceNight; }
	public function getPracticeNight() { return $this->practiceNight; }

	/**
	 * @var string $practiceStart
	 *
	 * @ORM\Column(name="practiceStart", type="string", length=5, nullable=true)
	 */
	private $practiceStart;
	public function setPracticeStart( $practiceStart ) { $this->practiceStart = $practiceStart; }
	public function getPracticeStart() { return $this->practiceStart; }

	/**
	 * @var text $practiceNotes
	 *
	 * @ORM\Column(name="practiceNotes", type="text", nullable=true)
	 */
	private $practiceNotes;
	public function setPracticeNotes( $practiceNotes ) { $this->practiceNotes = $practiceNotes; }
	public function getPracticeNotes() { return $this->practiceNotes; }

	/**
	 * @var boolean $groundFloor
	 *
	 * @ORM\Column(name="groundFloor", type="boolean", nullable=true)
	 */
	private $groundFloor;
	public function setGroundFloor( $groundFloor ) { $this->groundFloor = $groundFloor; }
	public function getGroundFloor() { return $this->groundFloor; }

	/**
	 * @var boolean $toilet
	 *
	 * @ORM\Column(name="toilet", type="boolean", nullable=true)
	 */
	private $toilet;
	public function setToilet( $toilet ) { $this->toilet = $toilet; }
	public function getToilet() { return $this->toilet; }

	/**
	 * @var boolean $unringable
	 *
	 * @ORM\Column(name="unringable", type="boolean", nullable=true)
	 */
	private $unringable;
	public function setUnringable( $unringable ) { $this->unringable = $unringable; }
	public function getUnringable() { return $this->unringable; }

	/**
	 * @var boolean $simulator
	 *
	 * @ORM\Column(name="simulator", type="boolean", nullable=true)
	 */
	private $simulator;
	public function setSimulator( $simulator ) { $this->simulator = $simulator; }
	public function getSimulator() { return $this->simulator; }

	/**
	 * @var smallint $overhaulYear
	 *
	 * @ORM\Column(name="overhaulYear", type="smallint", nullable=true)
	 */
	private $overhaulYear;
	public function setOverhaulyear( $overhaulYear ) { $this->overhaulYear = $overhaulYear; }
	public function getOverhaulYear() { return $this->overhaulYear; }

	/**
	 * @var string $contractor
	 *
	 * @ORM\Column(name="contractor", type="string", length=255, nullable=true)
	 */
	private $contractor;
	public function setContractor( $contractor ) { $this->contractor = $contractor; }
	public function getContractor() { return $this->contractor; }

	/**
	 * @var smallint $tuned
	 *
	 * @ORM\Column(name="tuned", type="smallint", nullable=true)
	 */
	private $tuned;
	public function setTuned( $tuned ) { $this->tuned = $tuned; }
	public function getTuned() { return $this->tuned; }

	/**
	 * @var text $extraInfo
	 *
	 * @ORM\Column(name="extraInfo", type="text", nullable=true)
	 */
	private $extraInfo;
	public function setExtrainfo( $extraInfo ) { $this->extraInfo = $extraInfo; }
	public function getExtraInfo() { return $this->extraInfo; }

	/**
	 * @var text $webPage
	 *
	 * @ORM\Column(name="webPage", type="text", nullable=true)
	 */
	private $webPage;
	public function setWebpage( $webPage ) { $this->webPage = $webPage; }
	public function getWebPage() { return $this->webPage; }

	/**
	 * @var Associations
	 *
	 * @ORM\ManyToMany(targetEntity="Associations", mappedBy="affiliatedTowers")
	 */
	private $affiliations;
	public function addAffiliation( \Blueline\CCCBRDataBundle\Entity\Associations $association ) { $this->affiliations[] = $association; }
	public function getAffiliations() { return $this->affiliations; }

	/**
	 * @var Methods
	 *
	 * @ORM\ManyToMany(targetEntity="Methods", mappedBy="firstTowerbellPealTower")
	 */
	private $firstPealedMethods;
	public function addFirstPealedMethod( \Blueline\CCCBRDataBundle\Entity\Methods $method ) { $this->firstPealedMethods[] = $method; }
	public function getFirstPealedMethods() { return $this->firstPealedMethods; }
	
	/**
	 * @ORM\OneToOne(targetEntity="TowerOldpks")
	 * @ORM\JoinColumn(name="doveid", referencedColumnName="tower_doveid")
	 */
	private $oldpk;
}
