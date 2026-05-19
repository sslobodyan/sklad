<?php
/**
 * Контролер адміністрування (бекап, рестор)
 */
class AdminController extends Controller
{
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    private function checkAdmin(): void
    {
        $groups = $_SESSION['nc_groups'] ?? [];
        if (!in_array('admin', $groups)) {
            $this->flash('error', 'Доступ заборонено');
            $this->redirect('movements');
            exit;
        }
    }

    private function getDbConfig(): array
    {
        return Database::getCurrentConfig();
    }

    public function backup(): void
    {
        $this->checkAdmin();
        
        $this->render('admin/backup', [
            'title' => 'Бекап бази даних',
            'activePage' => 'admin-backup',
        ]);
    }

    public function restore(): void
    {
        $this->checkAdmin();
        
        $this->render('admin/restore', [
            'title' => 'Відновлення бази даних',
            'activePage' => 'admin-restore',
        ]);
    }

    public function doBackup(): void
    {
        $this->checkAdmin();
        
        if (!$this->isPost()) {
            $this->redirect('admin/backup');
            return;
        }

        $config = $this->getDbConfig();
        $filename = 'sklad_backup_' . date('Y-m-d_H-i-s') . '_' . $config['name'] . '.sql';
        $zipFilename = str_replace('.sql', '.zip', $filename);
        
        $sqlFile = sys_get_temp_dir() . '/' . $filename;
        $zipFile = sys_get_temp_dir() . '/' . $zipFilename;
        
        $cmd = sprintf(
            'mysqldump --host=%s --user=%s --password=%s %s --routines --triggers --single-transaction --default-character-set=utf8mb4 2>&1',
            escapeshellarg($config['host']),
            escapeshellarg($config['user']),
            escapeshellarg($config['pass']),
            escapeshellarg($config['name'])
        );
        
        exec($cmd, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $this->flash('error', 'Помилка створення бекапу: ' . implode("\n", $output));
            $this->redirect('admin/backup');
            return;
        }
        
        file_put_contents($sqlFile, implode("\n", $output));
        
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->flash('error', 'Не вдалося створити ZIP архів');
            $this->redirect('admin/backup');
            return;
        }
        
        $zip->addFile($sqlFile, $filename);
        $zip->close();
        unlink($sqlFile);
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
        header('Content-Length: ' . filesize($zipFile));
        header('Cache-Control: max-age=0');
        readfile($zipFile);
        unlink($zipFile);
        exit;
    }

    public function doRestore(): void
    {
        $this->checkAdmin();
        
        if (!$this->isPost() || empty($_FILES['backup_file'])) {
            $this->flash('error', 'Файл не вибрано');
            $this->redirect('admin/restore');
            return;
        }
        
        $file = $_FILES['backup_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Помилка завантаження файлу');
            $this->redirect('admin/restore');
            return;
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['sql', 'zip'])) {
            $this->flash('error', 'Підтримуються тільки .sql або .zip файли');
            $this->redirect('admin/restore');
            return;
        }
        
        if ($ext === 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($file['tmp_name']) !== true) {
                $this->flash('error', 'Не вдалося відкрити ZIP архів');
                $this->redirect('admin/restore');
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
                $this->redirect('admin/restore');
                return;
            }
        } else {
            $sqlContent = file_get_contents($file['tmp_name']);
        }
        
        $config = $this->getDbConfig();
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
        
        $this->redirect('admin/restore');
    }
}