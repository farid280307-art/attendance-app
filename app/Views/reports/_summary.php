<?php

declare(strict_types=1);

$reportSummaryCards = [
    ['label' => 'Hadir', 'value' => $report['summary']['present'], 'icon' => 'bi-check-circle', 'tone' => 'success'],
    ['label' => 'Terlambat', 'value' => $report['summary']['late'], 'icon' => 'bi-clock-history', 'tone' => 'warning'],
    ['label' => 'Cuti', 'value' => $report['summary']['leave'], 'icon' => 'bi-calendar2-week', 'tone' => 'primary'],
    ['label' => 'Sakit', 'value' => $report['summary']['sick'], 'icon' => 'bi-bandaid', 'tone' => 'danger'],
    ['label' => 'Izin', 'value' => $report['summary']['permission'], 'icon' => 'bi-file-earmark-check', 'tone' => 'info'],
    ['label' => 'Alpha', 'value' => $report['summary']['alpha'], 'icon' => 'bi-person-x', 'tone' => 'danger'],
    ['label' => 'Libur', 'value' => $report['summary']['off'], 'icon' => 'bi-house', 'tone' => 'secondary'],
    ['label' => 'Hari Libur', 'value' => $report['summary']['holiday'], 'icon' => 'bi-calendar-event', 'tone' => 'primary'],
    ['label' => 'Tidak Ada Data', 'value' => $report['summary']['no_record'], 'icon' => 'bi-dash-circle', 'tone' => 'secondary'],
];
?>
<section class="row g-3 mb-4 report-summary" aria-label="Ringkasan <?= e($report['period']['label']) ?>">
    <?php foreach ($reportSummaryCards as $card): ?>
        <div class="col-6 col-md-4 col-xl-2">
            <article class="summary-card h-100">
                <span class="summary-icon is-<?= e($card['tone']) ?>" aria-hidden="true"><i class="bi <?= e($card['icon']) ?>"></i></span>
                <div><p class="summary-value mb-1"><?= e($card['value']) ?></p><p class="summary-label mb-0"><?= e($card['label']) ?></p></div>
            </article>
        </div>
    <?php endforeach; ?>
</section>
