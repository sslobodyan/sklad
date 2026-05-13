<?php
/**
 * Контролер експорту ресурсів
 */
class ResourceExportController extends Controller
{
    private ResourceModel $model;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->model = new ResourceModel($db);
    }

    public function export(): void
    {
        $filters = [
            'warehouse_id' => $this->get('warehouse_id'),
            'resource_type_id' => $this->get('resource_type_id'),
            'date_from' => $this->get('date_from'),
            'date_to' => $this->get('date_to'),
        ];

        $logs = $this->model->getLogs($filters);
        $rows = $this->buildExportRows($logs);

        $filename = $this->buildFilename($filters);
        $xlsx = $this->generateXlsx($rows, 'Витрата ресурсів');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($xlsx));
        echo $xlsx;
        exit;
    }

    private function buildExportRows(array $logs): array
    {
        $rows = [];
        $rows[] = ['Дата', 'Склад', 'Ресурс', 'Показник', 'Попередній', 'Витрата', 'Поправка', 'Примітка'];

        foreach ($logs as $l) {
            $fmt = $l['format'] ?? 'dec2';
            $rows[] = [
                date('d.m.Y', strtotime($l['log_date'])),
                $l['warehouse_name'],
                $l['type_name'] . ' (' . $l['unit'] . ')',
                $this->formatVal($l['reading'], $fmt),
                $l['prev_reading'] !== null ? $this->formatVal($l['prev_reading'], $fmt) : '',
                $l['delta'] !== null ? $this->formatVal($l['delta'], $fmt) : '',
                ($l['correction_pct'] > 0) || ($l['correction_pct'] < 0) ? $l['correction_pct'] : '',
                $l['note'] ?? '',
            ];
        }

        return $rows;
    }

    private function formatVal($v, string $fmt): string
    {
        if ($v === null || $v === '') return '';
        $v = (float)$v;
        switch ($fmt) {
            case 'int': return (string)(int)$v;
            case 'hm':
                $h = (int)floor($v);
                $m = (int)round(($v - $h) * 60);
                return $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
            default: return number_format($v, 2, '.', '');
        }
    }

    private function buildFilename(array $filters): string
    {
        $filename = 'Витрата_ресурсів';
        if (!empty($filters['date_from'])) $filename .= '_від_' . $filters['date_from'];
        if (!empty($filters['date_to'])) $filename .= '_до_' . $filters['date_to'];
        return $filename . '.xlsx';
    }

    private function generateXlsx(array $rows, string $sheetName): string
    {
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        
        foreach ($rows as $ri => $row) {
            $r = $ri + 1;
            $sheetXml .= '<row r="' . $r . '">';
            foreach ($row as $ci => $cell) {
                $ref = chr(65 + $ci) . $r;
                $sheetXml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . htmlspecialchars((string)$cell, ENT_XML1) . '</t></is></c>';
            }
            $sheetXml .= '</row>';
        }
        
        $sheetXml .= '</sheetData></worksheet>';

        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>';
        
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . htmlspecialchars($sheetName, ENT_XML1) . '" sheetId="1" r:id="rId1"/></sheets></workbook>';
        
        $wbr = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>';

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $ct);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $wb);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbr);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();
        
        $content = file_get_contents($tmp);
        unlink($tmp);
        return $content;
    }
}