<?php

class AdminRestoreController extends Controller
{
    use AuthorizeTrait;
    
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    public function index(): void
    {
        $this->checkAccess('index');
        
        $this->render('admin/restore', [
            'title' => 'Відновлення бази даних',
            'activePage' => 'adminRestore',
        ]);
    }

    public function doRestore(): void
    {
        $this->checkAccess('doRestore');
        
        if (!$this->isPost() || empty($_FILES['backup_file'])) {
            $this->flash('error', 'Файл не вибрано');
            $this->redirect('adminRestore');
            return;
        }
        
        $file = $_FILES['backup_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Помилка завантаження файлу');
            $this->redirect('adminRestore');
            return;
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['sql', 'zip'])) {
            $this->flash('error', 'Підтримуються тільки .sql або .zip файли');
            $this->redirect('adminRestore');
            return;
        }
        
        if ($ext === 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($file['tmp_name']) !== true) {
                $this->flash('error', 'Не вдалося відкрити ZIP архів');
                $this->redirect('adminRestore');
                return;
            }
            $sqlContent = '';
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (pathinfo($filename, PATHINFO_EXTENSION) === 'sql') {
                    $sqlContent = $zip->getFromName($filename);
                    break;
                }
            }
            $zip->close();
            if (empty($sqlContent)) {
                $this->flash('error', 'У ZIP архіві не знайдено .sql файлу');
                $this->redirect('adminRestore');
                return;
            }
        } else {
            $sqlContent = file_get_contents($file['tmp_name']);
        }
        
        $config = Database::getCurrentConfig();
        $sqlFile = sys_get_temp_dir() . '/restore_' . time() . '.sql';
        file_put_contents($sqlFile, $sqlContent);
        
        $cmd = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s 2>&1',
            escapeshellarg($config['host']),
            escapeshellarg($config['user']),
            escapeshellarg($config['pass']),
            escapeshellarg($config['name']),
            escapeshellarg($sqlFile)
        );
        
        exec($cmd, $output, $returnCode);
        unlink($sqlFile);
        
        if ($returnCode !== 0) {
            $this->flash('error', 'Помилка відновлення: ' . implode("\n", $output));
        } else {
            $this->flash('success', 'Базу даних успішно відновлено');
        }
        
        $this->redirect('adminRestore');
    }
}