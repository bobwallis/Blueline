<?php

namespace Blueline\MethodsBundle\Entity;

use Blueline\BluelineBundle\Helpers\Text;

/**
 * Performance
 */
class Performance
{
    // Constructor
    public function __construct($firstSet = array())
    {
        $this->setAll($firstSet);
    }

    // Casting helpers
    public function __toString()
    {
        return 'Performance:'.$this->getId();
    }

    public function __toArray()
    {
        $objectVars = get_object_vars($this);
        array_walk($objectVars, function (&$v, $k) {
            switch ($k) {
                // Filter out id because that's only really meaningful internally, and don't try to drill down into sub-entities
                case 'id':
                case 'method':
                case 'location_tower':
                    $v = null;
                    break;
                // Convert date object
                case 'date':
                    $v = $v->format('Y-m-d');
                    break;
            }
        });

        return array_filter($objectVars);
    }

    // setAll helper
    public function setAll($map)
    {
        foreach ($map as $key => $value) {
            $method = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (is_callable(array( $this, $method ))) {
                $this->$method($value);
            }
        }

        return $this;
    }

    // Variables
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var string
     */
    private $society;

    /**
     * @var string
     */
    private $rung_title;

    /**
     * @var string
     */
    private $rung_url;

    /**
     * @var string
     */
    private $reference;

    /**
     * @var string
     */
    private $location_room;

    /**
     * @var string
     */
    private $location_building;

    /**
     * @var string
     */
    private $location_address;

    /**
     * @var string
     */
    private $location_town;

    /**
     * @var string
     */
    private $location_county;

    /**
     * @var string
     */
    private $location_region;

    /**
     * @var string
     */
    private $location_country;

    /**
     * @var \Blueline\TowersBundle\Entity\Tower
     */
    private $location_tower;

    /**
     * @var \Blueline\MethodsBundle\Entity\Method
     */
    private $method;

    // Getters and setters
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set type
     *
     * @param  string      $type
     * @return Performance
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set date
     *
     * @param  \DateTime   $date
     * @return Performance
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set society
     *
     * @param  string      $society
     * @return Performance
     */
    public function setSociety($society)
    {
        $this->society = $society;

        return $this;
    }

    /**
     * Get society
     *
     * @return string
     */
    public function getSociety()
    {
        return $this->society;
    }

    /**
     * Set rung_title
     *
     * @param  string      $rungTitle
     * @return Performance
     */
    public function setRungTitle($rungTitle)
    {
        $this->rung_title = $rungTitle;

        return $this;
    }

    /**
     * Get rung_title
     *
     * @return string
     */
    public function getRungTitle()
    {
        return $this->rung_title;
    }

    /**
     * Set rung_url
     *
     * @param  string      $rungUrl
     * @return Performance
     */
    public function setRungUrl($rungUrl)
    {
        $this->rung_url = $rungUrl;

        return $this;
    }

    /**
     * Get rung_url
     *
     * @return string
     */
    public function getRungUrl()
    {
        return $this->rung_url;
    }

    /**
     * Set reference
     *
     * @param  string      $reference
     * @return Performance
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Get reference
     *
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Set location_room
     *
     * @param  string      $locationRoom
     * @return Performance
     */
    public function setLocationRoom($locationRoom)
    {
        $this->location_room = $locationRoom;

        return $this;
    }

    /**
     * Get location_room
     *
     * @return string
     */
    public function getLocationRoom()
    {
        return $this->location_room;
    }

    /**
     * Set location_building
     *
     * @param  string      $locationBuilding
     * @return Performance
     */
    public function setLocationBuilding($locationBuilding)
    {
        $this->location_building = $locationBuilding;

        return $this;
    }

    /**
     * Get location_building
     *
     * @return string
     */
    public function getLocationBuilding()
    {
        return $this->location_building;
    }

    /**
     * Set location_address
     *
     * @param  string      $locationAddress
     * @return Performance
     */
    public function setLocationAddress($locationAddress)
    {
        $this->location_address = $locationAddress;

        return $this;
    }

    /**
     * Get location_address
     *
     * @return string
     */
    public function getLocationAddress()
    {
        return $this->location_address;
    }

    /**
     * Set location_town
     *
     * @param  string      $locationTown
     * @return Performance
     */
    public function setLocationTown($locationTown)
    {
        $this->location_town = $locationTown;

        return $this;
    }

    /**
     * Get location_town
     *
     * @return string
     */
    public function getLocationTown()
    {
        return $this->location_town;
    }

    /**
     * Set location_county
     *
     * @param  string      $locationCounty
     * @return Performance
     */
    public function setLocationCounty($locationCounty)
    {
        $this->location_county = $locationCounty;

        return $this;
    }

    /**
     * Get location_county
     *
     * @return string
     */
    public function getLocationCounty()
    {
        return $this->location_county;
    }

    /**
     * Set location_region
     *
     * @param  string      $locationRegion
     * @return Performance
     */
    public function setLocationRegion($locationRegion)
    {
        $this->location_region = $locationRegion;

        return $this;
    }

    /**
     * Get location_region
     *
     * @return string
     */
    public function getLocationRegion()
    {
        return $this->location_region;
    }

    /**
     * Set location_country
     *
     * @param  string      $locationCountry
     * @return Performance
     */
    public function setLocationCountry($locationCountry)
    {
        $this->location_country = $locationCountry;

        return $this;
    }

    /**
     * Get location_country
     *
     * @return string
     */
    public function getLocationCountry()
    {
        return $this->location_country;
    }

    /**
     * Get location
     *
     * @return string
     */
    public function getLocation()
    {
        return Text::toList(array_filter(array(
            $this->getLocationRoom(),
            $this->getLocationBuilding(),
            $this->getLocationAddress(),
            $this->getLocationTown(),
            $this->getLocationCounty(),
            $this->getLocationRegion(),
            $this->getLocationCountry(),
        )), ', ', ', ');
    }

    /**
     * Set location_tower
     *
     * @param  \Blueline\TowersBundle\Entity\Tower $locationTower
     * @return Performance
     */
    public function setLocationTower(\Blueline\TowersBundle\Entity\Tower $locationTower = null)
    {
        $this->location_tower = $locationTower;

        return $this;
    }

    /**
     * Get location_tower
     *
     * @return \Blueline\TowersBundle\Entity\Tower
     */
    public function getLocationTower()
    {
        return $this->location_tower;
    }

    /**
     * Set method
     *
     * @param  \Blueline\MethodsBundle\Entity\Method $method
     * @return Performance
     */
    public function setMethod(\Blueline\MethodsBundle\Entity\Method $method = null)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Get method
     *
     * @return \Blueline\MethodsBundle\Entity\Method
     */
    public function getMethod()
    {
        return $this->method;
    }
}
