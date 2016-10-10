<?php
namespace Unilend\Bundle\FrontBundle\Twig;

class IntlExtension extends \Twig_Extensions_Extension_Intl
{
    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return array An array of filters
     */
    public function getFilters()
    {
        return array_merge(
            parent::getFilters(),
            [new \Twig_SimpleFilter('localizednumberwithprecision', [$this, 'localizedNumberWithPrecisionFilter'])]
        );
    }

    public function localizedNumberWithPrecisionFilter($number, $fractionDigits)
    {
        $formatter = twig_get_number_formatter(null, 'decimal');
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $fractionDigits);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $fractionDigits);

        return $formatter->format($number, \NumberFormatter::TYPE_DEFAULT);
    }
}
