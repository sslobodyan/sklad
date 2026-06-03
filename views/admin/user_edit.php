<div class="page-header">
    <div>
        <h1 class="page-title"><?= isset($user) && $user ? 'Редагування' : 'Новий' ?> користувач</h1>
        <p class="page-subtitle"><?= isset($user) && $user ? htmlspecialchars($user['nc_user']) : 'Додавання нового користувача' ?></p>
    </div>
    <div class="header-buttons">
        <a href="<?= $basePath ?>/admin/users" class="btn btn-secondary">Назад</a>
    </div>
</div>

<div class="card">
    <div class="table-scroll">
        <form method="post" action="<?= $basePath ?>/admin/users/save">
            <?php if (isset($user) && $user): ?>
            <input type="hidden" name="nc_user" value="<?= htmlspecialchars($user['nc_user']) ?>">
            <?php endif; ?>
            
            <table class="data-table">
                <tbody>
                    <?php if (!isset($user) || !$user): ?>
                    <tr>
                        <td style="width: 200px"><label class="form-label">Логін (NC_USER)</label></td>
                        <td>
                            <input type="text" name="nc_user" class="form-input" required>
                            <div class="form-hint">Логін користувача з Nextcloud</div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td><label class="form-label">Роль</label></td>
                        <td>
                            <select name="role" class="form-select">
                                <option value="manager" <?= isset($user) && $user['role'] == 'manager' ? 'selected' : '' ?>>Менеджер</option>
                                <option value="viewer" <?= isset($user) && $user['role'] == 'viewer' ? 'selected' : '' ?>>Спостерігач</option>
                                <option value="fuel" <?= isset($user) && $user['role'] == 'fuel' ? 'selected' : '' ?>>Заправник</option>
                            </select>
                            <div class="form-hint">Менеджер: повний доступ до документів та довідників. Спостерігач: тільки перегляд. Заправник: тільки сторінка заправки.</div>
                        </td>
                    </tr>
                    <tr>
                        <td><label class="form-label">Дозволені склади (ID через кому)</label></td>
                        <td>
                            <input type="text" name="allowed_warehouses" class="form-input" value="<?= isset($user) && is_array($user['allowed_warehouses']) ? implode(',', $user['allowed_warehouses']) : '' ?>">
                            <div class="form-hint">Порожньо = всі склади. Наприклад: 1,3,5</div>
                        </td>
                    </tr>
                    <tr>
                        <td><label class="form-label">Дозволені матеріали (ID через кому)</label></td>
                        <td>
                            <input type="text" name="allowed_materials" class="form-input" value="<?= isset($user) && is_array($user['allowed_materials']) ? implode(',', $user['allowed_materials']) : '' ?>">
                            <div class="form-hint">Порожньо = всі матеріали. Наприклад: 2,7,12</div>
                        </td>
                    </tr>
                    <tr>
                        <td><label class="form-label">Дозволені типи ресурсів (ID через кому)</label></td>
                        <td>
                            <input type="text" name="allowed_resource_types" class="form-input" value="<?= isset($user) && is_array($user['allowed_resource_types']) ? implode(',', $user['allowed_resource_types']) : '' ?>">
                            <div class="form-hint">Порожньо = всі типи. Наприклад: 1,3</div>
                        </td>
                    </tr>
                    <tr>
                        <td><label class="form-label">Додаткові права</label></td>
                        <td>
                            <label class="form-checkbox">
                                <input type="checkbox" name="can_export" value="1" <?= isset($user) && $user['can_export'] ? 'checked' : '' ?>>
                                <span>Може експортувати</span>
                            </label>
                            <label class="form-checkbox">
                                <input type="checkbox" name="can_import" value="1" <?= isset($user) && $user['can_import'] ? 'checked' : '' ?>>
                                <span>Може імпортувати</span>
                            </label>
                            <label class="form-checkbox">
                                <input type="checkbox" name="can_edit_rates" value="1" <?= isset($user) && $user['can_edit_rates'] ? 'checked' : '' ?>>
                                <span>Може редагувати норми списання</span>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="card-footer-info">
                <button type="submit" class="btn btn-primary">Зберегти</button>
                <a href="<?= $basePath ?>/admin/users" class="btn btn-secondary">Скасувати</a>
            </div>
        </form>
    </div>
</div>