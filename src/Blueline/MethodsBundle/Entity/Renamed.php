<?php

namespace Blueline\MethodsBundle\Entity;

/**
 * Blueline\MethodsBundle\Entity\Renamed
 */
class Renamed
{
    /**
     * @var string $old_title
     */
    private $old_title;

    /**
     * @var Blueline\MethodsBundle\Entity\Method
     */
    private $tower;

    /**
     * Set old_title
     *
     * @param  string  $oldTitle
     * @return Renamed
     */
    public function setOldTitle($oldTitle)
    {
        $this->old_title = $oldTitle;

        return $this;
    }

    /**
     * Get old_title
     *
     * @return string
     */
    public function getOldTitle()
    {
        return $this->old_title;
    }

    /**
     * @var Blueline\MethodsBundle\Entity\Method
     */
    private $method;

    /**
     * Set method
     *
     * @param  Blueline\MethodsBundle\Entity\Method $method
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
     * @return Blueline\MethodsBundle\Entity\Method
     */
    public function getMethod()
    {
        return $this->method;
    }
}
