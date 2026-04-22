<?php

namespace Blueline\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Collection entity.
 */
#[ORM\Entity(repositoryClass: \Blueline\Repository\CollectionRepository::class)]
#[ORM\Table(name: 'collections')]
class Collection
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\OneToMany(targetEntity: MethodInCollection::class, mappedBy: 'collection', cascade: ['all'])]
    private \Doctrine\Common\Collections\Collection $methods;

    /**
     * Create a collection entity and optionally hydrate it from an associative array.
     *
     * @param array<string, mixed> $firstSet Initial property values keyed by setter-compatible names
     */
    public function __construct($firstSet = [])
    {
        $this->methods = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setAll($firstSet);
    }

    /**
     * Convert the entity to a short debug string.
     *
     * @return string
     */
    public function __toString()
    {
        return 'Collection:'.$this->getId();
    }

    /**
     * Convert the entity to an array for template/API serialisation.
     *
     * Relationship fields and internal identifiers are intentionally excluded.
     *
     * @return array<string, mixed>
     */
    public function __toArray()
    {
        $objectVars = get_object_vars($this);
        array_walk($objectVars, function (&$v, $k) {
            // Filter out id because that's only really meaningful internally, and don't try to drill down into sub-entities
            if ('id' == $k || 'methods' == $k) {
                $v = null;
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
     *
     * @return Collection
     */
    public function setAll($map)
    {
        foreach ($map as $key => $value) {
            $method = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (is_callable([$this, $method])) {
                $this->$method($value);
            }
        }

        return $this;
    }

    // Getters and setters
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function addMethod(MethodInCollection $method)
    {
        $this->methods[] = $method;

        return $this;
    }

    public function removeMethod(MethodInCollection $method)
    {
        $this->methods->removeElement($method);
    }

    public function getMethods()
    {
        return $this->methods;
    }
}
