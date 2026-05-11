<div class="page-header">
    <div>
        <h1 class="page-title">Матеріали</h1>
        <p class="page-subtitle">Довідник номенклатури матеріалів</p>
    </div>
</div>

<div class="card card-stretch">
    <?php if (empty($materials)): ?>
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
        </svg>
        <p>Матеріалів поки немає</p>
        <button class="btn btn-primary btn-sm" onclick="openMaterialModal()">Додати перший матеріал</button>
    </div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:50px">#</th>
                <th>Назва матеріалу</th>
                <th style="width:140px">
                    <button class="btn btn-primary btn-sm table-header-add" title="Додати матеріал" onclick="openMaterialModal()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Додати
                    </button>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($materials as $i => $m):
                $isUsed = in_array($m['id'], $usedIds);
            ?>
            <tr ondblclick="openMaterialModal(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['name'])) ?>')" class="row-editable" title="Подвійний клік — редагувати">
                <td class="text-muted"><?= $i + 1 ?></td>
                <td class="font-medium"><?= htmlspecialchars($m['name']) ?></td>
                <td>
                    <div class="actions">
                        <button class="btn-icon" title="Редагувати" 
                                onclick="openMaterialModal(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['name'])) ?>')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </button>
                        <?php if (!$isUsed): ?>
                        <button class="btn-icon btn-icon-danger" title="Видалити" 
                                onclick="confirmDelete('<?= $basePath ?>/materials/delete/<?= $m['id'] ?>', 'Видалити матеріал «<?= htmlspecialchars(addslashes($m['name'])) ?>»?')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
