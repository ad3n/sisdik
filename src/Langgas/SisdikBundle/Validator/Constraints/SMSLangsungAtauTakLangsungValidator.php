<?php

namespace Langgas\SisdikBundle\Validator\Constraints;

use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use JMS\DiExtraBundle\Annotation\Validator;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @Validator("validasi.sms.langsung")
 */
class SMSLangsungAtauTakLangsungValidator extends ConstraintValidator
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

    public function validate($object, Constraint $constraint)
    {
        /* @var $context ExecutionContextInterface */
        $context = $this->context;
        if ($object->isLangsungKirimSms() == true && $object->getSmsJam() != '') {
            $context
                ->buildViolation($this->translator->trans('sms.langsung.atau.tak.langsung', [], 'validators'))
                ->atPath('langsungKirimSms')
                ->addViolation()
            ;
        }
    }
}
