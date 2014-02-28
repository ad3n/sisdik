<?php
namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="cukil_mp")
 * @ORM\Entity
 */
class CukilMp
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
     * @ORM\ManyToOne(targetEntity="TahunAkademik")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="tahun_akademik_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var TahunAkademik
     */
    private $tahunAkademik;

    /**
     * @ORM\ManyToOne(targetEntity="Semester")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="semester_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Semester
     */
    private $semester;

    /**
     * @ORM\ManyToOne(targetEntity="MataPelajaran")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="mata_pelajaran_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var MataPelajaran
     */
    private $mataPelajaran;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param TahunAkademik $tahunAkademik
     */
    public function setTahunAkademik(TahunAkademik $tahunAkademik = null)
    {
        $this->tahunAkademik = $tahunAkademik;
    }

    /**
     * @return TahunAkademik
     */
    public function getTahunAkademik()
    {
        return $this->tahunAkademik;
    }

    /**
     * @param Semester $semester
     */
    public function setSemester(Semester $semester = null)
    {
        $this->semester = $semester;
    }

    /**
     * @return Semester
     */
    public function getSemester()
    {
        return $this->semester;
    }

    /**
     * @param MataPelajaran $mataPelajaran
     */
    public function setMataPelajaran(MataPelajaran $mataPelajaran = null)
    {
        $this->mataPelajaran = $mataPelajaran;
    }

    /**
     * @return MataPelajaran
     */
    public function getMataPelajaran()
    {
        return $this->mataPelajaran;
    }
}
