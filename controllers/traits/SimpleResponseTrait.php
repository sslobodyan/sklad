<?php
/**
 * Trait SimpleResponseTrait
 * Відповіді для спрощеного режиму
 */
trait SimpleResponseTrait
{
    protected function renderSimple(string $view, array $data = []): void
    {
        extract($data);
        require ROOT_PATH . '/views/' . $view . '.php';
    }

    protected function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function renderSimpleError(string $error): void
    {
        $this->renderSimple('simple/error', ['error' => $error]);
    }
}