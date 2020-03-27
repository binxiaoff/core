<?php

declare(strict_types=1);

namespace Unilend\DataTransformer;

use Unilend\Entity\File;

class FileInputDataTransformer
{
    /**
     * @param        $data
     * @param string $to
     * @param array  $context
     */
    public function transform($data, string $to, array $context = [])
    {
        // transform fileInput into File
    }

    /**
     * @param        $data
     * @param string $to
     * @param array  $context
     *
     * @return bool
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        // in the case of an input, the value given here is an array (the JSON decoded).
        // if it's a book we transformed the data already
        if ($data instanceof File) {
            return false;
        }

        return File::class === $to && null !== ($context['input']['class'] ?? null);
    }
}
