<?php

namespace App\Repositories\Interfaces;

use App\DTOs\User\UserData;
use App\Models\User;

interface UserRepositoryInterface
{
    public function create(UserData $userData): User;

    public function findByPhone(string $phone): ?User;

    public function findByEmail(string $email): ?User;

    public function update(User $user, UserData $userData): User;

    public function verifyPhone(User $user): User;

    public function exists(string $phone, ?string $email = null): bool;
}
