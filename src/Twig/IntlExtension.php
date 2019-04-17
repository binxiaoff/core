<?php

namespace Unilend\Twig;

use NumberFormatter;
use Twig\Error\SyntaxError;
use Twig\Extensions\IntlExtension as BaseIntlExtension;
use Twig\TwigFilter;

class IntlExtension extends BaseIntlExtension
{
    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return array An array of filters
     */
    public function getFilters(): array
    {
        return array_merge(parent::getFilters(), [
            new TwigFilter('localizednumber', [$this, 'localizedNumberFilter']),
            new TwigFilter('localizednumberwithprecision', [$this, 'localizedNumberWithPrecisionFilter']),
        ]);
    }

    /**
     * @param string|int|float $number
     *
     * @throws SyntaxError
     *
     * @return bool|string
     */
    public function localizedNumberFilter($number)
    {
        $formatter = twig_get_number_formatter(null, 'decimal');
        $formatter->setSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL, html_entity_decode('&nbsp;'));

        return $formatter->format($number, NumberFormatter::TYPE_DEFAULT);
    }

    /**
     * @param string|int|float $number
     * @param int              $fractionDigits
     *
     * @throws SyntaxError
     *
     * @return bool|string
     */
    public function localizedNumberWithPrecisionFilter($number, int $fractionDigits)
    {
        $formatter = twig_get_number_formatter(null, 'decimal');
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $fractionDigits);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $fractionDigits);
        $formatter->setSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL, html_entity_decode('&nbsp;'));

        return $formatter->format($number, NumberFormatter::TYPE_DEFAULT);
    }
}
