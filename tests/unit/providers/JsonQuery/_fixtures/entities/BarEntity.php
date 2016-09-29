<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * Class FooEntity
 *
 * @ORM\Entity
 * @ORM\Table(name="bar")
 */
class BarEntity
{
    /**
     * @var int $id
     *
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
