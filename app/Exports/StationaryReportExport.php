<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StationaryReportExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $stationaryItems;

    protected $dateRange;

    public function __construct($stationaryItems, $dateRange = null)
    {
        $this->stationaryItems = $stationaryItems;
        $this->dateRange = $dateRange;

        // Debug: Log the data structure
        $this->debugDataStructure();
    }

    public function collection()
    {
        return $this->stationaryItems;
    }

    public function startCell(): string
    {
        return 'A1';
    }

    public function title(): string
    {
        return 'Stock Card Report';
    }

    public function headings(): array
    {
        $periodText = $this->getPeriodText();

        return [
            ['STOCK CARD REPORT'],
            [''],
            ['PERIOD: '.$periodText],
            [''],
            ['', '', '', '', '', '', '', '', '', '', 'PAGE : 1'],
            ['', '', '', '', '', '', '', '', '', '', 'GENERATED: '.now()->format('d/m/Y')],
            [''],
            ['AMANAHRAYA TRUSTEES BERHAD'],
        ];
    }

    public function map($item): array
    {
        $rows = [];

        // Add item header
        $rows[] = [
            $item->name.' - Transaction Log (Completed Requests)',
            '', '', '', '', '', '', '', '', '', '', $item->name,
        ];

        // Add stock information
        $rows[] = [
            'Current Stock: '.$item->current_stock.' | Min Stock: '.$item->min_stock,
            '', '', '', '', '', '', '', '', '', '', '',
        ];

        // Add column headers
        $rows[] = [
            'DATE',
            'DESCRIPTION',
            '',
            'IN',
            '',
            'OUT',
            '',
            'BALANCE',
            '',
            'COST P.',
            'SELL. P.',
            'AMOUNT',
        ];

        // Add opening balance
        $rows[] = [
            '',
            'BALANCE B/F',
            '',
            '',
            '',
            '',
            '',
            $item->current_stock,
            '',
            'RM'.number_format($item->cost_price, 2),
            'RM'.number_format($item->selling_price, 2),
            '',
        ];

        // Get transactions
        $transactions = $this->getTransactionLog($item);
        $totalIn = 0;
        $totalOut = 0;
        $totalAmount = 0;

        if (count($transactions) > 0) {
            foreach ($transactions as $transaction) {
                // Transaction date row
                $rows[] = [
                    $transaction['date'],
                    '', '', '', '', '', '', '', '', '', '', '',
                ];

                // Description row (purpose)
                $rows[] = [
                    '',
                    'Request: '.$transaction['purpose'],
                    '', '', '', '', '', '', '', '', '', '',
                ];

                // User information rows
                $rows[] = [
                    '',
                    $transaction['user'],
                    '', '', '', '', '', '', '', '', '', '',
                ];

                $rows[] = [
                    '',
                    $transaction['department'],
                    '', '', '', '', '', '', '', '', '', '',
                ];

                if (! empty($transaction['email'])) {
                    $rows[] = [
                        '',
                        $transaction['email'],
                        '', '', '', '', '', '', '', '', '', '',
                    ];
                }

                $rows[] = [
                    '',
                    'Approved by: '.$transaction['approver'],
                    '', '', '', '', '', '', '', '', '', '',
                ];

                // Transaction details row
                $rows[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    $transaction['out'],
                    '',
                    $transaction['balance'],
                    '',
                    'RM'.number_format($transaction['cost_price'], 2),
                    'RM'.number_format($transaction['selling_price'], 2),
                    $transaction['amount'] > 0 ? 'RM'.number_format($transaction['amount'], 2) : '',
                ];

                // Empty row for spacing
                $rows[] = ['', '', '', '', '', '', '', '', '', '', '', ''];

                // Update totals
                $totalIn += $transaction['in'];
                $totalOut += $transaction['out'];
                $totalAmount += $transaction['amount'];
            }

            // Add totals row
            $rows[] = [
                'TOTAL :',
                '',
                '',
                $totalIn,
                '',
                $totalOut,
                '',
                $item->current_stock,
                '',
                $item->unit,
                '',
                'RM'.number_format($totalAmount, 2),
            ];
        } else {
            // No transactions message
            $rows[] = [
                '',
                'No completed request history available',
                '', '', '', '', '', '', '', '', '', '',
            ];
        }

        // Add empty rows between items
        $rows[] = ['', '', '', '', '', '', '', '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', '', '', '', '', '', ''];

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12, // DATE
            'B' => 40, // DESCRIPTION
            'C' => 5,  // Empty
            'D' => 8,  // IN
            'E' => 5,  // Empty
            'F' => 8,  // OUT
            'G' => 5,  // Empty
            'H' => 10, // BALANCE
            'I' => 5,  // Empty
            'J' => 12, // COST P.
            'K' => 12, // SELL. P.
            'L' => 12, // AMOUNT
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // ... (keep the same styling code as before)
        // Set default font
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        // Merge cells for headers
        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('A3:L3');
        $sheet->mergeCells('A8:L8');
        $sheet->mergeCells('E5:L5');
        $sheet->mergeCells('E6:L6');

        // Main title style
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Period style
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Company name style
        $sheet->getStyle('A8')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Page info style
        $sheet->getStyle('E5')->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            'font' => ['bold' => true],
        ]);
        $sheet->getStyle('E6')->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            'font' => ['italic' => true],
        ]);

        // Auto-size rows for better readability
        foreach (range(1, 500) as $row) {
            $sheet->getRowDimension($row)->setRowHeight(18);
        }

        // Set specific row heights
        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(8)->setRowHeight(25);

        // Number formatting for currency columns
        $sheet->getStyle('J:L')->getNumberFormat()->setFormatCode('"RM"#,##0.00');

        // Alignment
        $sheet->getStyle('D:D')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F:F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H:H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J:L')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function getPeriodText()
    {
        if (! $this->dateRange) {
            return 'ALL TIME';
        }

        return match ($this->dateRange) {
            'today' => 'TODAY - '.now()->format('d/m/Y'),
            'yesterday' => 'YESTERDAY - '.now()->subDay()->format('d/m/Y'),
            'last_7_days' => 'LAST 7 DAYS - '.now()->subDays(6)->format('d/m/Y').' TO '.now()->format('d/m/Y'),
            'last_30_days' => 'LAST 30 DAYS - '.now()->subDays(29)->format('d/m/Y').' TO '.now()->format('d/m/Y'),
            'this_month' => 'THIS MONTH - '.now()->startOfMonth()->format('F Y'),
            'last_month' => 'LAST MONTH - '.now()->subMonth()->startOfMonth()->format('F Y'),
            'this_quarter' => 'THIS QUARTER - Q'.ceil(now()->month / 3).' '.now()->year,
            'this_year' => 'THIS YEAR - '.now()->year,
            default => 'ALL TIME',
        };
    }

    private function getTransactionLog($item)
    {
        // Debug the item structure
        $this->debugItemStructure($item);

        // Try multiple ways to access the data
        $requestDetails = null;

        // Method 1: Check if it's an Eloquent collection
        if (method_exists($item, 'getRelation') && $item->relationLoaded('requestItemDetails')) {
            $requestDetails = $item->requestItemDetails;
        }
        // Method 2: Check if it's a property
        elseif (isset($item->request_item_details)) {
            $requestDetails = $item->request_item_details;
        }
        // Method 3: Check if it's a relationship
        elseif (method_exists($item, 'requestItemDetails')) {
            $requestDetails = $item->requestItemDetails;
        }

        if (! $requestDetails) {
            Log::info('No request details found for item: '.$item->name);

            return [];
        }

        // Convert to array if it's a collection
        if (is_object($requestDetails) && method_exists($requestDetails, 'toArray')) {
            $requestDetails = $requestDetails->toArray();
        }

        // Filter only completed request details
        $completedRequests = array_filter($requestDetails, function ($detail) {
            // Multiple ways to check for completed status
            $status = null;

            if (isset($detail['request_item']['status'])) {
                $status = $detail['request_item']['status'];
            } elseif (isset($detail->request_item) && isset($detail->request_item->status)) {
                $status = $detail->request_item->status;
            } elseif (isset($detail['status'])) {
                $status = $detail['status'];
            }

            return $status === 'completed';
        });

        Log::info('Completed requests found: '.count($completedRequests).' for item: '.$item->name);

        if (empty($completedRequests)) {
            return [];
        }

        $balance = $item->current_stock;
        $transactions = [];

        foreach ($completedRequests as $detail) {
            // Extract request data with multiple access methods
            $request = null;
            if (isset($detail['request_item'])) {
                $request = $detail['request_item'];
            } elseif (isset($detail->request_item)) {
                $request = $detail->request_item;
            }

            // Get user and request information
            $userName = 'Unknown';
            $userDepartment = 'Unknown Department';
            $userEmail = '';
            $purpose = 'No purpose specified';
            $approverName = 'Unknown';
            $approvedAt = '';

            if ($request) {
                // Get user information
                $user = null;
                if (isset($request['user'])) {
                    $user = $request['user'];
                } elseif (isset($request->user)) {
                    $user = $request->user;
                }

                if ($user) {
                    $userName = $user['name'] ?? (isset($user->name) ? $user->name : 'Unknown');
                    $userDepartment = $user['department'] ?? (isset($user->department) ? $user->department : 'Unknown Department');
                    $userEmail = $user['email'] ?? (isset($user->email) ? $user->email : '');
                }

                // Get purpose
                $purpose = $request['purpose'] ?? (isset($request->purpose) ? $request->purpose : 'No purpose specified');

                // Get approver
                $approver = null;
                if (isset($request['approved_by'])) {
                    $approver = $request['approved_by'];
                } elseif (isset($request->approved_by)) {
                    $approver = $request->approved_by;
                } elseif (isset($request['approved_by_user'])) {
                    $approver = $request['approved_by_user'];
                } elseif (isset($request->approved_by_user)) {
                    $approver = $request->approved_by_user;
                }

                if ($approver) {
                    $approverName = $approver['name'] ?? (isset($approver->name) ? $approver->name : 'Unknown');
                }

                // Get approval date
                $approvedAt = $request['approved_at'] ?? (isset($request->approved_at) ? $request->approved_at : null);
                if ($approvedAt) {
                    $approvedAt = Carbon::parse($approvedAt)->format('d/m/Y');
                } else {
                    $approvedAt = 'N/A';
                }
            }

            // Get quantity
            $outQuantity = $detail['final_quantity'] ??
                          (isset($detail->final_quantity) ? $detail->final_quantity :
                          ($detail['quantity'] ?? (isset($detail->quantity) ? $detail->quantity : 0)));

            $transactions[] = [
                'date' => $approvedAt,
                'purpose' => $purpose,
                'user' => $userName,
                'department' => $userDepartment,
                'email' => $userEmail,
                'approver' => $approverName,
                'in' => 0,
                'out' => $outQuantity,
                'balance' => $balance - $outQuantity,
                'cost_price' => $item->cost_price,
                'selling_price' => $item->selling_price,
                'amount' => $outQuantity * (floatval($item->cost_price) ?? 0),
            ];

            $balance -= $outQuantity;
        }

        return array_reverse($transactions);
    }

    private function debugDataStructure()
    {
        if (count($this->stationaryItems) > 0) {
            $firstItem = $this->stationaryItems[0];

            Log::info('=== EXPORT DATA DEBUG ===');
            Log::info('First item name: '.$firstItem->name);
            Log::info('First item class: '.get_class($firstItem));

            // Check what relationships are loaded
            $relations = [];
            if (method_exists($firstItem, 'getRelations')) {
                $relations = array_keys($firstItem->getRelations());
            }
            Log::info('Loaded relations: '.implode(', ', $relations));

            // Check if request_item_details exists
            if (isset($firstItem->request_item_details)) {
                Log::info('request_item_details exists, type: '.gettype($firstItem->request_item_details));
                if (is_array($firstItem->request_item_details)) {
                    Log::info('request_item_details count: '.count($firstItem->request_item_details));
                    if (count($firstItem->request_item_details) > 0) {
                        $firstDetail = $firstItem->request_item_details[0];
                        Log::info('First detail type: '.gettype($firstDetail));
                        Log::info('First detail data: '.json_encode($firstDetail));
                    }
                } elseif (is_object($firstItem->request_item_details)) {
                    Log::info('request_item_details class: '.get_class($firstItem->request_item_details));
                    Log::info('request_item_details count: '.$firstItem->request_item_details->count());
                }
            } else {
                Log::info('request_item_details does not exist');
            }
            Log::info('=== END DEBUG ===');
        }
    }

    private function debugItemStructure($item)
    {
        Log::info("=== DEBUG ITEM: {$item->name} ===");

        // Check all possible ways to access request details
        $accessMethods = [
            'request_item_details' => isset($item->request_item_details),
            'requestItemDetails' => isset($item->requestItemDetails),
            'request_item_details (method)' => method_exists($item, 'request_item_details'),
            'requestItemDetails (method)' => method_exists($item, 'requestItemDetails'),
        ];

        foreach ($accessMethods as $method => $exists) {
            Log::info("{$method}: ".($exists ? 'YES' : 'NO'));
        }

        if (isset($item->request_item_details)) {
            $details = $item->request_item_details;
            if (is_array($details)) {
                Log::info('request_item_details is array, count: '.count($details));
                foreach ($details as $index => $detail) {
                    Log::info("Detail {$index}: ".json_encode($detail));
                    if (isset($detail['request_item'])) {
                        Log::info('  - Has request_item, status: '.($detail['request_item']['status'] ?? 'NOT SET'));
                    }
                }
            } elseif (is_object($details)) {
                Log::info('request_item_details is object, class: '.get_class($details));
            }
        }

        Log::info('=== END ITEM DEBUG ===');
    }
}
