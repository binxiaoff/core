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
            new TwigFilter('localizedcurrencywithprecision', [$this, 'localizedCurrencyWithPrecisionFilter']),
        ]);
    }

    /**
     * @param string|int|float $number
     * @param mixed            $style
     * @param mixed            $type
     * @param mixed|null       $locale
     *
     * @throws SyntaxError
     *
     * @return bool|string
     */
    public function localizedNumberFilter($number, $style = 'decimal', $type = 'default', $locale = null)
    {
        $formatted = twig_localized_number_filter($number, $style, $type, $locale);

        if (false !== $formatted) {
            $formatter = twig_get_number_formatter($locale, $style);
            $formatted = str_replace($formatter->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL), html_entity_decode('&nbsp;'), $formatted);
        }

        return $formatted;
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

    /**
     * @param string|int|float $number
     * @param int              $fractionDigits
     * @param null             $currency
     * @param null             $locale
     *
     * @throws SyntaxError
     *
     * @return string
     */
    public function localizedCurrencyWithPrecisionFilter($number, int $fractionDigits, $currency = null, $locale = null)
    {
        $formatter = twig_get_number_formatter($locale, 'currency');
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $fractionDigits);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $fractionDigits);
        $formatter->setSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL, html_entity_decode('&nbsp;'));

        return $formatter->formatCurrency($number, $currency);
    }
}
