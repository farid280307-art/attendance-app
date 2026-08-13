<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\LeaveRequest;
use DateTimeInterface;
use PDO;
use Throwable;

final class LeaveService
{
    private LeaveRequest $leaveRequests;
    private LeaveAttachmentService $attachments;

    public function __construct(private PDO $pdo, ?LeaveAttachmentService $attachments = null)
    {
        $this->leaveRequests = new LeaveRequest($pdo);
        $this->attachments = $attachments ?? new LeaveAttachmentService();
    }

    /**
     * @param array{type:string,start_date:string,end_date:string,reason:string} $data
     * @param array<string, mixed> $upload
     * @param array{ip_address?:?string,user_agent?:?string} $context
     */
    public function create(
        int $userId,
        array $data,
        array $upload,
        DateTimeInterface $now,
        array $context = []
    ): int {
        $attachment = null;
        $this->pdo->beginTransaction();

        try {
            if ($this->leaveRequests->hasOverlappingRequest($userId, $data['start_date'], $data['end_date'])) {
                throw new LeaveException(
                    'Anda sudah memiliki pengajuan lain pada rentang tanggal tersebut.',
                    422,
                    ['date_range' => 'Anda sudah memiliki pengajuan lain pada rentang tanggal tersebut.']
                );
            }

            $attachment = $this->attachments->storeOptional($upload, $now);
            $requestId = $this->leaveRequests->create([
                'user_id' => $userId,
                'type' => $data['type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'reason' => $data['reason'],
                'attachment' => $attachment,
            ]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $this->attachments->delete($attachment);
            throw $exception;
        }

        $this->logActivity(
            $userId,
            'leave.created',
            sprintf(
                'Karyawan membuat pengajuan %s #%d.',
                ['leave' => 'cuti', 'sick' => 'sakit', 'permission' => 'izin'][$data['type']],
                $requestId
            ),
            $context
        );

        return $requestId;
    }

    /** @param array{ip_address?:?string,user_agent?:?string} $context */
    public function approve(
        int $requestId,
        int $reviewerId,
        DateTimeInterface $now,
        array $context = []
    ): bool {
        $updated = $this->leaveRequests->approvePending(
            $requestId,
            $reviewerId,
            $now->format('Y-m-d H:i:s')
        );

        if ($updated) {
            $this->logActivity(
                $reviewerId,
                'leave.approved',
                sprintf('Admin menyetujui pengajuan #%d.', $requestId),
                $context
            );
        }

        return $updated;
    }

    /** @param array{ip_address?:?string,user_agent?:?string} $context */
    public function reject(
        int $requestId,
        int $reviewerId,
        string $reason,
        DateTimeInterface $now,
        array $context = []
    ): bool {
        $updated = $this->leaveRequests->rejectPending(
            $requestId,
            $reviewerId,
            $now->format('Y-m-d H:i:s'),
            $reason
        );

        if ($updated) {
            $this->logActivity(
                $reviewerId,
                'leave.rejected',
                sprintf('Admin menolak pengajuan #%d.', $requestId),
                $context
            );
        }

        return $updated;
    }

    /** @param array{ip_address?:?string,user_agent?:?string} $context */
    private function logActivity(int $userId, string $action, string $description, array $context): void
    {
        try {
            (new ActivityLog($this->pdo))->create(
                $userId,
                $action,
                $description,
                $context['ip_address'] ?? null,
                $context['user_agent'] ?? null
            );
        } catch (Throwable $exception) {
            error_log('Activity log pengajuan gagal disimpan: ' . $exception->getMessage());
        }
    }
}
