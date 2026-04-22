<?php

namespace Blueline\Entity;

use Doctrine\ORM\Mapping as ORM;
use Blueline\Helpers\Text;

/**
 * Performance entity
 */
#[ORM\Entity(repositoryClass: \Blueline\Repository\PerformanceRepository::class)]
#[ORM\Table(name: 'performances')]
#[ORM\Index(name: 'idx_performances_type', columns: array('type'))]
#[ORM\Index(name: 'idx_performances_method_title', columns: array('method_title'))]
class Performance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $type;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTime $date = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $society = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $rung_title = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $rung_url = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $location_room = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $location_building = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $location_address = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $location_town = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $location_county = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $location_region = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $location_country = null;

    #[ORM\ManyToOne(targetEntity: Method::class, inversedBy: 'performances', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'method_title', referencedColumnName: 'title')]
    private Method $method;

    /**
     * Create a performance entity and optionally hydrate it from an associative array.
     *
     * @param array<string, mixed> $firstSet Initial property values keyed by setter-compatible names
     */
    public function __construct($firstSet = array())
    {
        $this->setAll($firstSet);
    }

    /**
     * Convert the entity to a short debug string.
     *
     * @return string
     */
    public function __toString()
    {
        return 'Performance:'.$this->getId();
    }

    /**
     * Convert the entity to an array for template/API serialisation.
     *
     * Relationship and internal id fields are excluded.
     *
     * @return array<string, mixed>
     */
    public function __toArray()
    {
        $objectVars = get_object_vars($this);
        array_walk($objectVars, function (&$v, $k) {
            switch ($k) {
                // Filter out id because that's only really meaningful internally, and don't try to drill down into sub-entities
                case 'id':
                case 'method':
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

    /**
     * Bulk-set properties from an associative array.
     *
     * Keys are mapped to setter names using snake_case to StudlyCase conversion.
     * Unknown keys are ignored.
     *
     * @param array<string, mixed> $map
     * @return Performance
     */
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

    // Getters and setters
    public function getId()
    {
        return $this->id;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setSociety($society)
    {
        $this->society = $society;
        return $this;
    }

    public function getSociety()
    {
        return $this->society;
    }

    public function setRungTitle($rungTitle)
    {
        $this->rung_title = $rungTitle;
        return $this;
    }

    public function getRungTitle()
    {
        return $this->rung_title;
    }

    public function setRungUrl($rungUrl)
    {
        $this->rung_url = $rungUrl;
        return $this;
    }

    public function getRungUrl()
    {
        return $this->rung_url;
    }

    public function setReference($reference)
    {
        $this->reference = $reference;
        return $this;
    }

    public function getReference()
    {
        return $this->reference;
    }

    public function setLocationRoom($locationRoom)
    {
        $this->location_room = $locationRoom;
        return $this;
    }

    public function getLocationRoom()
    {
        return $this->location_room;
    }

    public function setLocationBuilding($locationBuilding)
    {
        $this->location_building = $locationBuilding;
        return $this;
    }

    public function getLocationBuilding()
    {
        return $this->location_building;
    }

    public function setLocationAddress($locationAddress)
    {
        $this->location_address = $locationAddress;
        return $this;
    }

    public function getLocationAddress()
    {
        return $this->location_address;
    }

    public function setLocationTown($locationTown)
    {
        $this->location_town = $locationTown;
        return $this;
    }

    public function getLocationTown()
    {
        return $this->location_town;
    }

    public function setLocationCounty($locationCounty)
    {
        $this->location_county = $locationCounty;
        return $this;
    }

    public function getLocationCounty()
    {
        return $this->location_county;
    }

    public function setLocationRegion($locationRegion)
    {
        $this->location_region = $locationRegion;
        return $this;
    }

    public function getLocationRegion()
    {
        return $this->location_region;
    }

    public function setLocationCountry($locationCountry)
    {
        $this->location_country = $locationCountry;
        return $this;
    }

    public function getLocationCountry()
    {
        return $this->location_country;
    }

    public function setMethod(?\Blueline\Entity\Method $method)
    {
        $this->method = $method;
        return $this;
    }

    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get location by concatenating all location fields with a separator, skipping any that are empty.
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
}
