<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TemplateExport implements FromArray, WithHeadings
{
    protected array $headers;

    public function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    public function array(): array
    {
        return []; // Data kosong, hanya butuh header
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Baris 1 (Header): Font Bold, Background Kuning Tipis
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFFFF00'] // Kuning
                ]
            ],
            // Baris 2 dst (Contoh Data): Font Italic, Warna Abu
            '2:' . ($sheet->getHighestRow()) => [
                'font' => ['italic' => true, 'color' => ['argb' => 'FF808080']]
            ],
        ];
    }
}