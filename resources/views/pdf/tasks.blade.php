<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تقرير المهام</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'dejavusans', sans-serif;
            padding: 10px;
            color: #333;
            direction: rtl;
            text-align: right;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #6366f1;
        }
        .header h1 {
            font-size: 26px;
            color: #6366f1;
            margin-bottom: 8px;
        }
        .header p { font-size: 14px; color: #666; }

        .info {
            margin-bottom: 20px;
            font-size: 12px;
            color: #666;
            background: #f9fafb;
            padding: 10px;
            border-radius: 8px;
        }
        .info span { margin-left: 15px; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        th {
            background: #6366f1;
            color: white;
            padding: 10px 6px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #4f46e5;
        }
        td {
            padding: 8px 6px;
            border: 1px solid #e5e7eb;
            text-align: right;
        }
        tr:nth-child(even) { background: #f9fafb; }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 10px;
        }
        .pending { background: #e5e7eb; color: #374151; }
        .in_progress { background: #dbeafe; color: #1e40af; }
        .completed { background: #d1fae5; color: #065f46; }
        .priority-low { background: #e5e7eb; color: #374151; }
        .priority-medium { background: #fef3c7; color: #92400e; }
        .priority-high { background: #fee2e2; color: #991b1b; }

        .numeric { text-align: center; direction: ltr; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 تقرير المهام</h1>
        <p>Task Manager</p>
    </div>

    <div class="info">
        <span>👤 {{ $userName }}</span>
        <span>📅 {{ $date }}</span>
        <span>📊 إجمالي: {{ $tasks->count() }} مهمة</span>
        @if($projectName)
            <span>📁 {{ $projectName }}</span>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 25%;">العنوان</th>
                <th style="width: 15%;">الحالة</th>
                <th style="width: 10%;">الأولوية</th>
                <th style="width: 15%;">المشروع</th>
                <th style="width: 15%;">التاجات</th>
                <th style="width: 15%;">الاستحقاق</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tasks as $task)
                <tr>
                    <td class="numeric">{{ $task->id }}</td>
                    <td>{{ $task->title }}</td>
                    <td style="text-align: center;">
                        <span class="badge {{ $task->status }}">
                            {{ match($task->status) {
                                'pending' => 'معلقة',
                                'in_progress' => 'قيد التنفيذ',
                                'completed' => 'مكتملة',
                                default => $task->status,
                            } }}
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge priority-{{ $task->priority }}">
                            {{ match($task->priority) {
                                'low' => 'منخفضة',
                                'medium' => 'متوسطة',
                                'high' => 'عالية',
                                default => $task->priority,
                            } }}
                        </span>
                    </td>
                    <td>{{ $task->project?->name ?? '—' }}</td>
                    <td>{{ $task->tags->pluck('name')->implode('، ') ?: '—' }}</td>
                    <td class="numeric">{{ $task->due_date?->format('Y-m-d') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #999;">
                        لا توجد مهام
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>