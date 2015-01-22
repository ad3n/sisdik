<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\OrangtuaWali;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\SiswaKelas;
use Langgas\SisdikBundle\Entity\Tahun;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Langgas\SisdikBundle\Entity\Tingkat;
use Langgas\SisdikBundle\Entity\VendorSekolah;
use Langgas\SisdikBundle\Util\Messenger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/sebar-sms")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH')")
 */
class SebarSmsController extends Controller
{
    /**
     * @Route("/", name="sebarsms")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'sekolah' => $sekolah,
                'aktif' => true,
            ])
        ;

        if ($tahunAkademik instanceof TahunAkademik) {
            $formSebarViaAkademik = $this->createForm('sisdik_sebarsms_via_akademik');
        }

        $formSebarViaTahunMasuk = $this->createForm('sisdik_sebarsms_via_tahunmasuk');

        return [
            'tahunAkademik' => $tahunAkademik,
            'formSebarViaAkademik' => $tahunAkademik instanceof TahunAkademik ? $formSebarViaAkademik->createView() : null,
            'formSebarViaTahunMasuk' => $formSebarViaTahunMasuk->createView(),
        ];
    }

    /**
     * @Route("/via-akademik", name="sebarpesan_akademik")
     * @Method("POST")
     */
    public function sebarViaAkademikAction()
    {
        $sekolah = $this->getSekolah();

        /* @var $translator Translator */
        $translator = $this->get('translator');

        $em = $this->getDoctrine()->getManager();

        $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'sekolah' => $sekolah,
                'aktif' => true,
            ])
        ;

        $vendorSekolah = $em->getRepository('LanggasSisdikBundle:VendorSekolah')
            ->findOneBy([
                'sekolah' => $sekolah,
            ])
        ;
        if (!$vendorSekolah instanceof VendorSekolah) {
            $return['responseCode'] = 400;
            $return['responseText'] = $translator->trans("error.vendor.sekolah.tidak.tersedia");
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        }

        $layananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
            ->findOneBy([
                'sekolah' => $sekolah,
                'jenisLayanan' => 'zzb-broadcast-sms',
                'status' => true,
            ])
        ;
        if (!$layananSms instanceof PilihanLayananSms) {
            $return['responseCode'] = 400;
            $return['responseText'] = $translator->trans("error.layanan.sebar.sms.tidak.aktif");
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        }

        $formSebarViaAkademik = $this->createForm('sisdik_sebarsms_via_akademik');
        $formSebarViaAkademik->submit($this->getRequest());

        $retval = [];
        if ($formSebarViaAkademik->isValid()) {
            $querybuilder = $em->createQueryBuilder()
                ->select('siswaKelas')
                ->from('LanggasSisdikBundle:SiswaKelas', 'siswaKelas')
                ->leftJoin('siswaKelas.siswa', 'siswa')
                ->where('siswaKelas.tahunAkademik = :tahunAkademik')
                ->andWhere('siswa.sekolah = :sekolah')
                ->andWhere('siswa.calonSiswa = :calon')
                ->andWhere('siswaKelas.aktif = :aktif')
                ->setParameter("tahunAkademik", $tahunAkademik)
                ->setParameter("sekolah", $sekolah)
                ->setParameter("calon", false)
                ->setParameter('aktif', true)
            ;

            $tingkat = $formSebarViaAkademik->get('tingkat')->getData();
            if ($tingkat instanceof Tingkat) {
                $querybuilder
                    ->leftJoin('siswaKelas.kelas', 'kelas')
                    ->andWhere('kelas.tingkat = :tingkat')
                    ->setParameter('tingkat', $tingkat)
                ;
            }

            $kelas = $formSebarViaAkademik->get('kelas')->getData();
            if ($kelas instanceof Kelas) {
                $querybuilder
                    ->andWhere('siswaKelas.kelas = :kelas')
                    ->setParameter('kelas', $kelas)
                ;
            }

            $filter = $formSebarViaAkademik->get('filter')->getData();
            if ($filter != '') {
                $querybuilder
                    ->andWhere('siswa.nomorIndukSistem = :filter')
                    ->setParameter('filter', $filter)
                ;
            }

            $keSiswa = $formSebarViaAkademik->get('keSiswa')->getData();

            $siswaDiKelas = $querybuilder->getQuery()->getResult();

            $counter = 0;
            foreach ($siswaDiKelas as $siswaKelas) {
                if ($siswaKelas instanceof SiswaKelas) {
                    $pesan = $formSebarViaAkademik->get('pesan')->getData();
                    $pesan = str_replace("%tahun-akademik%", $tahunAkademik->getNama(), $pesan);

                    if ($tingkat instanceof Tingkat) {
                        $pesan = str_replace("%tingkat%", $tingkat->getNama(), $pesan);
                    }

                    if ($kelas instanceof Kelas) {
                        $pesan = str_replace("%kelas%", $kelas->getNama(), $pesan);
                    }

                    $pesan = str_replace("%nama-siswa%", $siswaKelas->getSiswa()->getNamaLengkap(), $pesan);
                    $pesan = str_replace("%nomor-induk%", $siswaKelas->getSiswa()->getNomorInduk(), $pesan);
                    $pesan = str_replace("%nama-ortu%", $siswaKelas->getSiswa()->getOrangtuaWaliAktif()->getNama(), $pesan);

                    if ($keSiswa) {
                        $nomorponsel = preg_split("/[\s,\/]+/", $siswaKelas->getSiswa()->getPonselSiswa());
                    } else {
                        $ortuWaliAktif = $siswaKelas->getSiswa()->getOrangtuaWaliAktif();
                        if ($ortuWaliAktif instanceof OrangtuaWali) {
                            $nomorponsel = preg_split("/[\s,\/]+/", $ortuWaliAktif->getPonsel());
                        } else {
                            $nomorponsel = [];
                        }
                    }

                    foreach ($nomorponsel as $ponsel) {
                        $messenger = $this->get('sisdik.messenger');
                        if ($ponsel != "") {
                            if ($messenger instanceof Messenger) {
                                if ($vendorSekolah instanceof VendorSekolah) {
                                    if ($vendorSekolah->getJenis() == 'khusus') {
                                        $messenger->setUseVendor(true);
                                        $messenger->setVendorURL($vendorSekolah->getUrlPengirimPesan());
                                    }
                                }
                                $messenger->setPhoneNumber($ponsel);
                                $messenger->setMessage($pesan);
                                $messenger->sendMessage($sekolah);

                                $counter++;
                            }
                        }
                    }
                }
            }

            $retval = [
                'responseText' => $translator->trans('info.sebar.sms.terkirim', ['%jumlah%' => $counter]),
                'error' => 0,
                'nextUrl' => $this->generateUrl('logsmskeluar'),
            ];
        } else {
            $retval = [
                'responseText' => $translator->trans('errorinfo.form.sebarsms.via.akademik'),
                'error' => 1,
            ];
        }

        return new Response(json_encode($retval), 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @Route("/via-tahun-masuk", name="sebarpesan_tahunmasuk")
     * @Method("POST")
     */
    public function sebarViaTahunMasukAction()
    {
        $sekolah = $this->getSekolah();

        $translator = $this->get('translator');

        $em = $this->getDoctrine()->getManager();

        $vendorSekolah = $em->getRepository('LanggasSisdikBundle:VendorSekolah')
            ->findOneBy([
                'sekolah' => $sekolah,
            ])
        ;
        if (!$vendorSekolah instanceof VendorSekolah) {
            $return['responseCode'] = 400;
            $return['responseText'] = $translator->trans("error.vendor.sekolah.tidak.tersedia");
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        }

        $layananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
            ->findOneBy([
                'sekolah' => $sekolah,
                'jenisLayanan' => 'zzb-broadcast-sms',
                'status' => true,
            ])
        ;
        if (!$layananSms instanceof PilihanLayananSms) {
            $return['responseCode'] = 400;
            $return['responseText'] = $translator->trans("error.layanan.sebar.sms.tidak.aktif");
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        }

        $formSebarViaTahunMasuk = $this->createForm('sisdik_sebarsms_via_tahunmasuk');
        $formSebarViaTahunMasuk->submit($this->getRequest());

        $retval = [];

        if ($formSebarViaTahunMasuk->isValid()) {
            $querybuilder = $em->createQueryBuilder()
                ->select('siswa')
                ->from('LanggasSisdikBundle:Siswa', 'siswa')
                ->where('siswa.sekolah = :sekolah')
                ->andWhere('siswa.calonSiswa = :calon')
                ->setParameter("sekolah", $sekolah)
                ->setParameter("calon", false)
            ;

            $tahun = $formSebarViaTahunMasuk->get('tahun')->getData();
            if ($tahun instanceof Tahun) {
                $querybuilder
                    ->andWhere('siswa.tahun = :tahun')
                    ->setParameter('tahun', $tahun)
                ;
            }

            $filter = $formSebarViaTahunMasuk->get('filter')->getData();
            if ($filter != '') {
                $querybuilder
                    ->andWhere('siswa.nomorIndukSistem = :filter')
                    ->setParameter('filter', $filter)
                ;
            }

            $keSiswa = $formSebarViaTahunMasuk->get('keSiswa')->getData();
            if ($keSiswa) {
                $querybuilder->andWhere("siswa.ponselSiswa <> ''");
            }

            $dataSiswa = $querybuilder->getQuery()->getResult();

            $counter = 0;
            foreach ($dataSiswa as $siswa) {
                if ($siswa instanceof Siswa) {
                    $pesan = $formSebarViaTahunMasuk->get('pesan')->getData();

                    if ($tahun instanceof Tahun) {
                        $pesan = str_replace("%tahun-masuk%", $tahun->getTahun(), $pesan);
                    }

                    $pesan = str_replace("%nama-siswa%", $siswa->getNamaLengkap(), $pesan);
                    $pesan = str_replace("%nomor-induk%", $siswa->getNomorInduk(), $pesan);
                    $pesan = str_replace("%nama-ortu%", $siswa->getOrangtuaWaliAktif()->getNama(), $pesan);

                    if ($keSiswa) {
                        $nomorponsel = preg_split("/[\s,\/]+/", $siswa->getPonselSiswa());
                    } else {
                        $ortuWaliAktif = $siswa->getOrangtuaWaliAktif();
                        if ($ortuWaliAktif instanceof OrangtuaWali) {
                            $nomorponsel = preg_split("/[\s,\/]+/", $ortuWaliAktif->getPonsel());
                        } else {
                            $nomorponsel = [];
                        }
                    }

                    foreach ($nomorponsel as $ponsel) {
                        $messenger = $this->get('sisdik.messenger');
                        if ($ponsel != "") {
                            if ($messenger instanceof Messenger) {
                                if ($vendorSekolah instanceof VendorSekolah) {
                                    if ($vendorSekolah->getJenis() == 'khusus') {
                                        $messenger->setUseVendor(true);
                                        $messenger->setVendorURL($vendorSekolah->getUrlPengirimPesan());
                                    }
                                }
                                $messenger->setPhoneNumber($ponsel);
                                $messenger->setMessage($pesan);
                                $messenger->sendMessage($sekolah);

                                $counter++;
                            }
                        }
                    }
                }
            }

            $retval = [
                'responseText' => $translator->trans('info.sebar.sms.terkirim', ['%jumlah%' => $counter]),
                'error' => 0,
                'nextUrl' => $this->generateUrl('logsmskeluar'),
            ];
        } else {
            $retval = [
                'responseText' => $translator->trans('errorinfo.form.sebarsms.via.tahun.masuk'),
                'error' => 1,
            ];
        }

        return new Response(json_encode($retval), 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Mendapatkan nama siswa dan nomorinduksistem berdasarkan tingkat dan/atau kelas
     *
     * @Route("/ajax/saring-pakai-tingkat-kelas", name="siswa_filter_pakai_tingkat_kelas")
     */
    public function ajaxFilterSiswaPakaiTingkatKelasAction(Request $request)
    {
        $sekolah = $this->getSekolah();
        $em = $this->getDoctrine()->getManager();

        $tahunAkademik = $this->getRequest()->query->get('tahunAkademik');
        $tingkat = $this->getRequest()->query->get('tingkat');
        $kelas = $this->getRequest()->query->get('kelas');
        $filter = $this->getRequest()->query->get('filter');

        $querybuilder = $em->createQueryBuilder()
            ->select('siswaKelas')
            ->from('LanggasSisdikBundle:SiswaKelas', 'siswaKelas')
            ->leftJoin('siswaKelas.siswa', 'siswa')
            ->where('siswaKelas.tahunAkademik = :tahunAkademik')
            ->andWhere('siswa.sekolah = :sekolah')
            ->andWhere('siswa.calonSiswa = :calon')
            ->andWhere('siswaKelas.aktif = :aktif')
            ->andWhere('siswa.nomorIndukSistem LIKE :filter OR siswa.namaLengkap LIKE :filter')
            ->setParameter("tahunAkademik", $tahunAkademik)
            ->setParameter("sekolah", $sekolah)
            ->setParameter("calon", false)
            ->setParameter('aktif', true)
            ->setParameter('filter', "%$filter%")
        ;

        if ($tingkat != '') {
            $querybuilder
                ->leftJoin('siswaKelas.kelas', 'kelas')
                ->andWhere('kelas.tingkat = :tingkat')
                ->setParameter('tingkat', $tingkat)
            ;
        }

        if ($kelas != '') {
            $querybuilder
                ->andWhere('siswaKelas.kelas = :kelas')
                ->setParameter('kelas', $kelas)
            ;
        }

        $results = $querybuilder->getQuery()->getResult();

        $retval = [];
        foreach ($results as $result) {
            /* @var $result SiswaKelas */
            $retval[] = [
                'label' =>/** @Ignore */ $result->getSiswa()->getNamaLengkap()." ({$result->getSiswa()->getNomorIndukSistem()})",
                'value' => $result->getSiswa()->getNomorIndukSistem(),
            ];
        }

        return new Response(json_encode($retval), 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.sms', [], 'navigations')][$translator->trans('links.sebar.sms', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
