<?php

namespace App\Http\Controllers\Api;

use App\Exports\TasksExport;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    /**
     * تصدير المهام إلى PDF باستخدام mPDF (يدعم العربية والإيموجي).
     */
    public function tasksPdf(Request $request)
    {
        $userId = $request->user()->id;
        $projectId = $request->filled('project_id') ? $request->integer('project_id') : null;

        $query = Task::query()
            ->whereNull('parent_id')
            ->whereHas('project', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('members', fn ($q) => $q->where('user_id', $userId));
            })
            ->with(['project:id,name', 'tags:id,name']);

        $projectName = null;
        if ($projectId) {
            $project = Project::findOrFail($projectId);
            $this->authorize('view', $project);
            $query->where('project_id', $projectId);
            $projectName = $project->name;
        }

        $tasks = $query->orderBy('created_at', 'desc')->get();

        // ⭐ إعدادات mPDF للعربية + RTL + الإيموجي
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        // ⭐ إضافة Footer (رقم الصفحة + التاريخ)
        $mpdf->SetHTMLFooter('
            <div style="text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 5px;">
                تم إنشاء هذا التقرير بواسطة Task Manager — {DATE j-m-Y H:i}
            </div>
        ');

        $html = view('pdf.tasks', [
            'tasks' => $tasks,
            'userName' => $request->user()->name,
            'date' => now()->format('Y-m-d'),
            'projectName' => $projectName,
        ])->render();

        $mpdf->WriteHTML($html);

        $filename = 'tasks-' . now()->format('Y-m-d-His') . '.pdf';

        // إرجاع الملف كـ تحميل (Stream)
        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * تصدير المهام إلى Excel.
     */
    public function tasksExcel(Request $request): BinaryFileResponse
    {
        $userId = $request->user()->id;
        $projectId = $request->filled('project_id') ? $request->integer('project_id') : null;

        if ($projectId) {
            $project = Project::findOrFail($projectId);
            $this->authorize('view', $project);
        }

        $filename = 'tasks-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(
            new TasksExport($userId, $projectId),
            $filename
        );
    }
}