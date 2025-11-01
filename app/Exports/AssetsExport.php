<?php

namespace App\Exports;

use App\Models\Asset;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssetsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $assetIds;

    public function __construct(array $assetIds = [])
    {
        $this->assetIds = $assetIds;
    }

    public function collection()
    {
        return Asset::with(['model', 'category', 'user'])
            ->when(!empty($this->assetIds), function ($query) {
                $query->whereIn('id', $this->assetIds);
            })
            ->get();
    }

    public function headings(): array
    {
        return [
            'Asset Name',
            'Asset Tag',
            'Serial Number',
            'Model',
            'Category',
            'Status',
            'Quantity',
            'Location',
            'Secondary Location',
            'Assigned To',
            'Purchase Date',
            'Purchase Cost',
            'Current Value',
            'Depreciation Cost',
            'Estimated Life (Years)',
            'Estimated Life (Days)',
            'Fully Depreciated Date',
            'Last Sighting Date',
            'Notes',
            'Created At',
        ];
    }

    public function map($asset): array
    {
        return [
            $asset->asset_name,
            $asset->asset_tag_no,
            $asset->serial_no ?? 'N/A',
            $asset->model ? $asset->model->name : 'N/A',
            $asset->category ? $asset->category->name : 'N/A',
            $asset->status,
            $asset->qty,
            $asset->location,
            $asset->location_2 ?? 'N/A',
            $asset->user ? $asset->user->name : 'Not Assigned',
            $asset->purchase_date ? $asset->purchase_date->format('Y-m-d') : 'N/A',
            $asset->purchase_cost,
            $asset->current_value,
            $asset->depreciation_cost,
            $asset->estimated_life,
            $asset->estimated_life_days,
            $asset->fully_depreciated_date ? $asset->fully_depreciated_date->format('Y-m-d') : 'N/A',
            $asset->last_sighting_date ? $asset->last_sighting_date->format('Y-m-d') : 'N/A',
            $asset->notes ?? 'N/A',
            $asset->created_at->format('Y-m-d H:i:s'),
        ];
    }
}