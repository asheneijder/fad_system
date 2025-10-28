<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserActivityReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $users;

    public function __construct($users)
    {
        $this->users = $users;
    }

    public function collection()
    {
        return $this->users;
    }

    public function headings(): array
    {
        return [
            'User Name',
            'Email',
            'Department',
            'Status',
            'Roles',
            'Assets Assigned',
            'Last Login',
            'Created Date',
        ];
    }

    public function map($user): array
    {
        $roles = $user->roles->pluck('name')->implode(', ');

        return [
            $user->name,
            $user->email,
            $user->department ?? 'N/A',
            ucfirst($user->status),
            $roles,
            $user->assets_count ?? 0,
            $user->last_login_at?->format('Y-m-d H:i:s') ?? 'Never',
            $user->created_at?->format('Y-m-d'),
        ];
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
        return 'User Activity Report';
    }
}
