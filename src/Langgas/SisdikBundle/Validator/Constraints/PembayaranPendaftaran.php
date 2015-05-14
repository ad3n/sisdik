<?php

namespace Langgas\SisdikBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PembayaranPendaftaran extends Constraint
{
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return 'validasi.pembayaran.pendaftaran';
    }
}
