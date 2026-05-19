<div class="page-header">
    <div>
        <h1 class="page-title">Відновлення бази даних</h1>
        <p class="page-subtitle">Завантаження резервної копії</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= $basePath ?>/admin/dorestore" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">Файл бекапу <span class="required">*</span></label>
                <input type="file" name="backup_file" class="form-input" accept=".sql,.zip" required>
                <div class="form-hint">Підтримуються файли .sql та .zip (з .sql всередині)</div>
            </div>
            
            <div class="alert alert-warning" style="background: #fff3e0; border: 1px solid #f57c00; padding: 12px; margin: 16px 0; border-radius: 6px;">
                <strong>⚠️ Увага!</strong>
                <ul style="margin: 8px 0 0 20px;">
                    <li>Відновлення ПОВНІСТЮ замінить поточну базу даних</li>
                    <li>Всі поточні дані будуть втрачені</li>
                    <li>Рекомендується зробити бекап перед відновленням</li>
                    <li>Процес може зайняти кілька хвилин</li>
                </ul>
            </div>
            
            <div class="modal-footer" style="margin-top: 20px;">
                <a href="<?= $basePath ?>/admin/restore" class="btn btn-secondary" onclick="return false;">Назад</a>
                <button type="submit" class="btn btn-danger" onclick="return confirm('Відновити базу даних? Всі поточні дані будуть втрачені!')">Відновити</button>
            </div>
        </form>
    </div>
</div>