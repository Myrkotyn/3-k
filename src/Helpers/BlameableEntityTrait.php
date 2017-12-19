<?php

namespace App\Helpers;

use App\Entity\User;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * BlameableEntityTrait
 */
trait BlameableEntityTrait
{
    /**
     * @var User $createdBy User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Gedmo\Versioned
     */
    private $createdBy;

    /**
     * @var User $updatedBy User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Gedmo\Versioned
     */
    private $updatedBy;

    /**
     * Get created by
     *
     * @return User
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set created by
     *
     * @param User $createdBy User
     *
     * @return $this
     */
    public function setCreatedBy(User $createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get updated by
     *
     * @return User
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * Set updated by
     *
     * @param User $updatedBy User
     *
     * @return $this
     */
    public function setUpdatedBy(User $updatedBy)
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }
}