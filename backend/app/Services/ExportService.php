<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Export Service
 * 
 * Handles export functionality for multiple formats including:
 * - PDF reports with charts and formatting
 * - Excel spreadsheets with multiple sheets and styling
 * - CSV files for data analysis
 */
class ExportService
{
    private string $tempPath;

    public function __construct()
    {
        $this->tempPath = storage_path('app/temp/exports');
        
        // Ensure temp directory exists
        if (!file_exists($this->tempPath)) {
            mkdir($this->tempPath, 0755, true);
        }
    }

    /**
     * Export analytics report in specified format
     */
    public function exportAnalyticsReport(
        string $reportType,
        string $format,
        array $data,
        array $metadata = []
    ): array {
        $filename = $this->generateFilename($reportType, $format, $metadata);
        $filePath = $this->tempPath . '/' . $filename;

        switch ($format) {
            case 'pdf':
                return $this->exportToPDF($reportType, $data, $metadata, $filePath);
            case 'excel':
                return $this->exportToExcel($reportType, $data, $metadata, $filePath);
            case 'csv':
                return $this->exportToCSV($reportType, $data, $metadata, $filePath);
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    /**
     * Export to PDF format (simplified version without external dependencies)
     */
    private function exportToPDF(string $reportType, array $data, array $metadata, string $filePath): array
    {
        try {
            $html = $this->generatePDFHTML($reportType, $data, $metadata);
            
            // For now, save as HTML file since we don't have PDF library
            // In production, this would use a proper PDF library
            $htmlFilePath = str_replace('.pdf', '.html', $filePath);
            file_put_contents($htmlFilePath, $html);
            
            return [
                'file_path' => $htmlFilePath,
                'filename' => basename($htmlFilePath),
                'file_size' => filesize($htmlFilePath),
                'headers' => [
                    'Content-Type' => 'text/html',
                    'Content-Disposition' => 'attachment; filename="' . basename($htmlFilePath) . '"'
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('PDF export failed', [
                'error' => $e->getMessage(),
                'report_type' => $reportType,
                'file_path' => $filePath
            ]);
            throw $e;
        }
    }

    /**
     * Export to Excel format (simplified CSV version)
     */
    private function exportToExcel(string $reportType, array $data, array $metadata, string $filePath): array
    {
        try {
            // For now, export as CSV since we don't have Excel library
            // In production, this would use PhpSpreadsheet
            $csvFilePath = str_replace('.xlsx', '.csv', $filePath);
            return $this->exportToCSV($reportType, $data, $metadata, $csvFilePath);
            
        } catch (\Exception $e) {
            Log::error('Excel export failed', [
                'error' => $e->getMessage(),
                'report_type' => $reportType,
                'file_path' => $filePath
            ]);
            throw $e;
        }
    }

    /**
     * Export to CSV format
     */
    private function exportToCSV(string $reportType, array $data, array $metadata, string $filePath): array
    {
        try {
            $csvData = $this->prepareCSVData($reportType, $data, $metadata);
            
            $handle = fopen($filePath, 'w');
            
            // Add BOM for UTF-8
            fwrite($handle, "\xEF\xBB\xBF");
            
            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }
            
            fclose($handle);
            
            return [
                'file_path' => $filePath,
                'filename' => basename($filePath),
                'file_size' => filesize($filePath),
                'headers' => [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="' . basename($filePath) . '"'
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('CSV export failed', [
                'error' => $e->getMessage(),
                'report_type' => $reportType,
                'file_path' => $filePath
            ]);
            throw $e;
        }
    }

    /**
     * Generate filename for export
     */
    private function generateFilename(string $reportType, string $format, array $metadata): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $period = isset($metadata['period']) ? "_{$metadata['period']}" : '';
        
        return "analytics_{$reportType}_report{$period}_{$timestamp}.{$format}";
    }

    /**
     * Generate HTML for PDF export
     */
    private function generatePDFHTML(string $reportType, array $data, array $metadata): string
    {
        $title = $this->getReportTitle($reportType);
        $generatedAt = $metadata['generated_at'] ?? now();
        $generatedBy = $metadata['generated_by'] ?? 'System';
        $period = $this->formatPeriod($metadata);
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>{$title}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #007bff; padding-bottom: 20px; }
                .title { font-size: 24px; font-weight: bold; color: #007bff; margin-bottom: 10px; }
                .subtitle { font-size: 14px; color: #666; }
                .section { margin: 20px 0; }
                .section-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 15px; border-left: 4px solid #007bff; padding-left: 10px; }
                .metric-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 15px 0; }
                .metric-card { border: 1px solid #ddd; padding: 15px; border-radius: 5px; background: #f8f9fa; }
                .metric-label { font-size: 12px; color: #666; text-transform: uppercase; }
                .metric-value { font-size: 20px; font-weight: bold; color: #007bff; }
                .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .table th { background-color: #f8f9fa; font-weight: bold; }
                .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
                .positive { color: #28a745; }
                .negative { color: #dc3545; }
                .neutral { color: #6c757d; }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='title'>{$title}</div>
                <div class='subtitle'>Period: {$period}</div>
                <div class='subtitle'>Generated: {$generatedAt->format('F j, Y \a\t g:i A')} by {$generatedBy}</div>
            </div>
        ";

        // Add content based on report type
        $html .= $this->generatePDFContent($reportType, $data);

        $html .= "
            <div class='footer'>
                <p>This report was automatically generated by the ShipWithGlowie Analytics System.</p>
                <p>For questions or support, please contact the system administrator.</p>
            </div>
        </body>
        </html>
        ";

        return $html;
    }

    /**
     * Generate PDF content based on report type
     */
    private function generatePDFContent(string $reportType, array $data): string
    {
        return match ($reportType) {
            'revenue' => $this->generateRevenuePDFContent($data),
            'bookings' => $this->generateBookingsPDFContent($data),
            'customers' => $this->generateCustomersPDFContent($data),
            'operational' => $this->generateOperationalPDFContent($data),
            'comprehensive' => $this->generateComprehensivePDFContent($data),
            default => '<div class="section"><p>Report content not available.</p></div>'
        };
    }

    /**
     * Generate revenue PDF content
     */
    private function generateRevenuePDFContent(array $data): string
    {
        $analytics = $data['analytics'] ?? [];
        $financial = $data['financial_summary'] ?? [];
        
        $html = '<div class="section">';
        $html .= '<div class="section-title">Revenue Overview</div>';
        
        if (!empty($analytics)) {
            $html .= '<div class="metric-grid">';
            $html .= '<div class="metric-card">';
            $html .= '<div class="metric-label">Total Revenue</div>';
            $html .= '<div class="metric-value">$' . number_format($analytics['total_revenue'] ?? 0, 2) . '</div>';
            $html .= '</div>';
            $html .= '<div class="metric-card">';
            $html .= '<div class="metric-label">Total Transactions</div>';
            $html .= '<div class="metric-value">' . number_format($analytics['total_transactions'] ?? 0) . '</div>';
            $html .= '</div>';
            $html .= '<div class="metric-card">';
            $html .= '<div class="metric-label">Average Transaction</div>';
            $html .= '<div class="metric-value">$' . number_format($analytics['average_transaction'] ?? 0, 2) . '</div>';
            $html .= '</div>';
            $html .= '<div class="metric-card">';
            $html .= '<div class="metric-label">Growth Rate</div>';
            $html .= '<div class="metric-value">' . number_format($analytics['growth_rate'] ?? 0, 1) . '%</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        // Add revenue by method table if available
        if (!empty($analytics['revenue_by_method'])) {
            $html .= '<div class="section-title">Revenue by Payment Method</div>';
            $html .= '<table class="table">';
            $html .= '<thead><tr><th>Payment Method</th><th>Revenue</th><th>Transactions</th><th>Avg Amount</th></tr></thead>';
            $html .= '<tbody>';
            
            foreach ($analytics['revenue_by_method'] as $method) {
                $html .= '<tr>';
                $html .= '<td>' . ucfirst(str_replace('_', ' ', $method['payment_method'] ?? '')) . '</td>';
                $html .= '<td>$' . number_format($method['revenue'] ?? 0, 2) . '</td>';
                $html .= '<td>' . number_format($method['transactions'] ?? 0) . '</td>';
                $html .= '<td>$' . number_format($method['avg_amount'] ?? 0, 2) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    // Mock implementations for Excel methods (would be implemented with PhpSpreadsheet in production)
    private function createExcelWorksheets($spreadsheet, string $reportType, array $data, array $metadata): void { /* Would use PhpSpreadsheet */ }
    private function createRevenueWorksheets($spreadsheet, array $data, array $metadata): void { /* Would use PhpSpreadsheet */ }
    private function addWorksheetHeader(object $worksheet, string $title, array $metadata): void { /* Would use PhpSpreadsheet */ }
    private function styleHeaderRow(object $worksheet, int $row, int $columns): void { /* Would use PhpSpreadsheet */ }

    /**
     * Prepare CSV data based on report type
     */
    private function prepareCSVData(string $reportType, array $data, array $metadata): array
    {
        $csvData = [];
        
        // Add header information
        $csvData[] = [$this->getReportTitle($reportType)];
        $csvData[] = ['Period: ' . $this->formatPeriod($metadata)];
        $csvData[] = ['Generated: ' . ($metadata['generated_at'] ?? now())->format('Y-m-d H:i:s')];
        $csvData[] = ['Generated by: ' . ($metadata['generated_by'] ?? 'System')];
        $csvData[] = []; // Empty row
        
        // Add data based on report type
        switch ($reportType) {
            case 'revenue':
                $csvData = array_merge($csvData, $this->prepareRevenueCSVData($data));
                break;
            case 'bookings':
                $csvData = array_merge($csvData, $this->prepareBookingsCSVData($data));
                break;
            case 'customers':
                $csvData = array_merge($csvData, $this->prepareCustomersCSVData($data));
                break;
            case 'operational':
                $csvData = array_merge($csvData, $this->prepareOperationalCSVData($data));
                break;
            case 'comprehensive':
                $csvData = array_merge($csvData, $this->prepareComprehensiveCSVData($data));
                break;
        }
        
        return $csvData;
    }

    /**
     * Prepare revenue CSV data
     */
    private function prepareRevenueCSVData(array $data): array
    {
        $csvData = [];
        $analytics = $data['analytics'] ?? [];
        
        // Summary section
        $csvData[] = ['Revenue Summary'];
        $csvData[] = ['Metric', 'Value'];
        $csvData[] = ['Total Revenue', '$' . number_format($analytics['total_revenue'] ?? 0, 2)];
        $csvData[] = ['Total Transactions', number_format($analytics['total_transactions'] ?? 0)];
        $csvData[] = ['Average Transaction', '$' . number_format($analytics['average_transaction'] ?? 0, 2)];
        $csvData[] = ['Growth Rate', number_format($analytics['growth_rate'] ?? 0, 1) . '%'];
        $csvData[] = []; // Empty row
        
        // Daily revenue if available
        if (!empty($analytics['daily_revenue'])) {
            $csvData[] = ['Daily Revenue Breakdown'];
            $csvData[] = ['Date', 'Revenue', 'Transactions'];
            
            foreach ($analytics['daily_revenue'] as $daily) {
                $csvData[] = [
                    $daily['date'] ?? '',
                    '$' . number_format($daily['revenue'] ?? 0, 2),
                    number_format($daily['transactions'] ?? 0)
                ];
            }
            $csvData[] = []; // Empty row
        }
        
        // Revenue by method if available
        if (!empty($analytics['revenue_by_method'])) {
            $csvData[] = ['Revenue by Payment Method'];
            $csvData[] = ['Payment Method', 'Revenue', 'Transactions', 'Average Amount'];
            
            foreach ($analytics['revenue_by_method'] as $method) {
                $csvData[] = [
                    ucfirst(str_replace('_', ' ', $method['payment_method'] ?? '')),
                    '$' . number_format($method['revenue'] ?? 0, 2),
                    number_format($method['transactions'] ?? 0),
                    '$' . number_format($method['avg_amount'] ?? 0, 2)
                ];
            }
        }
        
        return $csvData;
    }

    /**
     * Helper methods
     */
    private function getReportTitle(string $reportType): string
    {
        return match ($reportType) {
            'revenue' => 'Revenue Analytics Report',
            'bookings' => 'Bookings Analytics Report',
            'customers' => 'Customer Analytics Report',
            'operational' => 'Operational Analytics Report',
            'comprehensive' => 'Comprehensive Analytics Report',
            default => 'Analytics Report'
        };
    }

    private function formatPeriod(array $metadata): string
    {
        if (isset($metadata['start_date']) && isset($metadata['end_date'])) {
            $start = Carbon::parse($metadata['start_date'])->format('M j, Y');
            $end = Carbon::parse($metadata['end_date'])->format('M j, Y');
            return "{$start} - {$end}";
        }
        
        return $metadata['period'] ?? 'Custom Period';
    }

    // Mock implementations for other report types
    private function generateBookingsPDFContent(array $data): string { return '<div class="section"><p>Bookings report content</p></div>'; }
    private function generateCustomersPDFContent(array $data): string { return '<div class="section"><p>Customers report content</p></div>'; }
    private function generateOperationalPDFContent(array $data): string { return '<div class="section"><p>Operational report content</p></div>'; }
    private function generateComprehensivePDFContent(array $data): string { return '<div class="section"><p>Comprehensive report content</p></div>'; }
    
    private function prepareBookingsCSVData(array $data): array { return [['Bookings data placeholder']]; }
    private function prepareCustomersCSVData(array $data): array { return [['Customers data placeholder']]; }
    private function prepareOperationalCSVData(array $data): array { return [['Operational data placeholder']]; }
    private function prepareComprehensiveCSVData(array $data): array { return [['Comprehensive data placeholder']]; }
}