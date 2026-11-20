<?php

namespace App\Exports;

use App\Models\Task;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TasksExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(
        private int $userId,
        private ?int $projectId = null,
    ) {}

    public function collection()
    {
        $query = Task::query()
            ->whereNull('parent_id')
            ->whereHas('project', function ($q) {
                $q->where('user_id', $this->userId)
                  ->orWhereHas('members', fn ($q) => $q->where('user_id', $this->userId));
            })
            ->with(['project:id,name', 'tags:id,name']);

        if ($this->projectId) {
            $query->where('project_id', $this->projectId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'العنوان',
            'الوصف',
            'الحالة',
            'الأولوية',
            'المشروع',
            'التاجات',
            'تاريخ الاستحقاق',
            'تاريخ الإنشاء',
        ];
    }

    public function map($task): array
    {
        $statusLabels = [
            'pending' => 'معلقة',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتملة',
        ];

        $priorityLabels = [
            'low' => 'منخفضة',
            'medium' => 'متوسطة',
            'high' => 'عالية',
        ];

        return [
            $task->id,
            $task->title,
            $task->description ?? '—',
            $statusLabels[$task->status] ?? $task->status,
            $priorityLabels[$task->priority] ?? $task->priority,
            $task->project?->name ?? '—',
            $task->tags->pluck('name')->implode(', ') ?: '—',
            $task->due_date?->format('Y-m-d') ?? '—',
            $task->created_at?->format('Y-m-d H:i') ?? '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '6366F1'],
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                ],
            ],
        ];
    }
}