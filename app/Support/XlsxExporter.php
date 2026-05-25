<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

class XlsxExporter
{
    /**
     * @param  array<int, array{name:string, rows:array<int, array<int, mixed>>}>  $sheets
     */
    public function build(array $sheets): string
    {
        if ($sheets === []) {
            throw new RuntimeException('El archivo Excel necesita al menos una hoja.');
        }

        $path = tempnam(sys_get_temp_dir(), 'xlsx_');

        if ($path === false) {
            throw new RuntimeException('No se pudo preparar el archivo Excel.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            @unlink($path);

            throw new RuntimeException('No se pudo crear el archivo Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml(count($sheets)));
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml(count($sheets)));

        foreach ($sheets as $index => $sheet) {
            $zip->addFromString(
                'xl/worksheets/sheet'.($index + 1).'.xml',
                $this->worksheetXml($sheet['rows']),
            );
        }

        $zip->close();

        $contents = file_get_contents($path);
        @unlink($path);

        if ($contents === false) {
            throw new RuntimeException('No se pudo leer el archivo Excel generado.');
        }

        return $contents;
    }

    private function contentTypesXml(int $sheetCount): string
    {
        $worksheetOverrides = collect(range(1, $sheetCount))
            ->map(fn (int $index): string => '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>')
            ->implode('');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .$worksheetOverrides
            .'</Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    /**
     * @param  array<int, array{name:string, rows:array<int, array<int, mixed>>}>  $sheets
     */
    private function workbookXml(array $sheets): string
    {
        $sheetTags = collect($sheets)
            ->map(fn (array $sheet, int $index): string => sprintf(
                '<sheet name="%s" sheetId="%d" r:id="rId%d"/>',
                $this->escape($this->sheetName($sheet['name'], $index)),
                $index + 1,
                $index + 1,
            ))
            ->implode('');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$sheetTags.'</sheets>'
            .'</workbook>';
    }

    private function workbookRelationshipsXml(int $sheetCount): string
    {
        $relationships = collect(range(1, $sheetCount))
            ->map(fn (int $index): string => '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>')
            ->implode('');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$relationships
            .'</Relationships>';
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function worksheetXml(array $rows): string
    {
        $rowTags = collect($rows)
            ->values()
            ->map(function (array $row, int $rowIndex): string {
                $rowNumber = $rowIndex + 1;
                $cells = collect($row)
                    ->values()
                    ->map(fn (mixed $value, int $columnIndex): string => $this->cellXml($value, $columnIndex, $rowNumber))
                    ->implode('');

                return '<row r="'.$rowNumber.'">'.$cells.'</row>';
            })
            ->implode('');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.$rowTags.'</sheetData>'
            .'</worksheet>';
    }

    private function cellXml(mixed $value, int $columnIndex, int $rowNumber): string
    {
        $reference = $this->columnName($columnIndex + 1).$rowNumber;

        if ($value === null || $value === '') {
            return '<c r="'.$reference.'"/>';
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="'.$reference.'"><v>'.$value.'</v></c>';
        }

        if (is_bool($value)) {
            return '<c r="'.$reference.'" t="b"><v="'.($value ? '1' : '0').'"/></c>';
        }

        return '<c r="'.$reference.'" t="inlineStr"><is><t>'.$this->escape((string) $value).'</t></is></c>';
    }

    private function columnName(int $columnNumber): string
    {
        $name = '';

        while ($columnNumber > 0) {
            $columnNumber--;
            $name = chr(65 + ($columnNumber % 26)).$name;
            $columnNumber = intdiv($columnNumber, 26);
        }

        return $name;
    }

    private function sheetName(string $name, int $index): string
    {
        $name = preg_replace('/[\[\]\*\/\\\\\?:]/', ' ', trim($name)) ?: 'Hoja '.($index + 1);

        return mb_substr($name, 0, 31);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
