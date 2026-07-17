<?php

namespace App\Exports;

use App\Models\Region;
use App\Models\Area;
use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AchievementReportExport implements FromCollection, WithMapping, WithStyles, WithTitle, WithCustomStartCell, WithEvents
{
    protected $startDate;
    protected $endDate;
    protected $regionId;
    protected $areaId;

    public function __construct($startDate = null, $endDate = null, $regionId = null, $areaId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->regionId = $regionId;
        $this->areaId = $areaId;
    }

    public function collection()
    {
        // Get Regions and their Areas, calculating totals per area and per region
        $transactionFilter = function($q) {
            if ($this->startDate && $this->endDate) {
                $q->whereBetween('transaction_date', [$this->startDate, $this->endDate]);
            } elseif ($this->startDate) {
                $q->whereDate('transaction_date', '>=', $this->startDate);
            } elseif ($this->endDate) {
                $q->whereDate('transaction_date', '<=', $this->endDate);
            }
        };

        $regionsQuery = Region::with(['areas' => function($q) {
            if ($this->areaId) {
                $q->where('id', $this->areaId);
            }
        }])
        ->when($this->regionId, fn($q) => $q->where('id', $this->regionId))
        ->get();

        $rows = new Collection();

        foreach ($regionsQuery as $region) {
            $regionPromotorCount = 0;
            $regionEdukasi = 0;
            $regionSp = 0;
            $regionRebuy = 0;
            $regionAkuisisi = 0;

            $isFirstArea = true;

            foreach ($region->areas as $area) {
                // Get totals for this area
                $stats = DB::table('users')
                    ->where('area_id', $area->id)
                    ->join('transactions', 'users.id', '=', 'transactions.user_id')
                    ->when($this->startDate && $this->endDate, function($q) {
                        if ($this->startDate === $this->endDate) {
                            $q->whereDate('transactions.transaction_date', $this->startDate);
                        } else {
                            $q->whereBetween('transactions.transaction_date', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
                        }
                    })
                    ->when($this->startDate && !$this->endDate, fn($q) => $q->whereDate('transactions.transaction_date', '>=', $this->startDate))
                    ->when(!$this->startDate && $this->endDate, fn($q) => $q->whereDate('transactions.transaction_date', '<=', $this->endDate))
                    ->selectRaw('
                        COUNT(DISTINCT users.id) as promotor_count,
                        SUM(transactions.jml_edukasi) as total_edukasi,
                        SUM(transactions.jml_sp) as total_sp,
                        SUM(transactions.jml_rebuy) as total_rebuy,
                        SUM(transactions.jml_aktivasi_gemini) as total_akuisisi
                    ')
                    ->first();

                if ($stats && $stats->promotor_count > 0) {
                    $rows->push([
                        'region' => $isFirstArea ? $region->name : '',
                        'rge' => '-', // Default empty or placeholder for RGE
                        'area' => $area->name,
                        'promotor_count' => $stats->promotor_count,
                        'edukasi' => $stats->total_edukasi ?? 0,
                        'sp' => $stats->total_sp ?? 0,
                        'rebuy' => $stats->total_rebuy ?? 0,
                        'akuisisi' => $stats->total_akuisisi ?? 0,
                        'is_total' => false,
                        'region_name' => $region->name, // for grouping
                    ]);

                    $regionPromotorCount += $stats->promotor_count;
                    $regionEdukasi += $stats->total_edukasi ?? 0;
                    $regionSp += $stats->total_sp ?? 0;
                    $regionRebuy += $stats->total_rebuy ?? 0;
                    $regionAkuisisi += $stats->total_akuisisi ?? 0;
                    
                    $isFirstArea = false;
                }
            }

            // Add TOTAL row for the region
            if (!$isFirstArea) {
                $rows->push([
                    'region' => '',
                    'rge' => 'TOTAL',
                    'area' => '',
                    'promotor_count' => $regionPromotorCount,
                    'edukasi' => $regionEdukasi,
                    'sp' => $regionSp,
                    'rebuy' => $regionRebuy,
                    'akuisisi' => $regionAkuisisi,
                    'is_total' => true,
                    'region_name' => $region->name,
                ]);
            }
        }

        return $rows;
    }

    public function map($row): array
    {
        return [
            $row['region'],
            $row['rge'],
            $row['area'],
            $row['promotor_count'],
            $row['edukasi'],
            $row['sp'],
            $row['rebuy'],
            $row['akuisisi'],
        ];
    }

    public function startCell(): string
    {
        return 'B4';
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();
        
        // Style the Title
        $sheet->mergeCells('B2:I2');
        $sheet->setCellValue('B2', 'REPORT ACCUMULATION ACHIEVEMENT PROMOTOR');
        
        $sheet->getStyle('B2:I2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'D9D9D9'],
            ]
        ]);

        // Define Headers
        $sheet->setCellValue('B3', 'Region');
        $sheet->setCellValue('C3', 'RGE');
        $sheet->setCellValue('D3', 'Area Penempatan');
        $sheet->setCellValue('E3', 'Jumlah Promotor');
        $sheet->setCellValue('F3', 'Edukasi Total');
        $sheet->setCellValue('G3', 'SP Total');
        $sheet->setCellValue('H3', 'Rebuy Total');
        $sheet->setCellValue('I3', 'Akuisisi Total');

        // Style the Headers
        $sheet->getStyle('B3:I3')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'BDD7EE'],
            ]
        ]);

        // Auto-size columns
        foreach (range('B', 'I') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Apply borders to all data cells
        if ($lastRow > 3) {
            $sheet->getStyle('B4:I' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);
        }

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                // Apply specific styling to TOTAL rows
                for ($row = 4; $row <= $lastRow; $row++) {
                    $rgeValue = $sheet->getCell('C' . $row)->getValue();
                    if ($rgeValue === 'TOTAL') {
                        $sheet->getStyle('B' . $row . ':I' . $row)->applyFromArray([
                            'font' => [
                                'bold' => true,
                            ],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'color' => ['rgb' => 'E2EFDA'], // Light green for total
                            ]
                        ]);
                    }
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Achievement Promotor';
    }
}
