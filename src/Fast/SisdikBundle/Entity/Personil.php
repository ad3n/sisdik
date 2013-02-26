<?php

namespace Fast\SisdikBundle\Entity;
/**
 * 
 * @author Ihsan Faisal
 *
 */
class Personil
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $user;

    /**
     * Get id
     * 
     * @param int $id
     * @return \Fast\SisdikBundle\Entity\Personil
     */
    public function setId($id) {
        $this->id = $id;

        return $this;
    }

    /**
     * Set id
     * 
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set user
     *
     * @param string $user
     * @return Personil
     */
    public function setUser($user) {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return string
     */
    public function getUser() {
        return $this->user;
    }
}
