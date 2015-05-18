<?php

namespace Langgas\SisdikBundle\Validator\Constraints;

use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use JMS\DiExtraBundle\Annotation\Validator;
use Langgas\SisdikBundle\Entity\PembayaranPendaftaran as EntityPembayaranPendaftaran;
use Langgas\SisdikBundle\Entity\TransaksiPembayaranPendaftaran;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @Validator("validasi.pembayaran.pendaftaran")
 */
class PembayaranPendaftaranValidator extends ConstraintValidator
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @InjectParams({
     *     "translator" = @Inject("translator")
     * })
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param $object     EntityPembayaranPendaftaran
     * @param $constraint Constraint
     */
    public function validate($object, Constraint $constraint)
    {
        /* @var $context ExecutionContextInterface */
        $context = $this->context;

        $totalTransaksi = $object->getTotalNominalTransaksiPembayaranPendaftaran();
        $jumlahBiaya = $object->getNominalTotalBiaya() - ($object->getNominalPotongan() + $object->getPersenPotonganDinominalkan());

        if ($object->getTransaksiPembayaranPendaftaran()->count() == 1) {
            $object->tertibBiayaPembayaran();
        }

        /*
        $context
            ->buildViolation(
                $object->getDaftarBiayaPendaftaran()->count().'; '
                .$object->getTransaksiPembayaranPendaftaran()->count().'; '
                .$totalTransaksi.'; '
                .$jumlahBiaya.'; '
                .$object->getNominalTotalBiaya().' - ('
                .$object->getNominalPotongan().' + '
                .$object->getPersenPotonganDinominalkan().') = '
                .($object->getNominalTotalBiaya() - ($object->getNominalPotongan() + $object->getPersenPotonganDinominalkan())).'; '
            )
            ->addViolation()
        ;
        */

        if ($object->getAdaPotongan() && ($object->getNominalPotongan() + $object->getPersenPotonganDinominalkan() <= 0)) {
            $context
                ->buildViolation($this->translator->trans('jumlah.potongan.tak.boleh.nol.atau.negatif', [], 'validators'))
                ->addViolation()
            ;
        }

        if ($totalTransaksi > $object->getNominalTotalBiaya() - ($object->getNominalPotongan() + $object->getPersenPotonganDinominalkan())) {
            $context
                ->buildViolation($this->translator->trans('jumlah.bayar.tak.bisa.lebih.besar.dari.biaya', [], 'validators'))
                ->addViolation()
            ;
        }

        if ($totalTransaksi < 0) {
            $context
                ->buildViolation($this->translator->trans('jumlah.bayar.tak.boleh.negatif', [], 'validators'))
                ->addViolation()
            ;
        }

        if ($object->getDaftarBiayaPendaftaran()->count() < 1) {
            $context
                ->buildViolation($this->translator->trans('item.biaya.harus.dipilih', [], 'validators'))
                ->addViolation()
            ;
        } elseif ($object->getDaftarBiayaPendaftaran()->count() == 1) {
            if ($object->getTransaksiPembayaranPendaftaran()->count() <= 1) {
                if ($totalTransaksi == 0 && $totalTransaksi < $object->getNominalTotalBiaya() - ($object->getNominalPotongan() + $object->getPersenPotonganDinominalkan())) {
                    $context
                        ->buildViolation($this->translator->trans('jumlah.bayar.cicilan.tak.boleh.nol', [], 'validators'))
                        ->addViolation()
                    ;
                }
            } else {
                /* @var $transaksi TransaksiPembayaranPendaftaran */
                $transaksi = $object->getTransaksiPembayaranPendaftaran()->last();
                if ($transaksi->getNominalPembayaran() <= 0) {
                    $context
                        ->buildViolation($this->translator->trans('jumlah.bayar.cicilan.tak.boleh.nol.atau.negatif', [], 'validators'))
                        ->addViolation()
                    ;
                }
            }
        } elseif ($object->getDaftarBiayaPendaftaran()->count() > 1) {
            if ($totalTransaksi >= 0 && $totalTransaksi < $object->getNominalTotalBiaya() - ($object->getNominalPotongan() + $object->getPersenPotonganDinominalkan())) {
                $context
                    ->buildViolation($this->translator->trans('biaya.lebih.dari.satu.tak.bisa.dicicil', [], 'validators'))
                    ->addViolation()
                ;
            }
        }
    }
}
