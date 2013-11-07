<?php

namespace Blueline\MethodsBundle\Entity;

/**
 * Blueline\MethodsBundle\Entity\Duplicate
 */
class Duplicate
{
    /**
     * @var string $duplicate_title
     */
    private $duplicate_title;

    /**
     * @var Blueline\MethodsBundle\Entity\Method
     */
    private $method;

    /**
     * Set duplicate_title
     *
     * @param  string    $duplicateTitle
     * @return Duplicate
     */
    public function setDuplicateTitle($duplicateTitle)
    {
        $this->duplicate_title = $duplicateTitle;

        return $this;
    }

    /**
     * Get duplicate_title
     *
     * @return string
     */
    public function getDuplicateTitle()
    {
        return $this->duplicate_title;
    }

    /**
     * Set method
     *
     * @param  Blueline\MethodsBundle\Entity\Method $method
     * @return Duplicate
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