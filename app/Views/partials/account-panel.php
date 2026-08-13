<?php

declare(strict_types=1);

$accountName = (string) ($user['name'] ?? 'User');
$accountInitial = function_exists('mb_substr')
    ? mb_substr($accountName, 0, 1, 'UTF-8')
    : substr($accountName, 0, 1);
$accountRole = ($user['role'] ?? null) === 'admin' ? 'Administrator' : 'Karyawan';
?>
<div class="app-account">
    <div class="app-account-profile">
        <span class="app-account-avatar" aria-hidden="true"><?= e(strtoupper($accountInitial)) ?></span>
        <span class="app-account-copy">
            <strong><?= e($accountName) ?></strong>
            <small><?= e($accountRole) ?></small>
        </span>
    </div>
    <form method="POST" action="<?= e(url('/logout')) ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn app-logout-button w-100">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            <span>Logout</span>
        </button>
    </form>
</div>
