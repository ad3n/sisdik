<?php

namespace Langgas\SisdikBundle\Translator;

use Symfony\Component\Translation\TranslatorInterface;

class NoTranslator implements TranslatorInterface
{
    public function trans($id, array $parameters = array(), $domain = null, $locale = null) {
        return $id;
    }

    public function transChoice($id, $number, array $parameters = array(), $domain = null, $locale = null) {
        return $id;
    }

    public function setLocale($locale) {
    }

    public function getLocale() {
        return '--';
    }

    public function setFallbackLocales($locale) {
    }

    public function addResource($resource) {
    }
}
