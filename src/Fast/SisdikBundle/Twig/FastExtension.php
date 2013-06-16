<?php

namespace Fast\SisdikBundle\Twig;
class FastExtension extends \Twig_Extension
{
    public function getFunctions() {
        return array(
                'currencySymbol' => new \Twig_Function_Method($this, 'currencySymbolFunction'),
                'highlightResult' => new \Twig_Function_Method($this, 'highlightResultFunction'),
        );
    }

    public function getFilters() {
        return array(
            'json_decode' => new \Twig_Filter_Method($this, 'jsonDecode'),
        );
    }

    public function jsonDecode($str) {
        return json_decode($str);
    }

    public function currencySymbolFunction($locale) {
        $locale = $locale == null ? \Locale::getDefault() : $locale;
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);

        return $symbol;
    }

    public function highlightResultFunction($subject, $search) {
        return preg_replace("/" . preg_quote($search, "/") . "/i", "<mark>$0</mark>",
                htmlspecialchars($subject));
    }

    public function getName() {
        return 'fast_extension';
    }
}

