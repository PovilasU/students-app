<?php

class ApplicationService
{
    public function __construct(private ApplicationRepository $repository)
    {
    }

    public function createDraftForStudent(
        int $studentId,
        string $title,
        string $description,
        string $type
    ): ?string {
        $title = trim($title);
        $description = trim($description);
        $type = trim($type);

        if ($title === '' || $description === '' || $type === '') {
            return 'Visi laukai yra privalomi.';
        }

        if ($this->repository->countRecentDraftsForStudent($studentId, 60) >= 5) {
            return 'Per daug bandymų sukurti paraiškas. Palaukite minutę ir bandykite vėl.';
        }

        $this->repository->insertDraft($studentId, $title, $description, $type);

        return null;
    }

    public function submitDraftForStudent(int $id, int $studentId): ?string
    {
        $app = $this->repository->findById($id);

        if (!$app) {
            return 'Paraiška nerasta.';
        }

        if ((int)$app['student_id'] !== $studentId) {
            return 'Negalite pateikti kito studento paraiškos.';
        }

        if ($app['status'] !== 'draft') {
            return 'Pateikti galima tik ruošinio būsenos paraiškas.';
        }

        $count = $this->repository->countSubmittedByTypeForStudent($studentId, $app['type']);
        if ($count >= 3) {
            return 'Jau turite 3 pateiktas šio tipo paraiškas.';
        }

        $this->repository->updateStatus($id, 'submitted');

        return null;
    }

    public function approveSubmittedByAdmin(int $id): void
    {
        $app = $this->repository->findById($id);
        if (!$app) {
            return;
        }

        if ($app['status'] !== 'submitted') {
            return;
        }

        $this->repository->updateStatus($id, 'approved');
    }

    public function rejectWithComment(int $id, string $comment): ?string
    {
        $comment = trim($comment);
        if ($comment === '') {
            return 'Prašome įrašyti atmetimo komentarą.';
        }

        $app = $this->repository->findById($id);
        if (!$app) {
            return 'Paraiška nerasta.';
        }

        if ($app['status'] !== 'submitted') {
            return 'Atmesti galima tik pateiktas paraiškas.';
        }

        $this->repository->updateStatusAndComment($id, 'rejected', $comment);

        return null;
    }

    public function getApplicationsForUser(array $user): array
    {
        if ($user['role'] === 'student') {
            return $this->repository->findAllForStudent((int)$user['id']);
        }

        return $this->repository->findAll();
    }

    public function getApplicationForEdit(int $id, array $user): ?array
    {
        $app = $this->repository->findById($id);
        if (!$app) {
            return null;
        }

        if ($user['role'] !== 'student' || (int)$app['student_id'] !== (int)$user['id']) {
            return null;
        }

        if ($app['status'] !== 'draft') {
            return null;
        }

        return $app;
    }

    public function updateDraftForStudent(
        int $id,
        array $user,
        string $title,
        string $description,
        string $type
    ): ?string {
        $app = $this->getApplicationForEdit($id, $user);
        if (!$app) {
            return 'Negalite redaguoti šios paraiškos.';
        }

        $title = trim($title);
        $description = trim($description);
        $type = trim($type);

        if ($title === '' || $description === '' || $type === '') {
            return 'Visi laukai yra privalomi.';
        }

        $this->repository->updateDraft($id, $title, $description, $type);

        return null;
    }

    public function getApplicationForAdmin(int $id): ?array
    {
        $app = $this->repository->findById($id);

        if (!$app) {
            return null;
        }

        return $app;
    }
}
