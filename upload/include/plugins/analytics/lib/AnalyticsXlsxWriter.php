<?php

namespace Analytics;

/**
 * Минимальный writer XLSX (Office Open XML, .xlsx).
 *
 * XLSX — это ZIP-архив с фиксированным набором XML-файлов. Здесь собирается
 * самый узкий минимум, которого хватает Excel и LibreOffice для открытия
 * одной таблицы: [Content_Types], корневые rels, workbook + его rels,
 * styles, sharedStrings и один worksheet.
 *
 * Не претендует на роль полноценной библиотеки (формулы, стили ячеек, числовые
 * форматы вне базового — не поддерживаются). Достаточно для экспорта
 * аналитики; если потребуется больше — заменим на PhpSpreadsheet целиком.
 *
 * Использование:
 *   $rows = [
 *       ['Дата', 'Всего', 'Закрыто'],   // строка-заголовок
 *       ['2026-05-01', 12, 9],
 *       ['2026-05-02', 15, 11],
 *   ];
 *   $bytes = XlsxWriter::build('Аналитика', $rows);
 */
class XlsxWriter {
    /**
     * Возвращает бинарное содержимое xlsx-файла.
     *
     * @param string $sheetName  Название листа (≤ 31 символа — ограничение Excel)
     * @param array  $rows       Двумерный массив, каждая внутренняя — строка
     */
    public static function build(string $sheetName, array $rows): string {
        $sheetName = self::sanitizeSheetName($sheetName);

        // sharedStrings: индексация всех строковых значений (Excel хочет inlineStr
        // или sharedStrings; sharedStrings даёт более компактные файлы).
        $strings = [];
        $stringIndex = [];
        $rowXml = '';
        $rowNum = 0;
        foreach ($rows as $row) {
            $rowNum++;
            $cellsXml = '';
            $colIndex = 0;
            foreach ($row as $value) {
                $colIndex++;
                $ref = self::colLetter($colIndex) . $rowNum;
                if ($value === null || $value === '') {
                    continue; // пустая ячейка
                }
                if (is_numeric($value) && !is_string($value)) {
                    $cellsXml .= '<c r="' . $ref . '"><v>' . $value . '</v></c>';
                } else {
                    $s = (string) $value;
                    if (!isset($stringIndex[$s])) {
                        $stringIndex[$s] = count($strings);
                        $strings[] = $s;
                    }
                    $idx = $stringIndex[$s];
                    $cellsXml .= '<c r="' . $ref . '" t="s"><v>' . $idx . '</v></c>';
                }
            }
            $rowXml .= '<row r="' . $rowNum . '">' . $cellsXml . '</row>';
        }

        // Worksheet
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<sheetData>' . $rowXml . '</sheetData>' .
            '</worksheet>';

        // SharedStrings
        $ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' .
            count($strings) . '" uniqueCount="' . count($strings) . '">';
        foreach ($strings as $s) {
            $ssXml .= '<si><t xml:space="preserve">' . self::xmlEscape($s) . '</t></si>';
        }
        $ssXml .= '</sst>';

        // Workbook
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"' .
            ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets><sheet name="' . self::xmlEscape($sheetName) . '" sheetId="1" r:id="rId1"/></sheets>' .
            '</workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
            '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>' .
            '</Relationships>';

        // Корневые rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>';

        // Content_Types
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
            '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>' .
            '</Types>';

        // Styles — пустой, но обязательный
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>' .
            '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>' .
            '<borders count="1"><border/></borders>' .
            '<cellStyleXfs count="1"><xf/></cellStyleXfs>' .
            '<cellXfs count="1"><xf/></cellXfs>' .
            '</styleSheet>';

        // Собираем zip во временный файл, читаем обратно — потоковый Zip Archive
        // в PHP не позволяет писать в php://memory.
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rootRels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->addFromString('xl/styles.xml', $styles);
        $zip->addFromString('xl/sharedStrings.xml', $ssXml);
        $zip->close();
        $bytes = file_get_contents($tmp);
        @unlink($tmp);
        return $bytes;
    }

    private static function colLetter(int $n): string {
        $letter = '';
        while ($n > 0) {
            $mod = ($n - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $n = (int) (($n - $mod) / 26);
        }
        return $letter;
    }

    private static function sanitizeSheetName(string $name): string {
        $name = preg_replace('/[\\\\\\/\\?\\*\\[\\]]/u', '_', $name);
        return mb_substr($name, 0, 31, 'UTF-8');
    }

    private static function xmlEscape(string $s): string {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
