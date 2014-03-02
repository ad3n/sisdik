<?php
namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="penjurusan", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="unique_idx",
 *     columns={"sekolah_id", "kode"})
 * })
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
class Penjurusan
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="nama", type="string", length=300, nullable=true)
     *
     * @var string
     */
    private $nama;

    /**
     * @ORM\Column(name="kode", type="string", length=50, nullable=false)
     *
     * @var string
     */
    private $kode;

    /**
     * @ORM\Column(name="kepala", type="string", length=400, nullable=true)
     *
     * @var string
     */
    private $kepala;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer", nullable=true)
     *
     * @var integer
     */
    private $lft;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer", nullable=true)
     *
     * @var integer
     */
    private $lvl;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer", nullable=true)
     *
     * @var integer
     */
    private $rgt;

    /**
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=true)
     *
     * @var integer
     */
    private $root;

    /**
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="sekolah_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Sekolah
     */
    private $sekolah;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Penjurusan", inversedBy="children")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     * })
     *
     * @var Penjurusan
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Penjurusan", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $nama
     */
    public function setNama($nama)
    {
        $this->nama = $nama;
    }

    /**
     * @return string
     */
    public function getNama()
    {
        return $this->nama;
    }

    /**
     * @param string $kode
     */
    public function setKode($kode)
    {
        $this->kode = $kode;
    }

    /**
     * @return string
     */
    public function getKode()
    {
        return $this->kode;
    }

    /**
     * @param string $kepala
     */
    public function setKepala($kepala)
    {
        $this->kepala = $kepala;
    }

    /**
     * @return string
     */
    public function getKepala()
    {
        return $this->kepala;
    }

    /**
     * @param integer $lft
     */
    public function setLft($lft)
    {
        $this->lft = $lft;
    }

    /**
     * @return integer
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * @param integer $lvl
     */
    public function setLvl($lvl)
    {
        $this->lvl = $lvl;
    }

    /**
     * @return integer
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * @param integer $rgt
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;
    }

    /**
     * @return integer
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * @param integer $root
     */
    public function setRoot($root)
    {
        $this->root = $root;
    }

    /**
     * @return integer
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param Sekolah $sekolah
     */
    public function setSekolah(Sekolah $sekolah = null)
    {
        $this->sekolah = $sekolah;
    }

    /**
     * @return Sekolah
     */
    public function getSekolah()
    {
        return $this->sekolah;
    }

    /**
     * @param Penjurusan $parent
     */
    public function setParent(Penjurusan $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * @return Penjurusan
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return string
     */
    public function getOptionLabel()
    {
        return
            str_repeat(html_entity_decode('&nbsp;', ENT_QUOTES, 'UTF-8'), $this->getLvl() * 5)
            . $this->getNama()
            . ' (' . $this->getKode() . ')'
        ;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getNama() . ' (' . $this->getKode() . ')';
    }
}
