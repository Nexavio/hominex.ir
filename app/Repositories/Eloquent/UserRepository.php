<?php

namespace App\Repositories\Eloquent;

use App\DTOs\User\UserData;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserRepository implements UserRepositoryInterface
{
    public function create(UserData $userData): User
    {
        $data = $userData->toArray();

        if ($data['password']) {
            $data['password'] = Hash::make($data['password']);
        }

        return User::create($data);
    }

    public function findByPhone(string $phone): ?User
    {
        return User::where('phone', $phone)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function update(User $user, UserData $userData): User
    {
        $data = $userData->toArray();

        // حذف فیلدهای null
        $data = array_filter($data, fn($value) => $value !== null);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return $user->fresh();
    }

    public function verifyPhone(User $user): User
    {
        $user->update([
            'phone_verified_at' => Carbon::now()
        ]);

        return $user->fresh();
    }

    public function exists(string $phone, ?string $email = null): bool
    {
        $query = User::where('phone', $phone);

        if ($email) {
            $query->orWhere('email', $email);
        }

        return $query->exists();
    }
}
