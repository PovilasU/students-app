<?php

class ApplicationService
{
    private ApplicationRepository $repository;

    public function __construct(ApplicationRepository $repository)
    {
        $this->repository = $repository;
    }

    public function submitDraftForStudent(int $id, int $studentId): ?string
    {
        $app = $this->repository->findDraftForStudent($id, $studentId);
        if (!$app) {
            return null;
        }

        $submittedCount = $this->repository->countSubmittedByTypeForStudent($studentId, $app['type']);
        if ($submittedCount >= 3) {
            return 'Jau turite 3 pateiktas šio tipo paraiškas.';
        }

        $this->repository->submitDraft($app['id']);
        return null;
    }

    public function createDraftForStudent(int $studentId, string $title, string $description, string $type): ?string
    {
        if ($title === '' || $description === '' || $type === '') {
            return 'Please fill all fields.';
        }

        $this->repository->createDraft($studentId, $title, $description, $type);
        return null;
    }

    public function getDraftForEditing(int $id, int $studentId): ?array
    {
        return $this->repository->findDraftForStudent($id, $studentId);
    }

    public function updateDraftForStudent(int $id, int $studentId, string $title, string $description, string $type): ?string
    {
        if ($title === '' || $description === '' || $type === '') {
            return 'Please fill all fields.';
        }

        $ok = $this->repository->updateDraft($id, $studentId, $title, $description, $type);
        if (!$ok) {
            return 'Application not found or not editable.';
        }

        return null;
    }

    public function approveSubmittedByAdmin(int $id): void
    {
        $this->repository->approveSubmitted($id);
    }

    public function getSubmittedForRejection(int $id): ?array
    {
        return $this->repository->findSubmittedById($id);
    }

    public function rejectWithComment(int $id, string $comment): ?string
    {
        if ($comment === '') {
            return 'Please enter rejection comment.';
        }

        $ok = $this->repository->rejectSubmittedWithComment($id, $comment);
        if (!$ok) {
            return 'Application not found or not in submitted state.';
        }

        return null;
    }

    public function getApplicationsForUser(array $currentUser): array
    {
        if ($currentUser['role'] === 'student') {
            return $this->repository->getStudentApplications((int)$currentUser['id']);
        }

        return $this->repository->getAllApplications();
    }
}
