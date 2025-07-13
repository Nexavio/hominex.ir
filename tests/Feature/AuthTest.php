<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\OtpCode;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;
use Carbon\Carbon;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock SMS service
        $this->app->instance(SmsService::class, Mockery::mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('sendOtpCode')->andReturn(true);
            $mock->shouldReceive('sendWelcomeMessage')->andReturn(true);
        }));
    }

    public function test_user_can_register_successfully()
    {
        $userData = [
            'phone' => '09123456789',
            'full_name' => 'تست کاربر',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user_id',
                    'phone',
                    'otp_expires_at'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'phone' => '09123456789',
            'email' => 'test@example.com',
            'full_name' => 'تست کاربر'
        ]);

        $this->assertDatabaseHas('otp_codes', [
            'phone' => '09123456789',
            'purpose' => 'register'
        ]);
    }

    public function test_user_registration_validates_phone_format()
    {
        $userData = [
            'phone' => '123456789', // Invalid format
            'full_name' => 'تست کاربر',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_user_cannot_register_with_duplicate_phone()
    {
        User::factory()->create(['phone' => '09123456789']);

        $userData = [
            'phone' => '09123456789',
            'full_name' => 'تست کاربر',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_user_can_verify_registration_otp()
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'phone_verified_at' => null
        ]);

        $otp = OtpCode::create([
            'phone' => '09123456789',
            'code' => '123456',
            'purpose' => 'register',
            'expires_at' => Carbon::now()->addMinutes(10)
        ]);

        $response = $this->postJson('/api/auth/verify-registration', [
            'phone' => '09123456789',
            'code' => '123456'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user_id',
                    'phone_verified',
                    'verified_at'
                ]
            ]);

        $user->refresh();
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_user_cannot_verify_with_wrong_otp()
    {
        User::factory()->create(['phone' => '09123456789']);

        OtpCode::create([
            'phone' => '09123456789',
            'code' => '123456',
            'purpose' => 'register',
            'expires_at' => Carbon::now()->addMinutes(10)
        ]);

        $response = $this->postJson('/api/auth/verify-registration', [
            'phone' => '09123456789',
            'code' => '654321' // Wrong code
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false
            ]);
    }

    public function test_user_can_login_with_password()
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'password' => bcrypt('password123'),
            'phone_verified_at' => Carbon::now()
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '09123456789',
            'password' => 'password123',
            'login_type' => 'password'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user'
                ]
            ]);
    }

    public function test_user_can_request_login_otp()
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'phone_verified_at' => Carbon::now()
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '09123456789',
            'login_type' => 'otp'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('otp_codes', [
            'phone' => '09123456789',
            'purpose' => 'login'
        ]);
    }

    public function test_user_cannot_login_without_phone_verification()
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'phone_verified_at' => null
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '09123456789',
            'password' => 'password123',
            'login_type' => 'password'
        ]);

        $response->assertStatus(403);
    }

    public function test_otp_rate_limiting()
    {
        // Simulate 3 previous attempts
        Cache::put('otp_rate_limit:09123456789', 3, 3600);

        $response = $this->postJson('/api/auth/send-otp', [
            'phone' => '09123456789'
        ]);

        $response->assertStatus(429);
    }

    public function test_otp_expires_after_10_minutes()
    {
        $otp = OtpCode::create([
            'phone' => '09123456789',
            'code' => '123456',
            'purpose' => 'login',
            'expires_at' => Carbon::now()->subMinutes(11) // Expired
        ]);

        $response = $this->postJson('/api/auth/verify-login', [
            'phone' => '09123456789',
            'code' => '123456'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false
            ]);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create([
            'phone_verified_at' => Carbon::now()
        ]);

        $token = auth()->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200);
    }

    public function test_user_can_refresh_token()
    {
        $user = User::factory()->create([
            'phone_verified_at' => Carbon::now()
        ]);

        $token = auth()->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in'
                ]
            ]);
    }

    public function test_user_can_get_profile()
    {
        $user = User::factory()->create([
            'phone_verified_at' => Carbon::now()
        ]);

        $token = auth()->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'phone',
                    'email',
                    'full_name',
                    'user_type'
                ]
            ]);
    }
}
