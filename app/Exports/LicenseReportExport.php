<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LicenseReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $licenses;

    public function __construct($licenses)
    {
        $this->licenses = $licenses;
    }

    public function collection()
    {
        return $this->licenses;
    }

    public function headings(): array
    {
        return [
            'License Name',
            'Manufacturer',
            'Product Key',
            'Licensed To',
            'Licensed Email',
            'Total Quantity',
            'Available Quantity',
            'Status',
            'Expiration Date',
            'Days Until Expiry',
        ];
    }

    public function map($license): array
    {
        $expirationDate = $license->expiration_date;
        $daysUntilExpiry = $expirationDate ? $expirationDate->diffInDays(now(), false) * -1 : 'N/A';

        return [
            $license->license_name,
            $license->manufacturer,
            $license->product_key,
            $license->licensed_name,
            $license->licensed_email,
            $license->total_qty,
            $license->available_qty,
            $license->status ? 'Active' : 'Inactive',
            $expirationDate?->format('Y-m-d'),
            $daysUntilExpiry,
        ];
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
        return 'Licenses Report';
    }
}
