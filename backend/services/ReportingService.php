<?php
namespace Backend\Services;

class ReportingService {
    /**
     * Export data array to CSV format
     */
    public function exportToCsv(array $data, array $headers, $filename = 'report.csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM for Excel compliance
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write rows
        foreach ($data as $row) {
            $formattedRow = [];
            foreach ($headers as $key => $label) {
                $formattedRow[] = $row[$key] ?? '';
            }
            fputcsv($output, $formattedRow);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export data array to basic HTML print format
     */
    public function exportToPrint(array $data, array $headers, $title = 'Report') {
        echo "<!DOCTYPE html><html><head><title>" . htmlspecialchars($title) . "</title>";
        echo "<style>body{font-family:sans-serif;padding:20px;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #ccc;padding:8px;text-align:left;} th{background:#f4f4f4;} @media print{button{display:none;}}</style>";
        echo "</head><body>";
        echo "<h2>" . htmlspecialchars($title) . "</h2>";
        echo "<button onclick='window.print()'>Print Report</button><br><br>";
        echo "<table><thead><tr>";
        foreach ($headers as $label) {
            echo "<th>" . htmlspecialchars($label) . "</th>";
        }
        echo "</tr></thead><tbody>";
        foreach ($data as $row) {
            echo "<tr>";
            foreach ($headers as $key => $label) {
                echo "<td>" . htmlspecialchars($row[$key] ?? '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</tbody></table></body></html>";
        exit;
    }
}
