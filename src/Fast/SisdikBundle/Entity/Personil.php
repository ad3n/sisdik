<?php
namespace Langgas\SisdikBundle\Entity;

/**
 * Merepresentasikan identitas user anggota panitia pendaftaran.
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
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }
}
