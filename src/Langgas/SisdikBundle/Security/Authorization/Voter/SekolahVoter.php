<?php

namespace Langgas\SisdikBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SekolahVoter implements VoterInterface
{
    const CREATE = 'create';
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    public function supportsAttribute($attribute)
    {
        return in_array($attribute, [
            self::CREATE,
            self::VIEW,
            self::EDIT,
            self::DELETE,
        ]);
    }

    public function supportsClass($class)
    {
        $supportedClasses = [
            'Langgas\SisdikBundle\Entity\Gelombang',
            'Langgas\SisdikBundle\Entity\JadwalKehadiran',
            'Langgas\SisdikBundle\Entity\JadwalKepulangan',
            'Langgas\SisdikBundle\Entity\JenisDokumenSiswa',
            'Langgas\SisdikBundle\Entity\JenisImbalan',
            'Langgas\SisdikBundle\Entity\Jenisbiaya',
            'Langgas\SisdikBundle\Entity\KategoriPotongan',
            'Langgas\SisdikBundle\Entity\Kelas',
            'Langgas\SisdikBundle\Entity\LayananSmsPeriodik',
            'Langgas\SisdikBundle\Entity\LayananSms',
            'Langgas\SisdikBundle\Entity\MesinKehadiran',
            'Langgas\SisdikBundle\Entity\PanitiaPendaftaran',
            'Langgas\SisdikBundle\Entity\Penjurusan',
            'Langgas\SisdikBundle\Entity\PilihanCetakKwitansi',
            'Langgas\SisdikBundle\Entity\Referensi',
            'Langgas\SisdikBundle\Entity\SekolahAsal',
            'Langgas\SisdikBundle\Entity\Siswa',
            'Langgas\SisdikBundle\Entity\TahunAkademik',
            'Langgas\SisdikBundle\Entity\Tahun',
            'Langgas\SisdikBundle\Entity\Templatesms',
            'Langgas\SisdikBundle\Entity\Tingkat',
            'Langgas\SisdikBundle\Entity\TransaksiPembayaranPendaftaran',
            'Langgas\SisdikBundle\Entity\TransaksiPembayaranSekali',
            'Langgas\SisdikBundle\Entity\User',
        ];

        return in_array($class, $supportedClasses);
    }

    public function vote(TokenInterface $token, $entity, array $attributes)
    {
        if (!$this->supportsClass(get_class($entity))) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        if (1 !== count($attributes)) {
            throw new \InvalidArgumentException('Hanya satu atribut yang bisa diperiksa');
        }

        $attribute = $attributes[0];

        if (!$this->supportsAttribute($attribute)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return VoterInterface::ACCESS_DENIED;
        }

        switch ($attribute) {
            case self::CREATE:
            case self::VIEW:
            case self::EDIT:
            case self::DELETE:
                if ($user->getSekolah()->getId() === $entity->getSekolah()->getId()) {
                    return VoterInterface::ACCESS_GRANTED;
                }
                break;
        }

        return VoterInterface::ACCESS_DENIED;
    }
}
