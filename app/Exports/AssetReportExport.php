<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $assets;

    public function __construct($assets)
    {
        $this->assets = $assets;
    }

    public function collection()
    {
        return $this->assets;
    }

    public function headings(): array
    {
        return [
            'No',
            'Category',
            'Asset Tag No',
            'Description',
            'Serial No',
            'Date of Purchase',
            'Est. Useful Life (Years)',
            'Est. Useful Life (Days)',
            'Fully Depreciated Date',
            'Purchase Cost',
            'Depreciation Cost',
            'Current Value',
            'Location',
            'Location 2',
            'Assigned To',
            'Status',
            'Last Sighting Date',
        ];
    }

    public function map($asset): array
    {
        static $counter = 0;
        $counter++;

        return [
            $counter,
            $asset->category->name ?? 'N/A',
            $asset->asset_tag_no,
            $asset->asset_name,
            $asset->serial_no,
            $asset->purchase_date?->format('d-M-Y'),
            $asset->estimated_life,
            $asset->estimated_life_days,
            $asset->fully_depreciated_date?->format('d-M-Y'),
            $asset->purchase_cost,
            $asset->depreciation_cost,
            $asset->current_value,
            $asset->location,
            $asset->location_2,
            $asset->user->name ?? 'Unassigned',
            ucfirst($asset->status),
            $asset->last_sighting_date?->format('d-M-Y') ?? 'Never',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set column widths
        $columnWidths = [
            'A' => 8, 'B' => 20, 'C' => 15, 'D' => 30, 'E' => 20,
            'F' => 15, 'G' => 18, 'H' => 18, 'I' => 18, 'J' => 15,
            'K' => 16, 'L' => 15, 'M' => 15, 'N' => 15, 'O' => 25,
            'P' => 12, 'Q' => 15,
        ];

        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        // Style header
        $sheet->getStyle('A1:Q1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2D5F8B']],
            'borders' => ['allBorders' => ['borderStyle' => 'thin']],
            'alignment' => ['horizontal' => 'center'],
        ]);

        // Format currency columns
        $lastRow = $this->assets->count() + 1;
        $sheet->getStyle("J2:L{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');

        // Add auto filter
        $sheet->setAutoFilter('A1:Q1');

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Fixed Asset Listing';
    }
}
