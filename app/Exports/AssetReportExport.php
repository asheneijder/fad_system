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
            'Asset Name',
            'Asset Tag No',
            'Serial No',
            'Category',
            'Model',
            'Status',
            'Assigned To',
            'Purchase Date',
            'Purchase Cost',
            'Current Value',
            'Location',
            'Last Sighting Date',
            'Notes',
        ];
    }

    public function map($asset): array
    {
        return [
            $asset->asset_name,
            $asset->asset_tag_no,
            $asset->serial_no,
            $asset->category->name ?? 'N/A',
            $asset->model->name ?? 'N/A',
            ucfirst($asset->status),
            $asset->user->name ?? 'Unassigned',
            $asset->purchase_date?->format('Y-m-d'),
            number_format($asset->purchase_cost, 2),
            number_format($asset->current_value, 2),
            $asset->location,
            $asset->last_sighting_date?->format('Y-m-d'),
            $asset->notes,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
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
        return 'Assets Report';
    }
}
