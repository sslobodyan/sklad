<?php

class AdminBackupController extends Controller
{
    use AuthorizeTrait;
    
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    public function index(): void
    {
        $this->checkAccess('index');
        
        $this->render('admin/backup', [
            'title' => 'Бекап бази даних',
            'activePage' => 'adminBackup',
        ]);
    }

    public function doBackup(): void
    {
        $this->checkAccess('doBackup');
        
        if (!$this->isPost()) {
            $this->redirect('adminBackup');
            return;
        }

        $config = Database::getCurrentConfig();
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
            $this->redirect('adminBackup');
            return;
        }
        
        file_put_contents($sqlFile, implode("\n", $output));
        
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->flash('error', 'Не вдалося створити ZIP архів');
            $this->redirect('adminBackup');
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
}