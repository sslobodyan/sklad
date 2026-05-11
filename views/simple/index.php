<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Заправка — <?= htmlspecialchars($warehouse['name']) ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        :root {
            --blue: #0082c9;
            --blue-dark: #006aa3;
            --green: #2e7d32;
            --green-light: #e8f5e9;
            --red: #c62828;
            --red-light: #ffebee;
            --gray: #f5f5f5;
            --border: #ddd;
            --text: #333;
            --text-light: #666;
        }
        
        html, body {
            height: 100%;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 16px;
            line-height: 1.4;
            color: var(--text);
            background: var(--gray);
            padding: 12px;
            padding-bottom: 100px;
        }
        
        .header {
            background: var(--blue);
            color: white;
            padding: 16px;
            margin: -12px -12px 16px;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group:last-child {
            margin-bottom: 0;
        }
        
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text);
        }
        
        input[type="date"],
        input[type="number"],
        select {
            width: 100%;
            padding: 14px 16px;
            font-size: 16px;
            border: 2px solid var(--border);
            border-radius: 10px;
            background: white;
            color: var(--text);
            -webkit-appearance: none;
            appearance: none;
        }
        
        input:focus,
        select:focus {
            outline: none;
            border-color: var(--blue);
        }
        
        select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 40px;
        }
        
        .btn-row {
            display: flex;
            gap: 12px;
        }
        
        .btn {
            flex: 1;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn:active {
            transform: scale(0.98);
        }
        
        .btn-incoming {
            background: var(--green-light);
            color: var(--green);
            border: 2px solid var(--green);
        }
        
        .btn-incoming.active {
            background: var(--green);
            color: white;
        }
        
        .btn-outgoing {
            background: var(--red-light);
            color: var(--red);
            border: 2px solid var(--red);
        }
        
        .btn-outgoing.active {
            background: var(--red);
            color: white;
        }
        
        .btn-save {
            background: var(--blue);
            color: white;
            margin-top: 12px;
        }
        
        .btn-save:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .action-panel {
            display: none;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }
        
        .action-panel.visible {
            display: block;
        }
        
        .movements-title {
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .movements-count {
            font-weight: normal;
            color: var(--text-light);
            font-size: 14px;
        }
        
        .movement-item {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .movement-item:last-child {
            margin-bottom: 0;
        }
        
        .movement-incoming {
            background: var(--green-light);
            border-left: 4px solid var(--green);
        }
        
        .movement-outgoing {
            background: var(--red-light);
            border-left: 4px solid var(--red);
        }
        
        .movement-material {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .movement-details {
            color: var(--text-light);
            display: flex;
            justify-content: space-between;
        }
        
        .movement-qty {
            font-weight: 600;
            color: var(--text);
        }
        
        .empty-message {
            text-align: center;
            color: var(--text-light);
            padding: 24px;
            font-size: 14px;
        }
        
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 14px 24px;
            border-radius: 8px;
            font-size: 14px;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s;
            max-width: calc(100% - 40px);
            text-align: center;
        }
        
        .toast.visible {
            opacity: 1;
        }
        
        .toast.error {
            background: var(--red);
        }
        
        .toast.success {
            background: var(--green);
        }
        
        .loading {
            pointer-events: none;
            opacity: 0.6;
        }
        
        @media (min-width: 500px) {
            body {
                max-width: 480px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>

<div class="header">
    ⛽ <?= htmlspecialchars($warehouse['name']) ?>
</div>

<div class="card">
    <div class="form-group">
        <label for="date">📅 Дата</label>
        <input type="date" id="date" value="<?= htmlspecialchars($date) ?>">
    </div>
    
    <div class="form-group">
        <label for="material">📦 Матеріал</label>
        <select id="material">
            <option value="">— Оберіть матеріал —</option>
            <?php foreach ($materials as $m): ?>
                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="form-group">
        <label>Тип операції</label>
        <div class="btn-row">
            <button type="button" class="btn btn-incoming" id="btnIncoming">
                ⬇️ Надійшло
            </button>
            <button type="button" class="btn btn-outgoing" id="btnOutgoing">
                ⬆️ Видано
            </button>
        </div>
    </div>
    
    <!-- Панель для надходження -->
    <div class="action-panel" id="panelIncoming">
        <div class="form-group">
            <label for="qtyIncoming">Кількість</label>
            <input type="number" id="qtyIncoming" inputmode="decimal" step="0.01" min="0" placeholder="0">
        </div>
        <button type="button" class="btn btn-save" id="saveIncoming">
            💾 Зберегти надходження
        </button>
    </div>
    
    <!-- Панель для видачі -->
    <div class="action-panel" id="panelOutgoing">
        <div class="form-group">
            <label for="targetWarehouse">Куди видати</label>
            <select id="targetWarehouse">
                <option value="">— Оберіть склад —</option>
                <?php foreach ($otherWarehouses as $w): ?>
                    <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="qtyOutgoing">Кількість</label>
            <input type="number" id="qtyOutgoing" inputmode="decimal" step="0.01" min="0" placeholder="0">
        </div>
        <button type="button" class="btn btn-save" id="saveOutgoing">
            💾 Зберегти видачу
        </button>
    </div>
</div>

<div class="card">
    <div class="movements-title">
        <span>📋 Рухи за <span id="movementsDate"><?= date('d.m.Y', strtotime($date)) ?></span></span>
        <span class="movements-count" id="movementsCount">(<?= count($movements) ?>)</span>
    </div>
    
    <div id="movementsList">
        <?php if (empty($movements)): ?>
            <div class="empty-message">Немає рухів за цю дату</div>
        <?php else: ?>
            <?php foreach ($movements as $m): 
                $isIncoming = $m['warehouse_to_id'] == $warehouse['id'];
                $direction = $isIncoming ? 'incoming' : 'outgoing';
                $counterpart = $isIncoming 
                    ? ($m['warehouse_from_name'] ?? 'Ззовні')
                    : ($m['warehouse_to_name'] ?? 'Списано');
            ?>
                <div class="movement-item movement-<?= $direction ?>">
                    <div class="movement-material"><?= htmlspecialchars($m['material_name']) ?></div>
                    <div class="movement-details">
                        <span><?= $isIncoming ? '← ' . $counterpart : '→ ' . $counterpart ?></span>
                        <span class="movement-qty"><?= rtrim(rtrim(number_format($m['quantity'], 2, '.', ''), '0'), '.') ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function() {
    const warehouseId = <?= $warehouse['id'] ?>;
    const basePath = '<?= BASE_PATH ?>';
    
    const dateInput = document.getElementById('date');
    const materialSelect = document.getElementById('material');
    const btnIncoming = document.getElementById('btnIncoming');
    const btnOutgoing = document.getElementById('btnOutgoing');
    const panelIncoming = document.getElementById('panelIncoming');
    const panelOutgoing = document.getElementById('panelOutgoing');
    const qtyIncoming = document.getElementById('qtyIncoming');
    const qtyOutgoing = document.getElementById('qtyOutgoing');
    const targetWarehouse = document.getElementById('targetWarehouse');
    const saveIncoming = document.getElementById('saveIncoming');
    const saveOutgoing = document.getElementById('saveOutgoing');
    const movementsList = document.getElementById('movementsList');
    const movementsDate = document.getElementById('movementsDate');
    const movementsCount = document.getElementById('movementsCount');
    const toast = document.getElementById('toast');
    
    let currentMode = null; // 'incoming' або 'outgoing'
    
    // Показати toast
    function showToast(message, type = 'info') {
        toast.textContent = message;
        toast.className = 'toast visible ' + type;
        setTimeout(() => toast.className = 'toast', 3000);
    }
    
    // Форматувати дату
    function formatDate(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleDateString('uk-UA');
    }
    
    // Перемикання режиму
    function setMode(mode) {
        currentMode = mode;
        
        btnIncoming.classList.toggle('active', mode === 'incoming');
        btnOutgoing.classList.toggle('active', mode === 'outgoing');
        panelIncoming.classList.toggle('visible', mode === 'incoming');
        panelOutgoing.classList.toggle('visible', mode === 'outgoing');
        
        // Очистити поля
        qtyIncoming.value = '';
        qtyOutgoing.value = '';
        targetWarehouse.value = '';
    }
    
    btnIncoming.addEventListener('click', () => setMode(currentMode === 'incoming' ? null : 'incoming'));
    btnOutgoing.addEventListener('click', () => setMode(currentMode === 'outgoing' ? null : 'outgoing'));
    
    // Завантажити рухи
    async function loadMovements() {
        const date = dateInput.value;
        movementsDate.textContent = formatDate(date);
        
        try {
            const resp = await fetch(basePath + '/simple/movements?date=' + date);
            const data = await resp.json();
            
            if (!data.success) {
                showToast(data.error || 'Помилка', 'error');
                return;
            }
            
            renderMovements(data.movements);
        } catch (e) {
            showToast('Помилка з\'єднання', 'error');
        }
    }
    
    // Відрендерити рухи
    function renderMovements(movements) {
        movementsCount.textContent = '(' + movements.length + ')';
        
        if (movements.length === 0) {
            movementsList.innerHTML = '<div class="empty-message">Немає рухів за цю дату</div>';
            return;
        }
        
        movementsList.innerHTML = movements.map(m => {
            const isIncoming = m.warehouse_to_id == warehouseId;
            const direction = isIncoming ? 'incoming' : 'outgoing';
            const counterpart = isIncoming 
                ? (m.warehouse_from_name || 'Ззовні')
                : (m.warehouse_to_name || 'Списано');
            const arrow = isIncoming ? '←' : '→';
            const qty = parseFloat(m.quantity).toString();
            
            return `
                <div class="movement-item movement-${direction}">
                    <div class="movement-material">${escapeHtml(m.material_name)}</div>
                    <div class="movement-details">
                        <span>${arrow} ${escapeHtml(counterpart)}</span>
                        <span class="movement-qty">${qty}</span>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Зміна дати — перезавантажити рухи
    dateInput.addEventListener('change', loadMovements);
    
    // Зберегти надходження
    saveIncoming.addEventListener('click', async () => {
        const date = dateInput.value;
        const materialId = materialSelect.value;
        const quantity = parseFloat(qtyIncoming.value);
        
        if (!materialId) {
            showToast('Оберіть матеріал', 'error');
            return;
        }
        if (!quantity || quantity <= 0) {
            showToast('Вкажіть кількість', 'error');
            return;
        }
        
        saveIncoming.disabled = true;
        
        try {
            const resp = await fetch(basePath + '/simple/incoming', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `date=${date}&material_id=${materialId}&quantity=${quantity}`
            });
            const data = await resp.json();
            
            if (data.success) {
                showToast('✓ Надходження збережено', 'success');
                qtyIncoming.value = '';
                setMode(null);
                loadMovements();
            } else {
                showToast(data.error || 'Помилка', 'error');
            }
        } catch (e) {
            showToast('Помилка з\'єднання', 'error');
        }
        
        saveIncoming.disabled = false;
    });
    
    // Зберегти видачу
    saveOutgoing.addEventListener('click', async () => {
        const date = dateInput.value;
        const materialId = materialSelect.value;
        const targetId = targetWarehouse.value;
        const quantity = parseFloat(qtyOutgoing.value);
        
        if (!materialId) {
            showToast('Оберіть матеріал', 'error');
            return;
        }
        if (!targetId) {
            showToast('Оберіть куди видати', 'error');
            return;
        }
        if (!quantity || quantity <= 0) {
            showToast('Вкажіть кількість', 'error');
            return;
        }
        
        saveOutgoing.disabled = true;
        
        try {
            const resp = await fetch(basePath + '/simple/outgoing', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `date=${date}&material_id=${materialId}&target_warehouse_id=${targetId}&quantity=${quantity}`
            });
            const data = await resp.json();
            
            if (data.success) {
                showToast('✓ Видачу збережено', 'success');
                qtyOutgoing.value = '';
                targetWarehouse.value = '';
                setMode(null);
                loadMovements();
            } else {
                showToast(data.error || 'Помилка', 'error');
            }
        } catch (e) {
            showToast('Помилка з\'єднання', 'error');
        }
        
        saveOutgoing.disabled = false;
    });
})();
</script>

</body>
</html>
