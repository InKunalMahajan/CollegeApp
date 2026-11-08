<?php

namespace App\Services;

use App\Models\SchoolAuditEvent;

class SchoolAuditTrail
{
    public function record(?int $schoolId, int $actorUserId, string $action, string $subjectType, int $subjectId, array $context = []): void
    {
        SchoolAuditEvent::create([
            'school_id' => $schoolId,
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'context' => $context,
        ]);
    }
}
