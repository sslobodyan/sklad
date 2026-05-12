<?php
/**
 * Адмін-налаштування: URL складського додатку
 */
\OCP\Util::addScript('sklad', 'admin');
?>
<div class="section" id="sklad-settings">
    <h2>Складський облік</h2>
    <p>
        <label for="sklad-url">URL додатку:</label><br>
        <input type="text" id="sklad-url" name="external_url" 
               value="<?php echo htmlspecialchars($_['external_url']); ?>"
               placeholder="/sklad/" style="width:400px;margin-top:4px">
    </p>
    <p style="color:#888;font-size:13px">
        Вкажіть URL де розгорнуто складський облік.<br>
        Наприклад: <code>/sklad/</code> або <code>https://baza.taildd9271.ts.net/sklad/</code>
    </p>
    <p>
        <button id="sklad-save" class="primary">Зберегти</button>
        <span id="sklad-msg" style="display:none;margin-left:10px"></span>
    </p>
</div>