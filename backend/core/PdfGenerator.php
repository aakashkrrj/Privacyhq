<?php
// governance/backend/core/PdfGenerator.php
namespace Backend\Core;

class PdfGenerator
{
    private $objects = [];
    private $currentPage = 0;
    private $pageObjects = [];
    private $contentStream = '';
    private $fontObjBold = 0;
    private $fontObjRegular = 0;
    private $y = 750; // Top margin on 612x792 page

    public function __construct()
    {
        // Reserve obj 1 (Catalog) and obj 2 (Pages)
        $this->objects[1] = ''; 
        $this->objects[2] = '';

        // Add Font objects (Helvetica & Helvetica-Bold)
        $this->fontObjRegular = $this->addObject("<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>");
        $this->fontObjBold = $this->addObject("<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>");

        $this->addPage();
    }

    private function addObject($content)
    {
        $id = count($this->objects) + 1;
        $this->objects[$id] = $content;
        return $id;
    }

    public function addPage()
    {
        if ($this->contentStream !== '') {
            $this->flushContentStream();
        }
        $this->y = 750;
        $this->contentStream = '';
    }

    private function flushContentStream()
    {
        if ($this->contentStream === '') return;

        $streamLen = strlen($this->contentStream);
        $contentObjId = $this->addObject("<< /Length {$streamLen} >>\nstream\n" . $this->contentStream . "\nendstream");

        $pageObjId = $this->addObject("<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 {$this->fontObjRegular} 0 R /F2 {$this->fontObjBold} 0 R >> /ProcSet [/PDF /Text] >> /Contents {$contentObjId} 0 R >>");
        $this->pageObjects[] = $pageObjId;
    }

    private function escapePdfText($text)
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace('(', '\\(', $text);
        $text = str_replace(')', '\\)', $text);
        // Replace non-ASCII / latin1 characters for WinAnsi
        return preg_replace('/[^\x20-\x7E]/', '?', $text);
    }

    public function addHeader($title, $subtitle = '')
    {
        // Corporate Blue Bar Header
        $this->contentStream .= "0 0.372 0.666 rg\n"; // RGB #005FAA
        $this->contentStream .= "50 " . ($this->y - 5) . " 512 25 re f\n"; // Fill bar

        $this->contentStream .= "1 1 1 rg\n"; // White text
        $this->contentStream .= "BT /F2 14 Tf 58 " . ($this->y + 2) . " Td (" . $this->escapePdfText("PRIVACYHQ GOVERNANCE PLATFORM") . ") Tj ET\n";

        $this->y -= 45;

        // Title
        $this->contentStream .= "0.1 0.1 0.1 rg\n"; // Dark text
        $this->contentStream .= "BT /F2 18 Tf 50 {$this->y} Td (" . $this->escapePdfText($title) . ") Tj ET\n";
        $this->y -= 22;

        if ($subtitle) {
            $this->contentStream .= "0.3 0.3 0.3 rg\n";
            $this->contentStream .= "BT /F1 10 Tf 50 {$this->y} Td (" . $this->escapePdfText($subtitle) . ") Tj ET\n";
            $this->y -= 20;
        }

        // Horizontal Line separator
        $this->contentStream .= "0.8 0.8 0.8 RG 1 w 50 {$this->y} m 562 {$this->y} l S\n";
        $this->y -= 25;
    }

    public function addMetadataBlocks(array $meta)
    {
        $count = count($meta);
        if ($count === 0) return;

        $boxWidth = 512 / min(4, $count);
        $x = 50;

        foreach ($meta as $label => $val) {
            // Box background & border
            $this->contentStream .= "0.96 0.96 0.97 rg 50 " . ($this->y - 25) . " " . ($boxWidth - 10) . " 35 re f\n";
            $this->contentStream .= "0.85 0.85 0.88 RG 1 w {$x} " . ($this->y - 25) . " " . ($boxWidth - 10) . " 35 re s\n";

            // Label
            $this->contentStream .= "0.35 0.35 0.4 rg\n";
            $this->contentStream .= "BT /F2 8 Tf " . ($x + 8) . " " . ($this->y + 1) . " Td (" . $this->escapePdfText(strtoupper($label)) . ") Tj ET\n";

            // Value
            $this->contentStream .= "0 0.37 0.66 rg\n";
            $this->contentStream .= "BT /F2 11 Tf " . ($x + 8) . " " . ($this->y - 15) . " Td (" . $this->escapePdfText((string)$val) . ") Tj ET\n";

            $x += $boxWidth;
        }

        $this->y -= 45;
    }

    public function addTable(array $headers, array $rows, array $colWidths = [])
    {
        $numCols = count($headers);
        if ($numCols === 0) return;

        if (empty($colWidths)) {
            $defaultW = 512 / $numCols;
            for ($i = 0; $i < $numCols; $i++) $colWidths[$i] = $defaultW;
        }

        // Check space for table header
        if ($this->y < 100) {
            $this->addPage();
        }

        // Draw Table Header Row
        $this->contentStream .= "0.93 0.94 0.96 rg 50 " . ($this->y - 16) . " 512 20 re f\n";
        $this->contentStream .= "0.75 0.78 0.82 RG 1 w 50 " . ($this->y - 16) . " 512 20 re s\n";

        $x = 55;
        $colIdx = 0;
        foreach ($headers as $fieldKey => $hdrLabel) {
            $w = $colWidths[$colIdx] ?? (512 / $numCols);
            $this->contentStream .= "0.15 0.20 0.30 rg\n";
            $this->contentStream .= "BT /F2 9 Tf {$x} " . ($this->y - 11) . " Td (" . $this->escapePdfText(strtoupper($hdrLabel)) . ") Tj ET\n";
            $x += $w;
            $colIdx++;
        }

        $this->y -= 20;

        // Draw Table Body Rows
        foreach ($rows as $rIdx => $row) {
            if ($this->y < 60) {
                $this->addPage();
                // Redraw table header on new page
                $this->contentStream .= "0.93 0.94 0.96 rg 50 " . ($this->y - 16) . " 512 20 re f\n";
                $this->contentStream .= "0.75 0.78 0.82 RG 1 w 50 " . ($this->y - 16) . " 512 20 re s\n";
                $x = 55;
                $colIdx = 0;
                foreach ($headers as $fieldKey => $hdrLabel) {
                    $w = $colWidths[$colIdx] ?? (512 / $numCols);
                    $this->contentStream .= "0.15 0.20 0.30 rg\n";
                    $this->contentStream .= "BT /F2 9 Tf {$x} " . ($this->y - 11) . " Td (" . $this->escapePdfText(strtoupper($hdrLabel)) . ") Tj ET\n";
                    $x += $w;
                    $colIdx++;
                }
                $this->y -= 20;
            }

            // Zebra striping
            if ($rIdx % 2 === 1) {
                $this->contentStream .= "0.98 0.98 0.99 rg 50 " . ($this->y - 14) . " 512 18 re f\n";
            }
            $this->contentStream .= "0.88 0.88 0.90 RG 0.5 w 50 " . ($this->y - 14) . " 512 18 re s\n";

            $x = 55;
            $colIdx = 0;
            foreach ($headers as $fieldKey => $hdrLabel) {
                $w = $colWidths[$colIdx] ?? (512 / $numCols);
                $cellVal = (string)($row[$fieldKey] ?? $row[$hdrLabel] ?? $row[$colIdx] ?? '');
                if (strlen($cellVal) > 32) {
                    $cellVal = substr($cellVal, 0, 29) . '...';
                }

                $this->contentStream .= "0.1 0.1 0.1 rg\n";
                $this->contentStream .= "BT /F1 8 Tf {$x} " . ($this->y - 10) . " Td (" . $this->escapePdfText($cellVal) . ") Tj ET\n";
                $x += $w;
                $colIdx++;
            }

            $this->y -= 18;
        }

        $this->y -= 15;
    }

    public function output()
    {
        $this->flushContentStream();

        // Object 2: Pages catalog
        $kidsStr = implode(' 0 R ', $this->pageObjects) . ' 0 R';
        $pageCount = count($this->pageObjects);
        $this->objects[2] = "<< /Type /Pages /Count {$pageCount} /Kids [ {$kidsStr} ] >>";

        // Object 1: Catalog
        $this->objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";

        // Build PDF output
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        ksort($this->objects);
        foreach ($this->objects as $id => $content) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$content}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $numObjects = count($this->objects) + 1;
        $pdf .= "xref\n0 {$numObjects}\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i < $numObjects; $i++) {
            $off = sprintf("%010d", $offsets[$i]);
            $pdf .= "{$off} 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size {$numObjects} /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF\n";

        return $pdf;
    }
}
