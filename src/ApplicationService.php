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

    public function approveSubmittedByAdmin(int $id): void
    {
        $this->repository->approveSubmitted($id);
    }

    public function getApplicationsForUser(array $currentUser): array
    {
        if ($currentUser['role'] === 'student') {
            return $this->repository->getStudentApplications((int)$currentUser['id']);
        }

        return $this->repository->getAllApplications();
    }
}
