<?php
// tests/Feature/AuthTest.php
namespace Tests\Feature;

use App\Models\User;
use App\Models\OtpCode;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;
use Carbon\Carbon;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh');

        if (empty(config('jwt.secret'))) {
            Artisan::call('jwt:secret', ['--force' => true]);
        }

        $this->app->instance(SmsService::class, Mockery::mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('sendOtpCode')->andReturn(true);
            $mock->shouldReceive('sendWelcomeMessage')->andReturn(true);
        }));
    }

    public function test_health_endpoint_works()
    {
        $response = $this->getJson('/api/v1/health');
        $response->assertStatus(200);
    }

    public function test_user_can_login_with_password()
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'password' => Hash::make('password123'),
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

    public function test_login_fails_with_wrong_password()
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'password' => Hash::make('password123'),
            'phone_verified_at' => Carbon::now()
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '09123456789',
            'password' => 'wrongpassword',
            'login_type' => 'password'
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_request_otp_for_login()
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
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
