<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Fast\SisdikBundle\Entity\Penjurusan
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="penjurusan")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
class Penjurusan
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string $nama
     *
     * @ORM\Column(name="nama", type="string", length=300, nullable=true)
     */
    private $nama;

    /**
     * @var string $kode
     *
     * @ORM\Column(name="kode", type="string", length=50, nullable=false)
     */
    private $kode;

    /**
     * @var string $kepala
     *
     * @ORM\Column(name="kepala", type="string", length=400, nullable=true)
     */
    private $kepala;

    /**
     * @var integer $lft
     *
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer", nullable=true)
     */
    private $lft;

    /**
     * @var integer $lvl
     * 
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer", nullable=true)
     */
    private $lvl;

    /**
     * @var integer $rgt
     *
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer", nullable=true)
     */
    private $rgt;

    /**
     * @var integer $root
     * 
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=true)
     */
    private $root;

    /**
     * @var Penjurusan
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Penjurusan", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $parent;

    /**
     * @var Sekolah
     *
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idsekolah", referencedColumnName="id")
     * })
     */
    private $idsekolah;

    /**
     * @ORM\OneToMany(targetEntity="Penjurusan", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set nama
     *
     * @param string $nama
     * @return Penjurusan
     */
    public function setNama($nama) {
        $this->nama = $nama;

        return $this;
    }

    /**
     * Get nama
     *
     * @return string 
     */
    public function getNama() {
        return $this->nama;
    }

    /**
     * Set kode
     *
     * @param string $kode
     * @return Penjurusan
     */
    public function setKode($kode) {
        $this->kode = $kode;

        return $this;
    }

    /**
     * Get kode
     *
     * @return string 
     */
    public function getKode() {
        return $this->kode;
    }

    /**
     * Set kepala
     *
     * @param string $kepala
     * @return Penjurusan
     */
    public function setKepala($kepala) {
        $this->kepala = $kepala;

        return $this;
    }

    /**
     * Get kepala
     *
     * @return string 
     */
    public function getKepala() {
        return $this->kepala;
    }

    /**
     * Set lft
     *
     * @param integer $lft
     * @return Penjurusan
     */
    public function setLft($lft) {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft
     *
     * @return integer 
     */
    public function getLft() {
        return $this->lft;
    }

    /**
     * Set lvl
     *
     * @param integer $lvl
     * @return Penjurusan
     */
    public function setLvl($lvl) {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * Get lvl
     *
     * @return integer 
     */
    public function getLvl() {
        return $this->lvl;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     * @return Penjurusan
     */
    public function setRgt($rgt) {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt
     *
     * @return integer 
     */
    public function getRgt() {
        return $this->rgt;
    }

    /**
     * Set root
     *
     * @param integer $root
     * @return Penjurusan
     */
    public function setRoot($root) {
        $this->root = $root;

        return $this;
    }

    /**
     * Get root
     *
     * @return integer 
     */
    public function getRoot() {
        return $this->root;
    }

    /**
     * Set parent
     *
     * @param Fast\SisdikBundle\Entity\Penjurusan $parent
     * @return Penjurusan
     */
    public function setParent(\Fast\SisdikBundle\Entity\Penjurusan $parent = null) {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return Fast\SisdikBundle\Entity\Penjurusan 
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * Set idsekolah
     *
     * @param Fast\SisdikBundle\Entity\Sekolah $idsekolah
     * @return Penjurusan
     */
    public function setIdsekolah(\Fast\SisdikBundle\Entity\Sekolah $idsekolah = null) {
        $this->idsekolah = $idsekolah;

        return $this;
    }

    /**
     * Get idsekolah
     *
     * @return Fast\SisdikBundle\Entity\Sekolah 
     */
    public function getIdsekolah() {
        return $this->idsekolah;
    }

    public function getOptionLabel() {
        return str_repeat(html_entity_decode('&nbsp;', ENT_QUOTES, 'UTF-8'), $this->getLvl() * 5)
                . $this->getNama() . ' (' . $this->getKode() . ')';
    }

    public function __toString() {
        return $this->getNama() . ' (' . $this->getKode() . ')';
    }
}