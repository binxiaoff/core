<?php

declare(strict_types=1);

namespace Unilend\Form\Traits;

use Unilend\Service\Translation\TranslationLoader;

trait ConstantsToChoicesTrait
{
    /**
     * @param array  $constants
     * @param string $translationSection
     *
     * @return array
     */
    private function getChoicesFromConstants(array $constants, string $translationSection): array
    {
        $choices = [];

        foreach ($constants as $name => $value) {
            $choices[$translationSection . TranslationLoader::SECTION_SEPARATOR . mb_strtolower($name)] = $value;
        }

        return $choices;
    }
}
