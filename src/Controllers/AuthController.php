<?php

declare(strict_types=1);

namespace App\Controllers;

final class AuthController
{
    public function __construct(private array $app)
    {
    }

    public function showLogin(): void
    {
        if ($this->currentUser() !== null) {
            redirect_to('/dashboard');
        }

        app_view('auth/login', [
            'title' => 'Login',
            'error' => $_SESSION['flash_error'] ?? null,
        ]);

        unset($_SESSION['flash_error']);
    }

    public function login(): void
    {
        $email = strtolower(form_value('email'));
        $password = form_value('password');

        $user = $this->app['store']->find('users', static fn (array $record): bool => strtolower($record['email']) === $email);
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            $_SESSION['flash_error'] = 'Invalid email or password.';
            redirect_to('/login');
        }

        $_SESSION['user_id'] = $user['id'];
        redirect_to('/dashboard');
    }

    public function logout(): void
    {
        session_destroy();
        redirect_to('/login');
    }

    private function currentUser(): ?array
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!is_string($userId)) {
            return null;
        }

        return $this->app['store']->find('users', static fn (array $record): bool => $record['id'] === $userId);
    }
}
