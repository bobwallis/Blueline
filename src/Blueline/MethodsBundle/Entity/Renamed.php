<?php

namespace Blueline\MethodsBundle\Entity;

/**
 * Renamed
 */
class Renamed
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var string
     */
    private $location;

    /**
     * @var string
     */
    private $rwRef;

    /**
     * @var \Blueline\MethodsBundle\Entity\Method
     */
    private $method;

    /**
     * Set id
     *
     * @param  string  $id
     * @return Renamed
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set title
     *
     * @param  string  $title
     * @return Renamed
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set date
     *
     * @param  \DateTime $date
     * @return Renamed
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
     * Set location
     *
     * @param  string  $location
     * @return Renamed
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set rwRef
     *
     * @param  string  $rwRef
     * @return Renamed
     */
    public function setRwRef($rwRef)
    {
        $this->rwRef = $rwRef;

        return $this;
    }

    /**
     * Get rwRef
     *
     * @return string
     */
    public function getRwRef()
    {
        return $this->rwRef;
    }

    /**
     * Set method
     *
     * @param  \Blueline\MethodsBundle\Entity\Method $method
     * @return Renamed
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

    /**
     * Sets multiple variables using an array of them
     *
     * @param array $map
     */
    public function setAll($map)
    {
        foreach ($map as $key => $value) {
            $method = 'set'.str_replace( ' ', '', ucwords( str_replace( '_', ' ', $key ) ) );
            if ( is_callable( array( $this, $method ) ) ) {
                $this->$method( $value );
            }
        }
    }
}
