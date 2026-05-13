<?php
/**
 * XlsxParserHelper
 * Парсинг Excel файлів без зовнішніх бібліотек
 */
class XlsxParserHelper
{
    /**
     * Парсинг XLSX файлу в масив рядків
     */
    public static function parse(string $filepath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filepath) !== true) {
            throw new Exception('Не вдалося відкрити файл як ZIP');
        }

        $sharedStrings = self::parseSharedStrings($zip);
        $rows = self::parseSheetData($zip, $sharedStrings);
        
        $zip->close();
        return $rows;
    }

    private static function parseSharedStrings($zip): array
    {
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        
        if ($ssXml) {
            $ss = new SimpleXMLElement($ssXml);
            foreach ($ss->si as $si) {
                $text = '';
                if (isset($si->t)) {
                    $text = (string)$si->t;
                } elseif (isset($si->r)) {
                    foreach ($si->r as $run) {
                        $text .= (string)$run->t;
                    }
                }
                $sharedStrings[] = $text;
            }
        }
        return $sharedStrings;
    }

    private static function parseSheetData($zip, array $sharedStrings): array
    {
        $sheetXml = $zip->getFromString('xl/worksheets/sheet1.xml');
        if (!$sheetXml) {
            throw new Exception('Не знайдено лист sheet1 у файлі');
        }
        
        $sheet = new SimpleXMLElement($sheetXml);
        $rows = [];
        
        foreach ($sheet->sheetData->row as $rowEl) {
            $rowData = [];
            $maxCol = 0;
            foreach ($rowEl->c as $cell) {
                $ref = (string)$cell['r'];
                $colIndex = self::colToIndex($ref);
                $maxCol = max($maxCol, $colIndex);

                $value = '';
                $type = (string)$cell['t'];
                if ($type === 's') {
                    $idx = (int)$cell->v;
                    $value = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string)$cell->is->t;
                } else {
                    $value = (string)$cell->v;
                }

                while (count($rowData) < $colIndex) {
                    $rowData[] = '';
                }
                $rowData[$colIndex] = $value;
            }
            $rows[] = $rowData;
        }
        return $rows;
    }

    private static function colToIndex(string $cellRef): int
    {
        preg_match('/^([A-Z]+)/', $cellRef, $m);
        $col = $m[1];
        $result = 0;
        for ($i = 0; $i < strlen($col); $i++) {
            $result = $result * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return $result - 1;
    }
}