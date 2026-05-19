<div class="page-header">
    <div>
        <h1 class="page-title">Бекап бази даних</h1>
        <p class="page-subtitle">Створення резервної копії</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= $basePath ?>/admin/dobackup">
            <div class="alert alert-info" style="background: #e3f2fd; border: 1px solid #1565c0; padding: 12px; margin-bottom: 20px; border-radius: 6px;">
                <strong>📦 Що включається в бекап:</strong>
                <ul style="margin: 8px 0 0 20px;">
                    <li>Всі таблиці бази даних з даними</li>
                    <li>Структура таблиць (індекси, ключі)</li>
                    <li>Тригери та збережені процедури</li>
                    <li>Зовнішні ключі (foreign keys)</li>
                </ul>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label" style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="include_data" value="1" checked disabled>
                    <span>Включити дані (завжди так)</span>
                </label>
                <div class="form-hint">Бекап завжди включає повну структуру та дані</div>
            </div>
            
            <div class="modal-footer" style="margin-top: 20px;">
                <a href="<?= $basePath ?>/admin/backup" class="btn btn-secondary" onclick="return false;">Назад</a>
                <button type="submit" class="btn btn-primary">Створити бекап</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Інструкція</h3>
    </div>
    <div class="card-body">
        <ul class="import-hints">
            <li>Бекап створюється через <strong>mysqldump</strong> з усіма тригерами та процедурами</li>
            <li>Файл стискається в ZIP архів для зменшення розміру</li>
            <li>Рекомендується робити бекап перед великими змінами</li>
            <li>Для відновлення використовуйте розділ "Restore DB"</li>
        </ul>
    </div>
</div>