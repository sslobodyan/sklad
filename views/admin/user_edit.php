<div class="page-header">
    <div>
        <h1 class="page-title"><?= isset($user) && $user ? 'Редагування' : 'Новий' ?> користувач</h1>
        <p class="page-subtitle"><?= isset($user) && $user ? htmlspecialchars($user['nc_user']) : 'Додавання нового користувача' ?></p>
    </div>
    <div class="header-buttons">
        <a href="<?= $basePath ?>/adminUsers" class="btn btn-secondary">Назад</a>
    </div>
</div>

<div class="card card-stretch">
    <div class="table-scroll" style="flex: 1; overflow: auto; min-height: 0;">
        <form method="post" action="<?= $basePath ?>/adminUsers/save" id="userForm">
            <?php if (isset($user) && $user): ?>
            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
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
                        </td>
                    </tr>
                    
                    <!-- Склади -->
                    <tr style="vertical-align: top;">
                        <td style="padding-top: 12px;"><label class="form-label">Дозволені склади</label></td>
                        <td style="padding-top: 12px;">
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <div style="flex: 1; min-width: 200px;">
                                    <div class="form-hint" style="margin-bottom: 5px;">Доступні</div>
                                    <select id="available_warehouses" size="8" class="form-select" style="width: 100%;" ondblclick="moveItem('available_warehouses', 'selected_warehouses')">
                                        <?php foreach ($warehouses as $w): ?>
                                        <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div style="display: flex; flex-direction: column; justify-content: center; gap: 8px;">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="moveItem('available_warehouses', 'selected_warehouses')">→</button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="moveAllItems('available_warehouses', 'selected_warehouses')">→→</button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="moveAllItems('selected_warehouses', 'available_warehouses')">←←</button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="moveItem('selected_warehouses', 'available_warehouses')">←</button>
                                </div>
                                <div style="flex: 1; min-width: 200px;">
                                    <div class="form-hint" style="margin-bottom: 5px;">Вибрані</div>
                                    <select name="allowed_warehouses[]" id="selected_warehouses" size="8" multiple class="form-select" style="width: 100%;" ondblclick="moveItem('selected_warehouses', 'available_warehouses')">
                                        <?php if (isset($user) && is_array($user['allowed_warehouses'])): ?>
                                        <?php foreach ($user['allowed_warehouses'] as $id): ?>
                                        <?php 
                                        $wh = array_filter($warehouses, function($w) use ($id) { return $w['id'] == $id; });
                                        $wh = array_values($wh);
                                        if (!empty($wh)): ?>
                                        <option value="<?= $wh[0]['id'] ?>" selected><?= htmlspecialchars($wh[0]['name']) ?></option>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Матеріали -->
                    <tr style="vertical-align: top;">
                        <td style="padding-top: 12px;"><label class="form-label">Дозволені матеріали</label></td>
                        <td style="padding-top: 12px;">
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <div style="flex: 1; min-width: 200px;">
                                    <div class="form-hint" style="margin-bottom: 5px;">Доступні</div>
                                    <select id="available_materials" size="8" class="form-select" style="width: 100%;" ondblclick="moveItem('available_materials', 'selected_materials')">
                                        <?php foreach ($materials as $m): ?>
                                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div style="display: flex; flex-direction: column; justify-content: center; gap: 8px;">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="moveItem('available_materials', 'selected_materials')">→</button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="moveAllItems('available_materials', 'selected_materials')">→→</button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="moveAllItems('selected_materials', 'available_materials')">←←</button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="moveItem('selected_materials', 'available_materials')">←</button>
                                </div>
                                <div style="flex: 1; min-width: 200px;">
                                    <div class="form-hint" style="margin-bottom: 5px;">Вибрані</div>
                                    <select name="allowed_materials[]" id="selected_materials" size="8" multiple class="form-select" style="width: 100%;" ondblclick="moveItem('selected_materials', 'available_materials')">
                                        <?php if (isset($user) && is_array($user['allowed_materials'])): ?>
                                        <?php foreach ($user['allowed_materials'] as $id): ?>
                                        <?php 
                                        $mat = array_filter($materials, function($m) use ($id) { return $m['id'] == $id; });
                                        $mat = array_values($mat);
                                        if (!empty($mat)): ?>
                                        <option value="<?= $mat[0]['id'] ?>" selected><?= htmlspecialchars($mat[0]['name']) ?></option>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Типи ресурсів -->
                    <tr style="vertical-align: top;">
                        <td style="padding-top: 12px;"><label class="form-label">Дозволені типи ресурсів</label></td>
                        <td style="padding-top: 12px;">
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <div style="flex: 1; min-width: 200px;">
                                    <div class="form-hint" style="margin-bottom: 5px;">Доступні</div>
                                    <select id="available_types" size="8" class="form-select" style="width: 100%;" ondblclick="moveItem('available_types', 'selected_types')">
                                        <?php foreach ($resourceTypes as $rt): ?>
                                        <option value="<?= $rt['id'] ?>"><?= htmlspecialchars($rt['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div style="display: flex; flex-direction: column; justify-content: center; gap: 8px;">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="moveItem('available_types', 'selected_types')">→</button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="moveAllItems('available_types', 'selected_types')">→→</button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="moveAllItems('selected_types', 'available_types')">←←</button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="moveItem('selected_types', 'available_types')">←</button>
                                </div>
                                <div style="flex: 1; min-width: 200px;">
                                    <div class="form-hint" style="margin-bottom: 5px;">Вибрані</div>
                                    <select name="allowed_resource_types[]" id="selected_types" size="8" multiple class="form-select" style="width: 100%;" ondblclick="moveItem('selected_types', 'available_types')">
                                        <?php if (isset($user) && is_array($user['allowed_resource_types'])): ?>
                                        <?php foreach ($user['allowed_resource_types'] as $id): ?>
                                        <?php 
                                        $rt = array_filter($resourceTypes, function($r) use ($id) { return $r['id'] == $id; });
                                        $rt = array_values($rt);
                                        if (!empty($rt)): ?>
                                        <option value="<?= $rt[0]['id'] ?>" selected><?= htmlspecialchars($rt[0]['name']) ?></option>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </td>
                    </tr>
                    
                    <tr style="vertical-align: top;">
                        <td style="padding-top: 12px;"><label class="form-label">Додаткові права</label></td>
                        <td style="padding-top: 12px;">
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

<!-- Поля для локальної авторизації -->
<tr style="vertical-align: top;">
    <td style="padding-top: 12px;"><label class="form-label">Локальна авторизація</label></td>
    <td style="padding-top: 12px;">
        <label class="form-checkbox">
            <input type="checkbox" name="is_local" value="1" <?= isset($user) && $user['is_local'] ? 'checked' : '' ?>>
            <span>Дозволити вхід через форму (локально)</span>
        </label>
    </td>
</tr>
<tr style="vertical-align: top;">
    <td style="padding-top: 12px;"><label class="form-label">Пароль</label></td>
    <td style="padding-top: 12px;">
        <input type="password" name="password" class="form-input" style="width: 250px;" placeholder="Новий пароль (залиште порожнім, щоб не змінювати)">
        <div class="form-hint">Заповніть тільки якщо потрібно змінити пароль</div>
    </td>
</tr>
<tr style="vertical-align: top;">
    <td style="padding-top: 12px;"><label class="form-label">Email</label></td>
    <td style="padding-top: 12px;">
        <input type="email" name="email" class="form-input" style="width: 250px;" value="<?= isset($user) ? htmlspecialchars($user['email'] ?? '') : '' ?>">
    </td>
</tr>

                </tbody>
            </table>
            
            <div class="card-footer-info" style="position: sticky; bottom: 0; background: #fafbfc; margin-top: 10px;">
                <button type="submit" class="btn btn-primary">Зберегти</button>
                <a href="<?= $basePath ?>/adminUsers" class="btn btn-secondary">Скасувати</a>
            </div>
        </form>
    </div>
</div>

<script>
function moveItem(fromId, toId) {
    var from = document.getElementById(fromId);
    var to = document.getElementById(toId);
    for (var i = 0; i < from.options.length; i++) {
        if (from.options[i].selected) {
            var option = document.createElement("option");
            option.value = from.options[i].value;
            option.text = from.options[i].text;
            option.selected = true;
            to.appendChild(option);
            from.remove(i);
            i--;
        }
    }
    sortSelect(to);
}

function moveAllItems(fromId, toId) {
    var from = document.getElementById(fromId);
    var to = document.getElementById(toId);
    for (var i = 0; i < from.options.length; i++) {
        var option = document.createElement("option");
        option.value = from.options[i].value;
        option.text = from.options[i].text;
        option.selected = true;
        to.appendChild(option);
    }
    from.innerHTML = '';
    sortSelect(to);
}

function sortSelect(select) {
    var options = Array.from(select.options);
    options.sort(function(a, b) {
        return a.text.localeCompare(b.text);
    });
    select.innerHTML = '';
    options.forEach(function(opt) {
        select.appendChild(opt);
    });
}

// Виділяємо всі вибрані елементи перед відправкою форми
document.getElementById('userForm').addEventListener('submit', function() {
    var selects = ['selected_warehouses', 'selected_materials', 'selected_types'];
    for (var i = 0; i < selects.length; i++) {
        var sel = document.getElementById(selects[i]);
        for (var j = 0; j < sel.options.length; j++) {
            sel.options[j].selected = true;
        }
    }
});
</script>