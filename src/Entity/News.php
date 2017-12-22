<?php

namespace App\Entity;

use App\Helpers\BlameableEntityTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\NewsRepository")
 * @ORM\Table(name="news")
 */
class News
{
    use BlameableEntityTrait, TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"default"})
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Groups({"default"})
     */
    private $title;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Groups({"default"})
     */
    private $description;

    /**
     * @return integer
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): ? string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return News
     */
    public function setTitle($title): ? News
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): ? string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return News
     */
    public function setDescription($description): ? News
    {
        $this->description = $description;

        return $this;
    }
}
