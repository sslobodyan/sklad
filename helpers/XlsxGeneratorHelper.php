<?php
/**
 * XlsxGeneratorHelper
 * Генерація Excel файлів без зовнішніх бібліотек
 */
class XlsxGeneratorHelper
{
    /**
     * Генерація XLSX з масиву рухів
     */
    public static function generate(array $movements): string
    {
        $rows = [];
        $rows[] = ['Дата', 'Звідки', 'Куди', 'Матеріал', 'Кількість', 'Показник', 'Дельта', 'Норма', 'Поправка', 'Примітка'];

        foreach ($movements as $m) {
            $isAuto = !empty($m['resource_log_id']);
            $fmt = $m['resource_format'] ?? 'dec2';
            $rows[] = [
                date('d.m.Y', strtotime($m['movement_date'])),
                $m['warehouse_from_name'] ?? '',
                $m['warehouse_to_name'] ?? '',
                $m['material_name'],
                (float)$m['quantity'],
                $isAuto ? self::formatResourceValue($m['resource_value'] ?? null, $fmt) : '',
                $isAuto ? self::formatResourceValue($m['resource_delta'] ?? null, $fmt) : '',
                $isAuto && isset($m['resource_rate']) ? rtrim(rtrim(number_format((float)$m['resource_rate'], 6, '.', ''), '0'), '.') : '',
                ($m['resource_correction'] > 0 || $m['resource_correction'] < 0) ? number_format((float)$m['resource_correction'], 2, '.', '') . '%' : '',
                $m['note'] ?? '',
            ];
        }

        $sheetXml = self::buildSheetXml($rows);
        $contentTypes = self::buildContentTypes();
        $rels = self::buildRels();
        $workbook = self::buildWorkbook();
        $workbookRels = self::buildWorkbookRels();

        return self::createZip($sheetXml, $contentTypes, $rels, $workbook, $workbookRels);
    }

    private static function formatResourceValue($value, string $format = 'dec2'): string
    {
        if ($value === null || $value === '') return '';
        $v = (float)$value;
        switch ($format) {
            case 'int':  return (string)(int)round($v);
            case 'hm':
                $h = (int)floor($v);
                $m = (int)round(($v - $h) * 60);
                return $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
            case 'dec2':
            default:     return number_format($v, 2, '.', '');
        }
    }

    private static function buildSheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<cols>';
        $xml .= '<col min="1" max="1" width="12" bestFit="1" customWidth="1"/>';
        $xml .= '<col min="2" max="3" width="20" bestFit="1" customWidth="1"/>';
        $xml .= '<col min="4" max="4" width="25" bestFit="1" customWidth="1"/>';
        $xml .= '<col min="5" max="5" width="12" bestFit="1" customWidth="1"/>';
        $xml .= '<col min="6" max="6" width="12" bestFit="1" customWidth="1"/>';
        $xml .= '<col min="7" max="7" width="12" bestFit="1" customWidth="1"/>';
        $xml .= '<col min="8" max="8" width="10" bestFit="1" customWidth="1"/>';
        $xml .= '<col min="9" max="9" width="10" bestFit="1" customWidth="1"/>';
        $xml .= '<col min="10" max="10" width="40" bestFit="1" customWidth="1"/>';
        $xml .= '</cols>';
        $xml .= '<sheetData>';

        foreach ($rows as $rowIdx => $row) {
            $r = $rowIdx + 1;
            $xml .= '<row r="' . $r . '">';
            foreach ($row as $colIdx => $cell) {
                $colLetter = chr(65 + $colIdx);
                $ref = $colLetter . $r;
                if (is_numeric($cell) && !is_string($cell)) {
                    $xml .= '<c r="' . $ref . '"><v>' . $cell . '</v></c>';
                } else {
                    $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . htmlspecialchars((string)$cell, ENT_XML1) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData>';
        $lastCol = chr(65 + count($rows[0]) - 1);
        $lastRow = count($rows);
        $xml .= '<autoFilter ref="A1:' . $lastCol . $lastRow . '"/>';
        $xml .= '</worksheet>';
        
        return $xml;
    }

    private static function buildContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';
    }

    private static function buildRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function buildWorkbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Рух" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function buildWorkbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '</Relationships>';
    }

    private static function createZip(string $sheetXml, string $contentTypes, string $rels, string $workbook, string $workbookRels): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        $content = file_get_contents($tmpFile);
        unlink($tmpFile);
        return $content;
    }
}