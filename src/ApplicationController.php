<?php

class ApplicationController
{
    private ApplicationService $service;

    public function __construct(ApplicationService $service)
    {
        $this->service = $service;
    }

    public function list(array $currentUser): array
    {
        return $this->service->getApplicationsForUser($currentUser);
    }

    public function create(array $currentUser, array $post): ?string
    {
        if ($currentUser['role'] !== 'student') {
            return 'Only students can create applications.';
        }

        $title = trim($post['title'] ?? '');
        $description = trim($post['description'] ?? '');
        $type = trim($post['type'] ?? '');

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
            return null;
        }

        if ($id <= 0) {
            return null;
        }

        return $this->service->submitDraftForStudent($id, (int)$currentUser['id']);
    }

    public function approve(array $currentUser, int $id): void
    {
        if ($currentUser['role'] !== 'admin') {
            return;
        }

        if ($id <= 0) {
            return;
        }

        $this->service->approveSubmittedByAdmin($id);
    }

    public function getEditData(array $currentUser, int $id): ?array
    {
        if ($currentUser['role'] !== 'student') {
            return null;
        }

        if ($id <= 0) {
            return null;
        }

        return $this->service->getDraftForEditing($id, (int)$currentUser['id']);
    }

    public function updateEdit(array $currentUser, int $id, array $post): ?string
    {
        if ($currentUser['role'] !== 'student') {
            return 'Not allowed.';
        }

        $title = trim($post['title'] ?? '');
        $description = trim($post['description'] ?? '');
        $type = trim($post['type'] ?? '');

        return $this->service->updateDraftForStudent(
            $id,
            (int)$currentUser['id'],
            $title,
            $description,
            $type
        );
    }

    public function getRejectData(array $currentUser, int $id): ?array
    {
        if ($currentUser['role'] !== 'admin') {
            return null;
        }

        if ($id <= 0) {
            return null;
        }

        return $this->service->getSubmittedForRejection($id);
    }

    public function reject(array $currentUser, int $id, array $post): ?string
    {
        if ($currentUser['role'] !== 'admin') {
            return 'Not allowed.';
        }

        $comment = trim($post['rejection_comment'] ?? '');

        return $this->service->rejectWithComment($id, $comment);
    }
}
