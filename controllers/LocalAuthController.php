<?php

class LocalAuthController extends Controller
{
    public function login(): void
    {
        // Якщо вже залогінений
        if (isset($_SESSION['nc_user'])) {
            $this->redirect('dashboard');
            return;
        }
        
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            $userModel = new UserModel($this->db);
            $user = $userModel->authenticate($username, $password);
            
            if ($user) {
                $_SESSION['nc_user'] = $user['username'];
                $_SESSION['nc_display_name'] = $user['display_name'] ?? $user['username'];
                $_SESSION['nc_groups'] = $user['is_admin'] ? ['admin'] : [];
                
                $userModel->updateLastLogin($user['id']);
                
                // Синхронізуємо з системою прав
                $permManager = PermissionManager::getInstance($this->db);
                $permManager->syncCurrentUser();
                
                $this->redirect('dashboard');
                return;
            } else {
                $error = 'Невірний логін або пароль';
            }
        }
        
        $this->renderSimple('login', ['error' => $error, 'title' => 'Вхід в систему']);
    }
    
    public function logout(): void
    {
        session_destroy();
        $this->redirect('login');
    }
    
    private function renderSimple(string $view, array $data = []): void
    {
        extract($data);
        require ROOT_PATH . '/views/auth/' . $view . '.php';
    }
}