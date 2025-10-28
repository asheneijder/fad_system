<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DashboardReportExport implements WithMultipleSheets
{
    protected $summary;

    public function __construct($summary)
    {
        $this->summary = $summary;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Summary Sheet
        $sheets[] = new DashboardSummarySheet($this->summary);

        // Assets Sheet
        $sheets[] = new AssetsSummarySheet($this->summary['assets']);

        // Licenses Sheet
        $sheets[] = new LicensesSummarySheet($this->summary['licenses']);

        // Users Sheet
        $sheets[] = new UsersSummarySheet($this->summary['users']);

        // Stationary Sheet
        $sheets[] = new StationarySummarySheet($this->summary['stationary']);

        // Claims Sheet
        $sheets[] = new ClaimsSummarySheet($this->summary['claims']);

        return $sheets;
    }
}

// Individual Sheets
class DashboardSummarySheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $summary;

    public function __construct($summary)
    {
        $this->summary = $summary;
    }

    public function array(): array
    {
        return [
            ['Category', 'Metric', 'Value'],
            ['Assets', 'Total Assets', $this->summary['assets']['total']],
            ['Assets', 'Assigned Assets', $this->summary['assets']['assigned']],
            ['Assets', 'Assignment Rate', $this->summary['assets']['assigned_rate'].'%'],
            ['Assets', 'Total Value', $this->summary['assets']['total_value']],
            ['Licenses', 'Total Licenses', $this->summary['licenses']['total']],
            ['Licenses', 'Expiring Soon', $this->summary['licenses']['expiring']],
            ['Licenses', 'Utilization Rate', $this->summary['licenses']['utilization'].'%'],
            ['Users', 'Total Users', $this->summary['users']['total']],
            ['Users', 'Active Users', $this->summary['users']['active']],
            ['Users', 'Users with Assets', $this->summary['users']['with_assets']],
            ['Stationary', 'Total Items', $this->summary['stationary']['total']],
            ['Stationary', 'Low Stock Items', $this->summary['stationary']['low_stock']],
            ['Stationary', 'Alert Rate', $this->summary['stationary']['alert_rate'].'%'],
            ['Claims', 'Pending Claims', $this->summary['claims']['pending']],
            ['Claims', 'Approved Claims', $this->summary['claims']['approved']],
            ['Claims', 'Total Claims', $this->summary['claims']['total']],
        ];
    }

    public function headings(): array
    {
        return ['Category', 'Metric', 'Value'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['argb' => 'FF6366F1'],
                ],
                'font' => ['color' => ['argb' => 'FFFFFFFF']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Dashboard Summary';
    }
}

class AssetsSummarySheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $assets;

    public function __construct($assets)
    {
        $this->assets = $assets;
    }

    public function array(): array
    {
        return [
            ['Metric', 'Value'],
            ['Total Assets', $this->assets['total']],
            ['Assigned Assets', $this->assets['assigned']],
            ['Unassigned Assets', $this->assets['unassigned']],
            ['Assignment Rate', $this->assets['assigned_rate'].'%'],
            ['Total Asset Value', $this->assets['total_value']],
        ];
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFE6E6FA'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Assets Overview';
    }
}

class LicensesSummarySheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $licenses;

    public function __construct($licenses)
    {
        $this->licenses = $licenses;
    }

    public function array(): array
    {
        return [
            ['Metric', 'Value'],
            ['Total Licenses', $this->licenses['total']],
            ['Expiring Soon', $this->licenses['expiring']],
            ['Expired Licenses', $this->licenses['expired']],
            ['Utilization Rate', $this->licenses['utilization'].'%'],
        ];
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFE6F4EA'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Licenses Overview';
    }
}

class UsersSummarySheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $users;

    public function __construct($users)
    {
        $this->users = $users;
    }

    public function array(): array
    {
        return [
            ['Metric', 'Value'],
            ['Total Users', $this->users['total']],
            ['Active Users', $this->users['active']],
            ['Inactive Users', $this->users['inactive']],
            ['Users with Assets', $this->users['with_assets']],
        ];
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFE6F3FF'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Users Overview';
    }
}

class StationarySummarySheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $stationary;

    public function __construct($stationary)
    {
        $this->stationary = $stationary;
    }

    public function array(): array
    {
        return [
            ['Metric', 'Value'],
            ['Total Items', $this->stationary['total']],
            ['Low Stock Items', $this->stationary['low_stock']],
            ['Out of Stock Items', $this->stationary['out_of_stock']],
            ['Alert Rate', $this->stationary['alert_rate'].'%'],
        ];
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFFEF6E6'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Stationary Overview';
    }
}

class ClaimsSummarySheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $claims;

    public function __construct($claims)
    {
        $this->claims = $claims;
    }

    public function array(): array
    {
        return [
            ['Metric', 'Value'],
            ['Pending Claims', $this->claims['pending']],
            ['Approved Claims', $this->claims['approved']],
            ['Total Claims', $this->claims['total']],
        ];
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFFCE7F3'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Claims Overview';
    }
}
