<?php

declare(strict_types=1);
?>
<section class="dashboard-panel" aria-labelledby="daily-report-title">
    <div class="panel-heading">
        <div><p class="panel-kicker mb-1"><?= e($report['period']['label']) ?></p><h2 class="panel-title mb-0" id="daily-report-title">Detail Harian</h2></div>
        <span class="panel-count"><?= e(count($report['days'])) ?> hari</span>
    </div>

    <div class="table-responsive d-none d-md-block">
        <table class="table app-table report-table align-middle mb-0">
            <thead><tr><th>Tanggal</th><th>Status</th><th>Jam Masuk</th><th>Jam Pulang</th><th>Keterangan</th></tr></thead>
            <tbody>
            <?php foreach ($report['days'] as $day): ?>
                <tr class="<?= $day['status'] === 'future' ? 'report-future-row' : '' ?>">
                    <td><strong><?= e(indonesian_short_date($day['date'])) ?></strong><small class="report-day-name"><?= e($day['day_name']) ?></small></td>
                    <td><span class="badge <?= e(report_status_class($day['status'])) ?>"><?= e($day['status_label']) ?></span></td>
                    <td><?= e(format_attendance_time($day['check_in'])) ?></td>
                    <td><?= e(format_attendance_time($day['check_out'])) ?></td>
                    <td>
                        <span><?= e(report_day_description($day)) ?></span>
                        <?php if ($day['work_location_name'] !== null): ?><small class="report-location"><i class="bi bi-geo-alt" aria-hidden="true"></i><?= e($day['work_location_name']) ?></small><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mobile-record-list d-md-none report-mobile-list">
        <?php foreach ($report['days'] as $day): ?>
            <article class="mobile-record<?= $day['status'] === 'future' ? ' report-future-row' : '' ?>">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div><h3><?= e(indonesian_short_date($day['date'])) ?></h3><p><?= e($day['day_name']) ?></p></div>
                    <span class="badge <?= e(report_status_class($day['status'])) ?>"><?= e($day['status_label']) ?></span>
                </div>
                <div class="mobile-record-meta">
                    <span><small>Masuk</small><strong><?= e(format_attendance_time($day['check_in'])) ?></strong></span>
                    <span><small>Pulang</small><strong><?= e(format_attendance_time($day['check_out'])) ?></strong></span>
                </div>
                <p class="report-mobile-description mb-0"><?= e(report_day_description($day)) ?><?php if ($day['work_location_name'] !== null): ?> · <?= e($day['work_location_name']) ?><?php endif; ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
