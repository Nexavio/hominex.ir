<?php

namespace App\DTOs\User;

use App\Enums\UserRole;

class UserData
{
    public function __construct(
        public string $phone,
        public ?string $email = null,
        public ?string $fullName = null,
        public ?string $password = null,
        public UserRole $userType = UserRole::REGULAR,
        public bool $isActive = true,
        public ?\DateTime $phoneVerifiedAt = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            phone: $data['phone'],
            email: $data['email'] ?? null,
            fullName: $data['full_name'] ?? null,
            password: $data['password'] ?? null,
            userType: isset($data['user_type']) ? UserRole::from($data['user_type']) : UserRole::REGULAR,
            isActive: $data['is_active'] ?? true,
            phoneVerifiedAt: isset($data['phone_verified_at']) ? new \DateTime($data['phone_verified_at']) : null
        );
    }

    public function toArray(): array
    {
        return [
            'phone' => $this->phone,
            'email' => $this->email,
            'full_name' => $this->fullName,
            'password' => $this->password,
            'user_type' => $this->userType->value,
            'is_active' => $this->isActive,
            'phone_verified_at' => $this->phoneVerifiedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
