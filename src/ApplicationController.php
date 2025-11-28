<?php

class ApplicationController
{
    public function __construct(private ApplicationService $service)
    {
    }

    public function list(array $currentUser): array
    {
        return $this->service->getApplicationsForUser($currentUser);
    }

    public function create(array $currentUser, array $data): ?string
    {
        if ($currentUser['role'] !== 'student') {
            return 'Tik studentai gali kurti paraiškas.';
        }

        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $type = $data['type'] ?? '';

        return $this->service->createDraftForStudent(
            (int)$currentUser['id'],
            $title,
            $description,
            $type
        );
    }

    public function submit(array $currentUser, int $id): ?string
    {
        if ($currentUser['role'] !== 'student') {
            return 'Tik studentai gali pateikti paraiškas.';
        }

        return $this->service->submitDraftForStudent($id, (int)$currentUser['id']);
    }

    public function approve(array $currentUser, int $id): void
    {
        if ($currentUser['role'] !== 'admin') {
            return;
        }

        $this->service->approveSubmittedByAdmin($id);
    }

    public function reject(array $currentUser, int $id, array $data): ?string
    {
        if ($currentUser['role'] !== 'admin') {
            return 'Tik administratoriai gali atmesti paraiškas.';
        }

        $comment = $data['rejection_comment'] ?? '';

        return $this->service->rejectWithComment($id, $comment);
    }

    public function getRejectData(array $currentUser, int $id): ?array
    {
        if ($currentUser['role'] !== 'admin') {
            return null;
        }

        return $this->service->getApplicationForAdmin($id);
    }

    public function getEditData(array $currentUser, int $id): ?array
    {
        return $this->service->getApplicationForEdit($id, $currentUser);
    }

    public function updateEdit(array $currentUser, int $id, array $data): ?string
    {
        if ($currentUser['role'] !== 'student') {
            return 'Tik studentai gali redaguoti paraiškas.';
        }

        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $type = $data['type'] ?? '';

        return $this->service->updateDraftForStudent(
            $id,
            $currentUser,
            $title,
            $description,
            $type
        );
    }
}
