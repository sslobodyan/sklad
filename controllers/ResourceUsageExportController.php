<?php
/**
 * Контролер експорту звіту по використанню ресурсів
 */
class ResourceUsageExportController extends Controller
{
    private ResourceTypesModel $typesModel;
    private ResourceRatesModel $ratesModel;
    private ResourceUsageReportModel $reportModel;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->typesModel = new ResourceTypesModel($db);
        $this->ratesModel = new ResourceRatesModel($db);
        $this->reportModel = new ResourceUsageReportModel($db);
    }

    public function export(): void
    {
        $resourceTypeId = (int)$this->get('resource_type_id', 0);
        $dateFrom = $this->get('date_from', SettingsController::getDateFrom());
        $dateTo = $this->get('date_to', SettingsController::getDateTo());

        if ($resourceTypeId <= 0) {
            $this->flash('error', 'Виберіть тип ресурсу');
            $this->redirect('reports/resource');
            return;
        }

        // Отримуємо всі склади з нормами для даного типу ресурсу
        $warehouses = $this->ratesModel->getWarehousesByResourceType($resourceTypeId);
        $warehouseIds = array_column($warehouses, 'id');
        
        if (empty($warehouseIds)) {
            $this->flash('error', 'Немає даних для експорту');
            $this->redirect('reports/resource');
            return;
        }

        $reportData = $this->reportModel->getDetailedReport(
            $resourceTypeId,
            $warehouseIds,
            $dateFrom,
            $dateTo
        );

        // Отримуємо назву ресурсу
        $typeInfo = $this->typesModel->getTypeById($resourceTypeId);
        $resourceName = $typeInfo['name'] ?? 'Ресурс';
        $resourceUnit = $typeInfo['unit'] ?? '';

        // Генеруємо Excel з двома листами
        $excel = $this->generateExcel($reportData, $resourceName, $resourceUnit, $dateFrom, $dateTo);
        
        $filename = 'Звіт_по_ресурсах_' . date('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($excel));
        header('Cache-Control: max-age=0');
        echo $excel;
        exit;
    }

    private function generateExcel(array $reportData, string $resourceName, string $resourceUnit, string $dateFrom, string $dateTo): string
    {
        // Лист 1: Деталізація (як на екрані)
        $sheet1Rows = [];
        $sheet1Rows[] = ['Звіт по використанню ресурсів'];
        $sheet1Rows[] = ['Ресурс:', $resourceName . ' (' . $resourceUnit . ')'];
        $sheet1Rows[] = ['Період:', date('d.m.Y', strtotime($dateFrom)) . ' - ' . date('d.m.Y', strtotime($dateTo))];
        $sheet1Rows[] = [];
        $sheet1Rows[] = ['Матеріал / Склад / Дата', 'Показник (' . $resourceUnit . ')', 'Δ ресурсу', 'Норма', 'Поправка', 'Вх.сальдо', 'Прихід', 'Витрата', 'Вих.сальдо'];
        
        foreach ($reportData as $material) {
            // Заголовок матеріалу
            $sheet1Rows[] = [$material['material_name'], '', number_format($material['total_delta'], 2), '', '', number_format($material['total_opening'], 2), number_format($material['total_incoming'], 2), number_format($material['total_consumed'], 2), number_format($material['total_closing'], 2)];
            
            foreach ($material['warehouses'] as $warehouse) {
                // Заголовок складу
                $sheet1Rows[] = ['  ' . $warehouse['warehouse_name'], '', number_format($warehouse['total_delta'], 2), '', '', number_format($warehouse['opening_balance'], 2), number_format($warehouse['total_incoming'], 2), number_format($warehouse['total_consumed'], 2), number_format($warehouse['closing_balance'], 2)];
                
                // Деталізація по днях
                foreach ($warehouse['rows'] as $row) {
                    $note = $row['note'];
                    if ($row['has_manual']) {
                        $note = '⚠️ Ручне списання: ' . $note;
                    }
                    $sheet1Rows[] = [
                        '    ' . date('d.m.Y', strtotime($row['date'])) . ($note ? ' (' . $note . ')' : ''),
                        $row['reading'],
                        $row['delta'],
                        $row['rate'],
                        $row['correction_pct'],
                        number_format($row['opening_balance'], 2),
                        $row['incoming'],
                        $row['consumed'],
                        number_format($row['closing_balance'], 2)
                    ];
                }
            }
            $sheet1Rows[] = []; // Порожній рядок між матеріалами
        }
        
        // Підсумок
        $sheet1Rows[] = [];
        $sheet1Rows[] = ['Загальний підсумок', '', number_format(array_sum(array_column($reportData, 'total_delta')), 2), '', '', number_format(array_sum(array_column($reportData, 'total_opening')), 2), number_format(array_sum(array_column($reportData, 'total_incoming')), 2), number_format(array_sum(array_column($reportData, 'total_consumed')), 2), number_format(array_sum(array_column($reportData, 'total_closing')), 2)];
        
        // Лист 2: Плоска таблиця для зведеної таблиці
        $sheet2Rows = [];
        $sheet2Rows[] = ['Матеріал', 'Склад', 'Дата', 'Показник (' . $resourceUnit . ')', 'Δ ресурсу', 'Норма', 'Поправка', 'Вх.сальдо', 'Надходження', 'Списання', 'Вих.сальдо'];
        
        foreach ($reportData as $material) {
            foreach ($material['warehouses'] as $warehouse) {
                foreach ($warehouse['rows'] as $row) {
                    $sheet2Rows[] = [
                        $material['material_name'],
                        $warehouse['warehouse_name'],
                        date('d.m.Y', strtotime($row['date'])),
                        $row['reading'],
                        $row['delta'],
                        $row['rate'],
                        $row['correction_pct'],
                        number_format($row['opening_balance'], 2),
                        $row['incoming'],
                        $row['consumed'],
                        number_format($row['closing_balance'], 2)
                    ];
                }
            }
        }
        
        // Генеруємо XLSX з двома листами
        return $this->generateXlsx($sheet1Rows, $sheet2Rows);
    }

    private function generateXlsx(array $sheet1Rows, array $sheet2Rows): string
    {
        // Лист 1
        $sheet1Xml = $this->buildSheetXml($sheet1Rows, 'Деталізація');
        
        // Лист 2
        $sheet2Xml = $this->buildSheetXml($sheet2Rows, 'Для зведеної');
        
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
                <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
                <Default Extension="xml" ContentType="application/xml"/>
                <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
                <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
                <Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
            </Types>';
        
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
                <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
            </Relationships>';
        
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
                <sheets>
                    <sheet name="Деталізація" sheetId="1" r:id="rId1"/>
                    <sheet name="Для зведеної" sheetId="2" r:id="rId2"/>
                </sheets>
            </workbook>';
        
        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
                <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
                <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
            </Relationships>';
        
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet1Xml);
        $zip->addFromString('xl/worksheets/sheet2.xml', $sheet2Xml);
        $zip->close();
        
        $content = file_get_contents($tmpFile);
        unlink($tmpFile);
        return $content;
    }
    
    private function buildSheetXml(array $rows, string $sheetName): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
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
        $xml .= '</worksheet>';
        
        return $xml;
    }
}