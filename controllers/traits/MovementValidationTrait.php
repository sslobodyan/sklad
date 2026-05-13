<?php
/**
 * Trait MovementValidationTrait
 * Валідація даних руху
 */
trait MovementValidationTrait
{
    private function getFormData(): array
    {
        return [
            'movement_date' => $this->post('movement_date'),
            'warehouse_from_id' => $this->post('warehouse_from_id') ?: null,
            'warehouse_to_id' => $this->post('warehouse_to_id') ?: null,
            'material_id' => $this->post('material_id'),
            'quantity' => (float)$this->post('quantity'),
            'note' => $this->post('note', ''),
        ];
    }

    private function validateData(array $data): ?string
    {
        if (empty($data['movement_date'])) return 'Вкажіть дату';
        if (empty($data['material_id'])) return 'Оберіть матеріал';
        if ($data['quantity'] <= 0) return 'Кількість повинна бути > 0';
        if (empty($data['warehouse_from_id']) && empty($data['warehouse_to_id'])) {
            return 'Вкажіть хоча б один склад';
        }
        if ($data['warehouse_from_id'] && $data['warehouse_to_id'] && $data['warehouse_from_id'] == $data['warehouse_to_id']) {
            return 'Склади не повинні збігатися';
        }
        return null;
    }

    private function respondWith(bool $success, string $message, array $extra = []): void
    {
        if ($this->isAjax()) {
            $data = array_merge(['success' => $success], $extra);
            $data[$success ? 'message' : 'error'] = $message;
            $this->json($data);
        } else {
            $this->flash($success ? 'success' : 'error', $message);
            $this->redirect('movements');
        }
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}