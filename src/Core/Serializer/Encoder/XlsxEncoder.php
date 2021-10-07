<?php

declare(strict_types=1);

namespace KLS\Core\Serializer\Encoder;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

class XlsxEncoder implements DecoderInterface
{
    public const FORMAT = 'xlsx';

    /** TODO: this encoder allows you to work only with collection */
    public function decode(string $data, string $format, array $context = [])
    {
        $handle = \tmpfile();
        \fwrite($handle, $data);

        $file = \stream_get_meta_data($handle);

        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($file['uri']);

        $sheetIterator = $reader->getSheetIterator();
        $sheetIterator->rewind();

        $sheet = $sheetIterator->current();

        $rowIterator = $sheet->getRowIterator();

        $rowIterator->rewind();

        $header = $rowIterator->current();

        $rowIterator->next();

        $header = \array_map('strval', $header->getCells());

        $result = [];
        while ($rowIterator->valid()) {
            $rowResult = \array_map('strval', $rowIterator->current()->getCells());
            $result[]  = \array_combine($header, $rowResult);

            $rowIterator->next();
        }

        \fclose($handle);

        return $result;
    }

    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format;
    }
}
