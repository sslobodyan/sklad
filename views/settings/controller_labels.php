<div class="page-header">
    <div>
        <h1 class="page-title">Назви контролерів</h1>
        <p class="page-subtitle">Налаштування назв пунктів меню (порожнє поле = оригінальна назва)</p>
    </div>
</div>

<div class="card card-stretch">
    <?php if (empty($controllers)): ?>
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
        <p>Контролерів не знайдено</p>
    </div>
    <?php else: ?>
    <div class="table-scroll">
        <form method="post" action="<?= $basePath ?>/settings/controller-labels/save">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Контролер</th>
                        <th>Назва для відображення</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($controllers as $key => $originalName): ?>
                    <?php $currentLabel = $currentLabels[$key] ?? ''; ?>
                    <tr>
                        <td class="font-mono"><?= htmlspecialchars($originalName) ?></td>
                        <td><input type="text" name="label_<?= $key ?>" value="<?= htmlspecialchars($currentLabel) ?>" placeholder="<?= htmlspecialchars($originalName) ?>" class="form-input" style="width: 300px;"></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="card-footer-info">
                <button type="submit" class="btn btn-primary">Зберегти назви</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>