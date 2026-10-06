<?php

namespace App\Exports;

use App\Models\Admin\MediaPlan;
use App\Models\Admin\MediaPlanEvent;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

/*
 * تصدير الخطة الإعلامية إلى Excel — بنسختين هوية:
 *  - rowad     : مؤسسة الرواد للتعاون والتنمية (البرتقالي المؤسسي).
 *  - rowaduna  : هوية «روادنا» (كحلي/مرجاني) عند التصدير من صفحة روادنا.
 */
class MediaPlanFormExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    public function __construct(private MediaPlan $plan, private string $theme = 'rowad') {}

    private function palette(): array
    {
        return $this->theme === 'rowaduna'
            ? ['title' => 'FF1B2B5A', 'head' => 'FFFF4B3E', 'soft' => 'FFE9EDF7']
            : ['title' => 'FFF37021', 'head' => 'FFD95300', 'soft' => 'FFFFF3EC'];
    }

    private function orgName(): string
    {
        return $this->theme === 'rowaduna' ? 'روادنا — RAWADUNA' : 'مؤسسة الرواد للتعاون والتنمية';
    }

    public function collection(): Collection
    {
        return $this->plan->events->map(fn (MediaPlanEvent $e) => $e);
    }

    public function headings(): array
    {
        return [
            'التاريخ', 'اليوم', 'الساعة', 'المكتب', 'اسم الفعالية', 'موقع الفعالية',
            'المسؤول عن الفعالية', 'نوع التغطية', 'الملخص',
            'المراسل', 'حالة التغطية', 'سبب عدم التغطية', 'رابط المواد (درايف)',
            'الناشر/المونتير', 'حالة النشر', 'رابط المعاينة (مؤقت)', 'روابط النشر الدائم',
            'ملاحظات التغطية',
        ];
    }

    public function map($event): array
    {
        $links = collect($event->publish_links ?? [])
            ->map(fn ($l) => (MediaPlanEvent::PLATFORMS[$l['platform']] ?? $l['platform']) . ': ' . $l['url'])
            ->implode(' | ');

        return [
            $event->event_date->format('Y-m-d'),
            $event->day ?? '—',
            substr((string) $event->event_time, 0, 5),
            $event->office ?? '—',
            $event->event_name,
            $event->location ?? '—',
            $event->responsible?->name ?? '—',
            $event->coverage_type ?? '—',
            $event->summary ?? '—',
            $event->reporter?->name ?? '—',
            MediaPlanEvent::COVERAGE_STATUSES[$event->coverage_status] ?? (string) $event->coverage_status,
            $event->not_covered_reason ?? '—',
            $event->media_items_url ?? '—',
            $event->publisher?->name ?? '—',
            MediaPlanEvent::PUBLISH_STATUSES[$event->publish_status] ?? (string) $event->publish_status,
            $event->preview_url ?? '—',
            $links !== '' ? $links : '—',
            $event->coverage_note ?? '—',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => fn (AfterSheet $event) => $this->decorate($event->sheet->getDelegate()),
        ];
    }

    private function decorate($sheet): void
    {
        $p = $this->palette();
        $plan = $this->plan;
        $month = optional($plan->month_date)->format('Y-m');

        $header = [
            [$this->orgName() . ' — الخطة الإعلامية — Media Plan', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['الشهر / Month:', $month, '', 'المركز / Office:', $plan->center?->name ?? '—', '', 'المشروع / Project:', $plan->project?->name ?? '—', '', 'الحالة / Status:', MediaPlan::STATUSES[$plan->status] ?? $plan->status, '', 'عدد الفعاليات:', $plan->events->count(), '', '', '', ''],
            ['أنشأها / Creator:', $plan->creator?->name ?? '—', '', 'المدير المباشر:', $plan->directManager?->name ?? '—', '', 'مدير المشاريع:', $plan->pm2User?->name ?? '—', '', 'مسؤول روادنا:', $plan->rowadunaUser?->name ?? '—', '', '', '', '', '', '', '', ''],
            ['ملاحظات / Note:', $plan->note ?? '—', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
        ];

        $insert = count($header);
        $sheet->insertNewRowBefore(1, $insert);

        foreach ($header as $i => $line) {
            $sheet->fromArray($line, null, 'A' . ($i + 1));
        }

        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()
            ->setARGB($p['title']);
        $sheet->getStyle("A2:G{$insert}")->getFont()->setSize(10);

        $headRow = $insert + 1;
        $cols = 'R';
        $sheet->getStyle("A{$headRow}:{$cols}{$headRow}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$headRow}:{$cols}{$headRow}")->getFill()
            ->setFillType('solid')->getStartColor()->setARGB($p['head']);
        $sheet->getStyle("A{$headRow}:{$cols}{$lastRow}")->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }
}
