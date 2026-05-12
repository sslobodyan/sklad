<?php
/**
 * Шаблон Nextcloud App — вбудовує складський облік через iframe
 * Передає user/groups через параметри URL (підписані HMAC)
 */
$skladUrl = $_['skladUrl'] ?? '/sklad/simple/';
$subPath = $_['subPath'] ?? '';
$userInfo = $_['userInfo'] ?? ['uid' => '', 'displayName' => '', 'groups' => []];
if ($subPath) {
    $skladUrl = rtrim($skladUrl, '/') . '/' . $subPath;
}
// Передаємо query string від оригінального запиту
$query = $_SERVER['QUERY_STRING'] ?? '';
// Додаємо інформацію про користувача
$authParams = [
    'nc_user' => $userInfo['uid'],
    'nc_name' => $userInfo['displayName'],
    'nc_groups' => implode(',', $userInfo['groups']),
    'nc_ts' => time(),
];
// Підписуємо параметри секретним ключем для захисту від підробки
$secret = \OC::$server->getConfig()->getSystemValue('secret', '');
$authParams['nc_sig'] = hash_hmac('sha256', $authParams['nc_user'] . '|' . $authParams['nc_groups'] . '|' . $authParams['nc_ts'], $secret);
// Збираємо URL
$separator = (strpos($skladUrl, '?') !== false) ? '&' : '?';
$fullUrl = $skladUrl . $separator . http_build_query($authParams);
if ($query) {
    $fullUrl .= '&' . $query;
}
?>
<div id="app-content">
    <iframe 
        id="sklad-frame"
        src="<?php echo htmlspecialchars($fullUrl); ?>"
        style="width:100%; height:calc(100vh - 50px); border:none; display:block;"
        allow="clipboard-write"
    ></iframe>
</div>
