<?php

namespace Langgas\SisdikBundle\Entity;

/**
 * Merepresentasikan siswa
 */
class PersonilSiswa
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $namaSiswa;

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
     * @param string $namaSiswa
     */
    public function setNamaSiswa($namaSiswa)
    {
        $this->namaSiswa = $namaSiswa;
    }

    /**
     * @return string
     */
    public function getNamaSiswa()
    {
        return $this->namaSiswa;
    }
}
