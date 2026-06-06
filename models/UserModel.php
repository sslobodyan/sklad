<?php

class UserModel extends Model
{
    protected string $table = 'users';

    public function authenticate(string $username, string $password): ?array
    {
        $user = $this->db->query(
            "SELECT * FROM users WHERE username = ?",
            [$username]
        )->fetch();
        
        if (!$user) {
            return null;
        }
        
        if (password_verify($password, $user['password_hash'])) {
            return $user;
        }
        
        return null;
    }

    public function updateLastLogin(int $userId): void
    {
        $this->db->query(
            "UPDATE users SET last_login = NOW() WHERE id = ?",
            [$userId]
        );
    }

    public function getUserByUsername(string $username): ?array
    {
        return $this->db->query(
            "SELECT * FROM users WHERE username = ?",
            [$username]
        )->fetch();
    }

    public function createUser(string $username, string $password, string $displayName, string $email, bool $isAdmin = false): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $this->db->query(
            "INSERT INTO users (username, password_hash, display_name, email, is_admin) 
             VALUES (?, ?, ?, ?, ?)",
            [$username, $hash, $displayName, $email, $isAdmin ? 1 : 0]
        );
        
        return $this->db->lastInsertId();
    }
}