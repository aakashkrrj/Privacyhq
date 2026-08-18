<?php
// governance/backend/core/XlsxGenerator.php
namespace Backend\Core;

class XlsxGenerator
{
    private $headers = [];
    private $rows = [];
    private $title = 'PrivacyHQ Report';

    public function __construct(array $headers = [], array $rows = [], string $title = 'PrivacyHQ Report')
    {
        $this->headers = $headers;
        $this->rows = $rows;
        $this->title = $title;
    }

    private function colLetter($colIndex)
    {
        $letter = '';
        while ($colIndex >= 0) {
            $letter = chr(($colIndex % 26) + 65) . $letter;
            $colIndex = intval($colIndex / 26) - 1;
        }
        return $letter;
    }

    private function xmlEscape($str)
    {
        return htmlspecialchars((string)$str, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    public function output()
    {
        // Collect shared strings
        $sharedStrings = [];
        $stringMap = [];

        $addSharedString = function($val) use (&$sharedStrings, &$stringMap) {
            $str = (string)$val;
            if (!isset($stringMap[$str])) {
                $stringMap[$str] = count($sharedStrings);
                $sharedStrings[] = $str;
            }
            return $stringMap[$str];
        };

        // All Data Rows
        $allDataRows = [];
        $allDataRows[] = [$this->title];
        $allDataRows[] = ['Export Date: ' . date('Y-m-d H:i:s'), 'Total Records: ' . count($this->rows)];
        $allDataRows[] = []; // spacer
        $allDataRows[] = array_values($this->headers);

        foreach ($this->rows as $r) {
            $rowVals = [];
            foreach ($this->headers as $key => $label) {
                $val = $r[$key] ?? $r[$label] ?? '';
                if (is_array($val)) $val = json_encode($val);
                $rowVals[] = $val;
            }
            $allDataRows[] = $rowVals;
        }

        // Build Sheet1 XML
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n";
        $sheetXml .= '<sheetData>' . "\n";

        foreach ($allDataRows as $rIdx => $row) {
            $rowNum = $rIdx + 1;
            $isHeader = ($rIdx === 3);
            $styleAttr = $isHeader ? ' s="1"' : '';

            $sheetXml .= "<row r=\"{$rowNum}\">" . "\n";
            foreach ($row as $cIdx => $val) {
                $colRef = $this->colLetter($cIdx) . $rowNum;
                if (is_numeric($val) && !preg_match('/^0\d+/', $val)) {
                    $sheetXml .= "<c r=\"{$colRef}\"{$styleAttr}><v>{$val}</v></c>";
                } else {
                    $sIdx = $addSharedString($val);
                    $sheetXml .= "<c r=\"{$colRef}\" t=\"s\"{$styleAttr}><v>{$sIdx}</v></c>";
                }
            }
            $sheetXml .= "</row>\n";
        }

        $sheetXml .= '</sheetData></worksheet>';

        // Build SharedStrings XML
        $ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $ssXml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($sharedStrings) . '" uniqueCount="' . count($sharedStrings) . '">' . "\n";
        foreach ($sharedStrings as $str) {
            $ssXml .= '<si><t xml:space="preserve">' . $this->xmlEscape($str) . '</t></si>';
        }
        $ssXml .= '</sst>';

        // Build Styles XML
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $stylesXml .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";
        $stylesXml .= '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FF005FAA"/><name val="Calibri"/></font></fonts>' . "\n";
        $stylesXml .= '<fills count="2"><fill><patternFill fillType="none"/></fill><fill><patternFill fillType="gray125"/></fill></fills>' . "\n";
        $stylesXml .= '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>' . "\n";
        $stylesXml .= '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>' . "\n";
        $stylesXml .= '</styleSheet>';

        // Build Content Types XML
        $ctXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $ctXml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' . "\n";
        $ctXml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' . "\n";
        $ctXml .= '<Default Extension="xml" ContentType="application/xml"/>' . "\n";
        $ctXml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' . "\n";
        $ctXml .= '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' . "\n";
        $ctXml .= '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>' . "\n";
        $ctXml .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . "\n";
        $ctXml .= '</Types>';

        // Build Rels XML
        $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $relsXml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";
        $relsXml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' . "\n";
        $relsXml .= '</Relationships>';

        // Build Workbook XML
        $wbXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $wbXml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n";
        $wbXml .= '<sheets><sheet name="Report Data" sheetId="1" r:id="rId1"/></sheets>' . "\n";
        $wbXml .= '</workbook>';

        // Build Workbook Rels XML
        $wbRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $wbRelsXml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";
        $wbRelsXml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' . "\n";
        $wbRelsXml .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>' . "\n";
        $wbRelsXml .= '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' . "\n";
        $wbRelsXml .= '</Relationships>';

        // Package into ZIP using pure PHP Zip writer
        $files = [
            '[Content_Types].xml' => $ctXml,
            '_rels/.rels' => $relsXml,
            'xl/workbook.xml' => $wbXml,
            'xl/_rels/workbook.xml.rels' => $wbRelsXml,
            'xl/styles.xml' => $stylesXml,
            'xl/sharedStrings.xml' => $ssXml,
            'xl/worksheets/sheet1.xml' => $sheetXml
        ];

        return $this->buildPureZip($files);
    }

    /**
     * Pure PHP ZIP file builder using deflate compression (zlib)
     */
    private function buildPureZip(array $files)
    {
        $zipData = '';
        $cdData = '';
        $offset = 0;

        $dosTime = pack('v', 0x7000); // 14:00
        $dosDate = pack('v', 0x5d00); // Aug 18 2026

        foreach ($files as $name => $content) {
            $uncompressedSize = strlen($content);
            $crc = crc32($content);

            // Compress using gzdeflate
            $compressedContent = gzdeflate($content);
            $compressedSize = strlen($compressedContent);
            $method = 8; // Deflate

            // Local file header
            $localHeader = pack('V', 0x04034b50) // signature
                . pack('v', 20)                 // version needed (2.0)
                . pack('v', 0)                  // bit flag
                . pack('v', $method)            // compression method
                . $dosTime . $dosDate
                . pack('V', $crc)
                . pack('V', $compressedSize)
                . pack('V', $uncompressedSize)
                . pack('v', strlen($name))
                . pack('v', 0)                  // extra field length
                . $name;

            $zipData .= $localHeader . $compressedContent;

            // Central directory header
            $cdHeader = pack('V', 0x02014b50)   // signature
                . pack('v', 20)                 // version made by
                . pack('v', 20)                 // version needed
                . pack('v', 0)                  // bit flag
                . pack('v', $method)            // compression method
                . $dosTime . $dosDate
                . pack('V', $crc)
                . pack('V', $compressedSize)
                . pack('V', $uncompressedSize)
                . pack('v', strlen($name))
                . pack('v', 0)                  // extra field length
                . pack('v', 0)                  // file comment length
                . pack('v', 0)                  // disk number start
                . pack('v', 0)                  // internal file attributes
                . pack('V', 32)                 // external file attributes (archive)
                . pack('V', $offset)            // relative offset
                . $name;

            $cdData .= $cdHeader;
            $offset = strlen($zipData);
        }

        // End of central directory record
        $eocd = pack('V', 0x06054b50)           // signature
            . pack('v', 0)                      // disk num
            . pack('v', 0)                      // start disk
            . pack('v', count($files))          // entries on disk
            . pack('v', count($files))          // total entries
            . pack('V', strlen($cdData))        // central dir size
            . pack('V', strlen($zipData))       // central dir offset
            . pack('v', 0);                     // comment len

        return $zipData . $cdData . $eocd;
    }
}
