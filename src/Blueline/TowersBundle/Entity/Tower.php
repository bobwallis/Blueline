<?php
namespace Blueline\TowersBundle\Entity;

class Tower
{
    // Constructor
    public function __construct($firstSet = array())
    {
        $this->oldpks       = new \Doctrine\Common\Collections\ArrayCollection();
        $this->associations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->performances = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setAll( $firstSet );
    }

    // Casting helpers
    public function __toString() {
        return $this->getPlace();
    }

    public function __toArray()
    {
        $objectVars = get_object_vars($this);
        array_walk( $objectVars, function( &$v, $k ) {
            // Filter oldpks for now.
            if( $k == 'oldpks' || $k == 'associations' || $k == 'performances' ) {
                $v = null;
            }
        } );
        return array_filter( $objectVars );
    }

    // setAll helper
    public function setAll($map)
    {
        foreach ($map as $key => $value) {
            $method = 'set'.ucwords( $key );
            if ( is_callable( array( $this, $method ) ) ) {
                $this->$method( $value );
            }
        }
    }

    // Variables
    /**
     * @var string $id
     */
    private $id;

    /**
     * @var string $gridReference
     */
    private $gridReference;

    /**
     * @var decimal $latitude
     */
    private $latitude;

    /**
     * @var decimal $longitude
     */
    private $longitude;

    /**
     * @var decimal $latitudeSatNav
     */
    private $latitudeSatNav;

    /**
     * @var decimal $longitudeSatNav
     */
    private $longitudeSatNav;

    /**
     * @var string $postcode
     */
    private $postcode;

    /**
     * @var string $country
     */
    private $country;

    /**
     * @var string $county
     */
    private $county;

    /**
     * @var string $diocese
     */
    private $diocese;

    /**
     * @var string $place
     */
    private $place;

    /**
     * @var string $altName
     */
    private $altName;

    /**
     * @var string $dedication
     */
    private $dedication;

    /**
     * @var smallint $bells
     */
    private $bells;

    /**
     * @var smallint $weight
     */
    private $weight;

    /**
     * @var boolean $weightApprox
     */
    private $weightApprox;

    /**
     * @var string $note
     */
    private $note;

    /**
     * @var decimal $hz
     */
    private $hz;

    /**
     * @var smallint $practiceNight
     */
    private $practiceNight;

    /**
     * @var string $practiceStart
     */
    private $practiceStart;

    /**
     * @var text $practiceNotes
     */
    private $practiceNotes;

    /**
     * @var boolean $groundFloor
     */
    private $groundFloor;

    /**
     * @var boolean $toilet
     */
    private $toilet;

    /**
     * @var boolean $unringable
     */
    private $unringable;

    /**
     * @var boolean $simulator
     */
    private $simulator;

    /**
     * @var smallint $overhaulYear
     */
    private $overhaulYear;

    /**
     * @var string $contractor
     */
    private $contractor;

    /**
     * @var smallint $tunedYear
     */
    private $tunedYear;

    /**
     * @var text $extraInfo
     */
    private $extraInfo;

    /**
     * @var text $webPage
     */
    private $webPage;

    /**
     * @var Blueline\TowersBundle\Entity\OldPK
     */
    private $oldpks;

    /**
     * @var Doctrine\Common\Collections\Collection
     */
    private $associations;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $performances;

    // Getters and setters
    /**
     * Set id
     *
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set gridReference
     *
     * @param string $gridReference
     */
    public function setGridReference($gridReference)
    {
        $this->gridReference = $gridReference;
    }

    /**
     * Get gridReference
     *
     * @return string
     */
    public function getGridReference()
    {
        return $this->gridReference;
    }

    /**
     * Set latitude
     *
     * @param decimal $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * Get latitude
     *
     * @return decimal
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param decimal $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    /**
     * Get longitude
     *
     * @return decimal
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set latitudeSatNav
     *
     * @param decimal $latitudeSatNav
     */
    public function setLatitudeSatNav($latitudeSatNav)
    {
        $this->latitudeSatNav = $latitudeSatNav;
    }

    /**
     * Get latitudeSatNav
     *
     * @return decimal
     */
    public function getLatitudeSatNav()
    {
        return $this->latitudeSatNav;
    }

    /**
     * Set longitudeSatNav
     *
     * @param decimal $longitudeSatNav
     */
    public function setLongitudeSatNav($longitudeSatNav)
    {
        $this->longitudeSatNav = $longitudeSatNav;
    }

    /**
     * Get longitudeSatNav
     *
     * @return decimal
     */
    public function getLongitudeSatNav()
    {
        return $this->longitudeSatNav;
    }

    /**
     * Set postcode
     *
     * @param string $postcode
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;
    }

    /**
     * Get postcode
     *
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * Set country
     *
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set county
     *
     * @param string $county
     */
    public function setCounty($county)
    {
        $this->county = $county;
    }

    /**
     * Get county
     *
     * @return string
     */
    public function getCounty()
    {
        return $this->county;
    }

    /**
     * Set diocese
     *
     * @param string $diocese
     */
    public function setDiocese($diocese)
    {
        $this->diocese = $diocese;
    }

    /**
     * Get diocese
     *
     * @return string
     */
    public function getDiocese()
    {
        return $this->diocese;
    }

    /**
     * Set place
     *
     * @param string $place
     */
    public function setPlace($place)
    {
        $this->place = $place;
    }

    /**
     * Get place
     *
     * @return string
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * Set altName
     *
     * @param string $altName
     */
    public function setAltName($altName)
    {
        $this->altName = $altName;
    }

    /**
     * Get altName
     *
     * @return string
     */
    public function getAltName()
    {
        return $this->altName;
    }

    /**
     * Set dedication
     *
     * @param string $dedication
     */
    public function setDedication($dedication)
    {
        $this->dedication = $dedication;
    }

    /**
     * Get dedication
     *
     * @return string
     */
    public function getDedication()
    {
        return $this->dedication;
    }

    /**
     * Set bells
     *
     * @param smallint $bells
     */
    public function setBells($bells)
    {
        $this->bells = $bells;
    }

    /**
     * Get bells
     *
     * @return smallint
     */
    public function getBells()
    {
        return $this->bells;
    }

    /**
     * Set weight
     *
     * @param smallint $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * Get weight
     *
     * @return smallint
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Get weight text
     *
     * @return string
     */
    public function getWeightText()
    {
        $weight       = $this->getWeight();
        $weightApprox = $this->getWeightApprox();
        $tmp          = $weight % 112;
        $cwt          = ($weight - $tmp) / 112;
        $tmp2         = $tmp;
        $tmp          = $tmp2 % 28;
        $qtr          = ($tmp2 - $tmp) / 28;
        if ($weightApprox == true && $tmp == 0 && $qtr == 0) {
            return $cwt.'cwt';
        } else {
            return $cwt.'-'.$qtr.'-'.$tmp;
        }
    }

    /**
     * Set weightApprox
     *
     * @param boolean $weightApprox
     */
    public function setWeightApprox($weightApprox)
    {
        $this->weightApprox = $weightApprox;
    }

    /**
     * Get weightApprox
     *
     * @return boolean
     */
    public function getWeightApprox()
    {
        return $this->weightApprox;
    }

    /**
     * Set note
     *
     * @param string $note
     */
    public function setNote($note)
    {
        $this->note = $note;
    }

    /**
     * Get note
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set hz
     *
     * @param decimal $hz
     */
    public function setHz($hz)
    {
        $this->hz = $hz;
    }

    /**
     * Get hz
     *
     * @return decimal
     */
    public function getHz()
    {
        return $this->hz;
    }

    /**
     * Set practiceNight
     *
     * @param smallint $practiceNight
     */
    public function setPracticeNight($practiceNight)
    {
        $this->practiceNight = $practiceNight;
    }

    /**
     * Get practiceNight
     *
     * @return smallint
     */
    public function getPracticeNight()
    {
        return $this->practiceNight;
    }

    /**
     * Set practiceStart
     *
     * @param string $practiceStart
     */
    public function setPracticeStart($practiceStart)
    {
        $this->practiceStart = $practiceStart;
    }

    /**
     * Get practiceStart
     *
     * @return string
     */
    public function getPracticeStart()
    {
        return $this->practiceStart;
    }

    /**
     * Set practiceNotes
     *
     * @param text $practiceNotes
     */
    public function setPracticeNotes($practiceNotes)
    {
        $this->practiceNotes = $practiceNotes;
    }

    /**
     * Get practiceNotes
     *
     * @return text
     */
    public function getPracticeNotes()
    {
        return $this->practiceNotes;
    }

    /**
     * Set groundFloor
     *
     * @param boolean $groundFloor
     */
    public function setGroundFloor($groundFloor)
    {
        $this->groundFloor = $groundFloor;
    }

    /**
     * Get groundFloor
     *
     * @return boolean
     */
    public function getGroundFloor()
    {
        return $this->groundFloor;
    }

    /**
     * Set toilet
     *
     * @param boolean $toilet
     */
    public function setToilet($toilet)
    {
        $this->toilet = $toilet;
    }

    /**
     * Get toilet
     *
     * @return boolean
     */
    public function getToilet()
    {
        return $this->toilet;
    }

    /**
     * Set unringable
     *
     * @param boolean $unringable
     */
    public function setUnringable($unringable)
    {
        $this->unringable = $unringable;
    }

    /**
     * Get unringable
     *
     * @return boolean
     */
    public function getUnringable()
    {
        return $this->unringable;
    }

    /**
     * Set simulator
     *
     * @param boolean $simulator
     */
    public function setSimulator($simulator)
    {
        $this->simulator = $simulator;
    }

    /**
     * Get simulator
     *
     * @return boolean
     */
    public function getSimulator()
    {
        return $this->simulator;
    }

    /**
     * Set overhaulYear
     *
     * @param smallint $overhaulYear
     */
    public function setOverhaulYear($overhaulYear)
    {
        $this->overhaulYear = $overhaulYear;
    }

    /**
     * Get overhaulYear
     *
     * @return smallint
     */
    public function getOverhaulYear()
    {
        return $this->overhaulYear;
    }

    /**
     * Set contractor
     *
     * @param string $contractor
     */
    public function setContractor($contractor)
    {
        $this->contractor = $contractor;
    }

    /**
     * Get contractor
     *
     * @return string
     */
    public function getContractor()
    {
        return $this->contractor;
    }

    /**
     * Set tunedYear
     *
     * @param smallint $tunedYear
     */
    public function setTunedYear($tunedYear)
    {
        $this->tunedYear = $tunedYear;
    }

    /**
     * Get tunedYear
     *
     * @return smallint
     */
    public function getTunedYear()
    {
        return $this->tunedYear;
    }

    /**
     * Set extraInfo
     *
     * @param text $extraInfo
     */
    public function setExtraInfo($extraInfo)
    {
        $this->extraInfo = $extraInfo;
    }

    /**
     * Get extraInfo
     *
     * @return text
     */
    public function getExtraInfo()
    {
        return $this->extraInfo;
    }

    /**
     * Set webPage
     *
     * @param text $webPage
     */
    public function setWebPage($webPage)
    {
        $this->webPage = $webPage;
    }

    /**
     * Get webPage
     *
     * @return text
     */
    public function getWebPage()
    {
        return $this->webPage;
    }

    /**
     * Get dove link
     *
     * @return text
     */
    public function getDoveLink()
    {
        return 'http://dove.cccbr.org.uk/detail.php?DoveID='. str_replace( '_', '+', $this->getId() ) . '&showFrames=true';
    }


    /**
     * Add associations
     *
     * @param Blueline\AssociationsBundle\Entity\Association $associations
     */
    public function addAssociation(\Blueline\AssociationsBundle\Entity\Association $associations)
    {
        $this->associations[] = $associations;
    }

    /**
     * Removes associations
     *
     * @param Blueline\AssociationsBundle\Entity\Association $associations
     */
    public function removeAssociation(\Blueline\AssociationsBundle\Entity\Association $associations)
    {
        return $this->associations->removeElement( $associations );
    }

    /**
     * Get associations
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getAssociations()
    {
        return $this->associations;
    }
    /**
     * @var text $affiliations
     */
    private $affiliations;

    /**
     * Set affiliations
     *
     * @param text $affiliations
     */
    public function setAffiliations($affiliations)
    {
        $this->affiliations = $affiliations;
    }

    /**
     * Get affiliations
     *
     * @return text
     */
    public function getAffiliations()
    {
        return $this->affiliations;
    }

    /**
     * Add oldpks
     *
     * @param  Blueline\TowersBundle\Entity\OldPK $oldpks
     * @return Tower
     */
    public function addOldpk(\Blueline\TowersBundle\Entity\OldPK $oldpks)
    {
        $this->oldpks[] = $oldpks;

        return $this;
    }

    /**
     * Remove oldpks
     *
     * @param Blueline\TowersBundle\Entity\OldPK $oldpks
     */
    public function removeOldpk(\Blueline\TowersBundle\Entity\OldPK $oldpks)
    {
        $this->oldpks->removeElement($oldpks);
    }

    /**
     * Get oldpks
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOldpks()
    {
        return $this->oldpks;
    }

    /**
     * Add performances
     *
     * @param  \Blueline\MethodsBundle\Entity\Performance $performances
     * @return Tower
     */
    public function addPerformance(\Blueline\MethodsBundle\Entity\Performance $performances)
    {
        $this->performances[] = $performances;

        return $this;
    }

    /**
     * Remove performances
     *
     * @param \Blueline\MethodsBundle\Entity\Performance $performances
     */
    public function removePerformance(\Blueline\MethodsBundle\Entity\Performance $performances)
    {
        $this->performances->removeElement($performances);
    }

    /**
     * Get performances
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPerformances()
    {
        return $this->performances;
    }

    /**
     * Get performances which were first peals
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFirstPealPerformances()
    {
        return $this->getPerformances()->filter( function($p) { return $p->getType() == 'firstTowerbellPeal'; } );
    }
}
