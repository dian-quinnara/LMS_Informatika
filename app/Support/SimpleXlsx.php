<?php

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;

/**
 * Pembaca/penulis XLSX minimal tanpa dependensi (tidak butuh ext-zip).
 *
 * Cukup untuk tabel sederhana: membaca sheet pertama sebagai array baris string
 * dan menulis workbook berisi teks. Hanya memakai zlib (gzinflate) dan SimpleXML.
 */
class SimpleXlsx
{
    private const MAX_ENTRY_BYTES = 20 * 1024 * 1024;

    private const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const NS_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /**
     * Baca sheet pertama menjadi daftar baris (setiap baris = array string, indeks kolom 0..n).
     *
     * @return list<list<string>>
     */
    public static function readFirstSheet(string $path): array
    {
        $entries = self::readZipEntries((string) file_get_contents($path));
        $sheetPath = self::firstSheetPath($entries);

        if (! isset($entries[$sheetPath])) {
            throw new RuntimeException('Lembar kerja tidak ditemukan di dalam file Excel.');
        }

        $sharedStrings = isset($entries['xl/sharedStrings.xml'])
            ? self::parseSharedStrings($entries['xl/sharedStrings.xml'])
            : [];

        $sheet = self::loadXml($entries[$sheetPath]);
        $sheet->registerXPathNamespace('m', self::NS_MAIN);
        $rows = [];

        foreach ($sheet->xpath('//m:sheetData/m:row') ?: [] as $rowNode) {
            $row = [];
            $nextColumn = 0;

            foreach ($rowNode->children(self::NS_MAIN)->c as $cell) {
                $reference = (string) $cell->attributes()->r;
                $column = $reference !== '' ? self::columnIndex($reference) : $nextColumn;
                $row[$column] = self::cellValue($cell, $sharedStrings);
                $nextColumn = $column + 1;
            }

            if ($row === []) {
                $rows[] = [];

                continue;
            }

            $filled = array_fill(0, max(array_keys($row)) + 1, '');
            $rows[] = array_values(array_replace($filled, $row));
        }

        return $rows;
    }

    /**
     * Buat file XLSX dari beberapa sheet.
     *
     * @param  array<string, array{rows: list<list<string>>, widths?: list<int>, text_columns?: list<int>, bold_first_row?: bool}>  $sheets
     */
    public static function build(array $sheets): string
    {
        $files = [];
        $sheetEntries = '';
        $relEntries = '';
        $overrides = '';
        $index = 1;

        foreach ($sheets as $name => $sheet) {
            $files["xl/worksheets/sheet{$index}.xml"] = self::sheetXml($sheet);
            $sheetEntries .= '<sheet name="'.self::xml($name).'" sheetId="'.$index.'" r:id="rId'.$index.'"/>';
            $relEntries .= '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>';
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $index++;
        }

        $relEntries .= '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                .$overrides.'</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<workbook xmlns="'.self::NS_MAIN.'" xmlns:r="'.self::NS_REL.'"><sheets>'.$sheetEntries.'</sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$relEntries.'</Relationships>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<styleSheet xmlns="'.self::NS_MAIN.'">'
                .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
                .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
                .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
                .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
                .'<cellXfs count="3">'
                .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
                .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
                .'<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
                .'</cellXfs></styleSheet>',
        ] + $files;

        return self::zip($files);
    }

    /**
     * @param  array{rows: list<list<string>>, widths?: list<int>, text_columns?: list<int>, bold_first_row?: bool}  $sheet
     */
    private static function sheetXml(array $sheet): string
    {
        $cols = '';
        foreach ($sheet['widths'] ?? [] as $i => $width) {
            $cols .= '<col min="'.($i + 1).'" max="'.($i + 1).'" width="'.$width.'" customWidth="1"'
                .(in_array($i, $sheet['text_columns'] ?? [], true) ? ' style="2"' : '').'/>';
        }

        $rowsXml = '';
        foreach ($sheet['rows'] as $r => $row) {
            $rowsXml .= '<row r="'.($r + 1).'">';
            foreach ($row as $c => $value) {
                $style = $r === 0 && ($sheet['bold_first_row'] ?? false) ? 1 : (in_array($c, $sheet['text_columns'] ?? [], true) ? 2 : 0);
                $rowsXml .= '<c r="'.self::columnName($c).($r + 1).'" t="inlineStr" s="'.$style.'"><is><t xml:space="preserve">'.self::xml($value).'</t></is></c>';
            }
            $rowsXml .= '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="'.self::NS_MAIN.'">'
            .($cols !== '' ? '<cols>'.$cols.'</cols>' : '')
            .'<sheetData>'.$rowsXml.'</sheetData></worksheet>';
    }

    /**
     * @param  array<string, string>  $files
     */
    private static function zip(array $files): string
    {
        $data = '';
        $central = '';
        $time = 0x0000;
        $date = 0x5921; // 2024-09-01, tanggal tetap agar keluaran deterministik.

        foreach ($files as $name => $content) {
            $crc = crc32($content);
            $compressed = gzdeflate($content, 6);
            $size = strlen($content);
            $compressedSize = strlen($compressed);
            $offset = strlen($data);

            $data .= pack('VvvvvvVVVvv', 0x04034B50, 20, 0x0800, 8, $time, $date, $crc, $compressedSize, $size, strlen($name), 0)
                .$name.$compressed;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014B50, 20, 20, 0x0800, 8, $time, $date, $crc, $compressedSize, $size, strlen($name), 0, 0, 0, 0, 0, $offset)
                .$name;
        }

        return $data.$central.pack('VvvvvVVv', 0x06054B50, 0, 0, count($files), count($files), strlen($central), strlen($data), 0);
    }

    /**
     * @return array<string, string>
     */
    private static function readZipEntries(string $bytes): array
    {
        $length = strlen($bytes);
        $tailStart = max(0, $length - 65557);
        $found = strrpos(substr($bytes, $tailStart), "PK\x05\x06");
        $eocd = $found === false ? false : $tailStart + $found;

        if ($length < 22 || $eocd === false) {
            throw new RuntimeException('File bukan Excel (.xlsx) yang valid.');
        }

        $end = unpack('Vsig/vdisk/vcdDisk/ventriesDisk/ventries/VcdSize/VcdOffset', substr($bytes, $eocd, 20));
        $position = $end['cdOffset'];
        $entries = [];

        for ($i = 0; $i < $end['entries']; $i++) {
            if (substr($bytes, $position, 4) !== "PK\x01\x02") {
                throw new RuntimeException('Struktur file Excel rusak.');
            }

            $header = unpack('vmethod/vtime/vdate/Vcrc/VcompressedSize/Vsize/vnameLength/vextraLength/vcommentLength/vdisk/vinternal/Vexternal/Voffset', substr($bytes, $position + 10, 36));
            $name = substr($bytes, $position + 46, $header['nameLength']);
            $position += 46 + $header['nameLength'] + $header['extraLength'] + $header['commentLength'];

            if (! str_ends_with($name, '.xml') && ! str_ends_with($name, '.rels')) {
                continue;
            }

            if ($header['size'] > self::MAX_ENTRY_BYTES) {
                throw new RuntimeException('Isi file Excel terlalu besar.');
            }

            $local = unpack('vnameLength/vextraLength', substr($bytes, $header['offset'] + 26, 4));
            $raw = substr($bytes, $header['offset'] + 30 + $local['nameLength'] + $local['extraLength'], $header['compressedSize']);

            $content = match ($header['method']) {
                0 => $raw,
                8 => @gzinflate($raw, self::MAX_ENTRY_BYTES),
                default => throw new RuntimeException('Metode kompresi file Excel tidak didukung.'),
            };

            if ($content === false) {
                throw new RuntimeException('File Excel tidak dapat dibaca.');
            }

            $entries[$name] = $content;
        }

        return $entries;
    }

    /**
     * @param  array<string, string>  $entries
     */
    private static function firstSheetPath(array $entries): string
    {
        $fallback = 'xl/worksheets/sheet1.xml';

        if (! isset($entries['xl/workbook.xml'], $entries['xl/_rels/workbook.xml.rels'])) {
            return $fallback;
        }

        $workbook = self::loadXml($entries['xl/workbook.xml']);
        $firstSheet = $workbook->children(self::NS_MAIN)->sheets->sheet[0] ?? null;
        // Elemen SimpleXML kosong bernilai false, jadi bandingkan dengan null secara eksplisit.
        $relationId = $firstSheet !== null ? (string) $firstSheet->attributes(self::NS_REL)->id : '';

        $rels = self::loadXml($entries['xl/_rels/workbook.xml.rels']);
        foreach ($rels->children() as $rel) {
            if ((string) $rel['Id'] === $relationId) {
                $target = ltrim((string) $rel['Target'], '/');

                return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
            }
        }

        return $fallback;
    }

    /**
     * @return list<string>
     */
    private static function parseSharedStrings(string $xml): array
    {
        $document = self::loadXml($xml);
        $strings = [];

        foreach ($document->children(self::NS_MAIN)->si as $item) {
            $item->registerXPathNamespace('m', self::NS_MAIN);
            $strings[] = implode('', array_map('strval', $item->xpath('.//m:t') ?: []));
        }

        return $strings;
    }

    /**
     * @param  list<string>  $sharedStrings
     */
    private static function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell->attributes()->t;
        $children = $cell->children(self::NS_MAIN);

        if ($type === 'inlineStr') {
            $cell->registerXPathNamespace('m', self::NS_MAIN);

            return implode('', array_map('strval', $cell->xpath('.//m:is//m:t') ?: []));
        }

        $value = (string) $children->v;

        return match ($type) {
            's' => $sharedStrings[(int) $value] ?? '',
            'b' => $value === '1' ? 'TRUE' : 'FALSE',
            'str', 'e' => $value,
            default => self::normalizeNumber($value),
        };
    }

    /**
     * Angka bulat (mis. NIS yang tersimpan sebagai angka) dikembalikan tanpa notasi ilmiah.
     */
    private static function normalizeNumber(string $value): string
    {
        if ($value === '' || ! is_numeric($value)) {
            return $value;
        }

        $number = (float) $value;

        if (floor($number) === $number && abs($number) < 1e15) {
            return number_format($number, 0, '', '');
        }

        return $value;
    }

    private static function loadXml(string $xml): SimpleXMLElement
    {
        $document = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);

        if ($document === false) {
            throw new RuntimeException('Isi file Excel tidak valid.');
        }

        return $document;
    }

    private static function columnIndex(string $reference): int
    {
        $letters = strtoupper((string) preg_replace('/[^A-Za-z]/', '', $reference));
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }

    private static function columnName(int $index): string
    {
        $name = '';
        for ($index++; $index > 0; $index = intdiv($index - 1, 26)) {
            $name = chr(65 + ($index - 1) % 26).$name;
        }

        return $name;
    }

    private static function xml(string $value): string
    {
        $clean = (string) preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value);

        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
