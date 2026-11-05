<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_account_and_redirects_to_login_without_authenticating(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Ayu Putri',
            'email' => '  AYU@example.com ',
            'password' => 'safe-password-123',
            'password_confirmation' => 'safe-password-123',
        ]);

        $user = User::where('email', 'ayu@example.com')->firstOrFail();

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Akun berhasil dibuat. Silakan masuk menggunakan email dan kata sandi Anda.');
        $this->assertGuest();
        $this->assertTrue(Hash::check('safe-password-123', $user->password));
        $this->assertSame('customer', $user->role);
        $this->assertNotSame('safe-password-123', $user->password);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Akun berhasil dibuat. Silakan masuk menggunakan email dan kata sandi Anda.');
    }

    public function test_user_can_log_in_with_email_and_password(): void
    {
        $user = User::create([
            'name' => 'Ayu Putri',
            'email' => 'ayu@example.com',
            'password' => 'safe-password-123',
        ]);
        $this->post(route('login.store'), [
            'email' => ' AYU@EXAMPLE.COM ',
            'password' => 'safe-password-123',
            'remember' => '1',
        ])
            ->assertRedirect(route('home'))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_google_login_creates_a_verified_pemesan_account(): void
    {
        $googleUser = \Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getRaw')->once()->andReturn(['email_verified' => true]);
        $googleUser->shouldReceive('getEmail')->once()->andReturn(' AYU@example.com ');
        $googleUser->shouldReceive('getName')->once()->andReturn('Ayu Putri');

        $provider = \Mockery::mock(SocialiteProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        $socialite = \Mockery::mock(SocialiteFactory::class);
        $socialite->shouldReceive('driver')->with('google')->once()->andReturn($provider);
        $this->app->instance(SocialiteFactory::class, $socialite);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('home'))
            ->assertSessionHasNoErrors();

        $user = User::where('email', 'ayu@example.com')->firstOrFail();
        $this->assertSame('pemesan', $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_login_rejects_an_unverified_email(): void
    {
        $googleUser = \Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getRaw')->once()->andReturn(['email_verified' => false]);
        $googleUser->shouldReceive('getEmail')->once()->andReturn('ayu@example.com');

        $provider = \Mockery::mock(SocialiteProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        $socialite = \Mockery::mock(SocialiteFactory::class);
        $socialite->shouldReceive('driver')->with('google')->once()->andReturn($provider);
        $this->app->instance(SocialiteFactory::class, $socialite);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('warning');

        $this->assertDatabaseMissing('users', ['email' => 'ayu@example.com']);
        $this->assertGuest();
    }

    public function test_google_password_reset_redirect_starts_oauth_with_a_session_intent(): void
    {
        config([
            'services.google.client_id' => 'client-id',
            'services.google.client_secret' => 'client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);

        $provider = \Mockery::mock(SocialiteProvider::class);
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
        $socialite = \Mockery::mock(SocialiteFactory::class);
        $socialite->shouldReceive('driver')->with('google')->once()->andReturn($provider);
        $this->app->instance(SocialiteFactory::class, $socialite);

        $this->get(route('auth.google.password-reset'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth')
            ->assertSessionHas('google_password_reset', true);
    }

    public function test_google_password_reset_only_authenticates_an_existing_verified_customer(): void
    {
        $user = User::create([
            'name' => 'Ayu Putri',
            'email' => 'ayu@example.com',
            'password' => 'safe-password-123',
        ]);

        $googleUser = \Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getRaw')->once()->andReturn(['email_verified' => true]);
        $googleUser->shouldReceive('getEmail')->once()->andReturn('AYU@example.com');

        $provider = \Mockery::mock(SocialiteProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        $socialite = \Mockery::mock(SocialiteFactory::class);
        $socialite->shouldReceive('driver')->with('google')->once()->andReturn($provider);
        $this->app->instance(SocialiteFactory::class, $socialite);

        $this->withSession(['google_password_reset' => true])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHas('status', 'Identitas Google terverifikasi. Silakan buat kata sandi baru untuk akun Anda.');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);

        $this->put(route('account.password.update'), [
            'email' => $user->email,
            'password' => 'new-safe-password-123',
            'password_confirmation' => 'new-safe-password-123',
        ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', 'Kata sandi berhasil diubah.');

        $this->assertTrue(Hash::check('new-safe-password-123', $user->fresh()->password));
    }

    public function test_google_password_reset_does_not_create_an_account_for_an_unknown_email(): void
    {
        $googleUser = \Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getRaw')->once()->andReturn(['email_verified' => true]);
        $googleUser->shouldReceive('getEmail')->once()->andReturn('unknown@example.com');

        $provider = \Mockery::mock(SocialiteProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        $socialite = \Mockery::mock(SocialiteFactory::class);
        $socialite->shouldReceive('driver')->with('google')->once()->andReturn($provider);
        $this->app->instance(SocialiteFactory::class, $socialite);

        $this->withSession(['google_password_reset' => true])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_google_login_does_not_authenticate_an_admin_account(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'safe-password-123',
            'role' => 'admin',
        ]);

        $googleUser = \Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getRaw')->once()->andReturn(['email_verified' => true]);
        $googleUser->shouldReceive('getEmail')->once()->andReturn('admin@example.com');
        $googleUser->shouldReceive('getName')->once()->andReturn('Admin');

        $provider = \Mockery::mock(SocialiteProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        $socialite = \Mockery::mock(SocialiteFactory::class);
        $socialite->shouldReceive('driver')->with('google')->once()->andReturn($provider);
        $this->app->instance(SocialiteFactory::class, $socialite);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('warning');

        $this->assertGuest();
        $this->assertSame('admin', User::where('email', 'admin@example.com')->value('role'));
    }

    public function test_google_sign_in_is_only_shown_on_the_customer_login_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk dengan Google')
            ->assertSee(route('auth.google.redirect'), false);

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertDontSee('Masuk dengan Google');
    }

    public function test_google_redirect_shows_a_clear_message_when_oauth_is_not_configured(): void
    {
        config([
            'services.google.client_id' => '',
            'services.google.client_secret' => '',
            'services.google.redirect' => '',
        ]);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('warning', 'Login Google belum dikonfigurasi. Isi GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, dan GOOGLE_REDIRECT_URL di file .env.');
    }

    public function test_pemesan_role_can_use_email_and_password_login(): void
    {
        $user = User::create([
            'name' => 'Ayu Putri',
            'email' => 'ayu@example.com',
            'password' => 'safe-password-123',
            'role' => 'pemesan',
        ]);
        $this->post(route('login.store'), [
            'email' => 'ayu@example.com',
            'password' => 'safe-password-123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_password_recovery_pages_are_available_from_customer_login(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'), false);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Lupa kata sandi?')
            ->assertDontSee('recovery_code')
            ->assertDontSee('name="email"', false)
            ->assertSee('Lanjutkan dengan Google')
            ->assertSee(route('auth.google.password-reset'), false);
    }

    public function test_guest_cannot_open_signed_in_password_change_form(): void
    {
        $this->get(route('account.password.edit'))
            ->assertRedirect(route('login'));

        $this->put(route('account.password.update'), [
            'email' => 'ayu@example.com',
            'password' => 'new-safe-password-123',
            'password_confirmation' => 'new-safe-password-123',
        ])->assertRedirect(route('login'));
    }

    public function test_signed_in_user_can_change_password_with_email_and_confirmation(): void
    {
        $user = User::create([
            'name' => 'Ayu Putri',
            'email' => 'ayu@example.com',
            'password' => 'safe-password-123',
        ]);

        $this->actingAs($user)
            ->get(route('account.password.edit'))
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false);

        $this->put(route('account.password.update'), [
            'email' => ' AYU@EXAMPLE.COM ',
            'password' => 'new-safe-password-123',
            'password_confirmation' => 'new-safe-password-123',
        ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', 'Kata sandi berhasil diubah.')
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-safe-password-123', $user->fresh()->password));
    }

    public function test_signed_in_user_cannot_change_password_for_another_email(): void
    {
        $user = User::create([
            'name' => 'Ayu Putri',
            'email' => 'ayu@example.com',
            'password' => 'safe-password-123',
        ]);
        User::create([
            'name' => 'Pengguna Lain',
            'email' => 'other@example.com',
            'password' => 'other-safe-password-123',
        ]);

        $this->actingAs($user)
            ->from(route('account.password.edit'))
            ->put(route('account.password.update'), [
                'email' => 'other@example.com',
                'password' => 'new-safe-password-123',
                'password_confirmation' => 'new-safe-password-123',
            ])
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('safe-password-123', $user->fresh()->password));
        $this->assertTrue(Hash::check('other-safe-password-123', User::where('email', 'other@example.com')->firstOrFail()->password));
    }

    public function test_admin_and_customer_use_separate_login_portals_and_admin_redirect(): void
    {
        $customer = User::create([
            'name' => 'Pelanggan',
            'email' => 'customer@example.com',
            'password' => 'safe-password-123',
        ]);
        $admin = User::create([
            'name' => 'Pemilik Hotel',
            'email' => 'owner@example.com',
            'password' => 'safe-password-123',
            'role' => 'admin',
        ]);

        $this->get(route('login'))->assertOk()->assertSee('akun pemesan');
        $this->get(route('admin.login'))->assertOk()->assertSee('Masuk Admin Hotel HAR Official');

        $this->post(route('admin.login.store'), [
            'email' => $customer->email,
            'password' => 'safe-password-123',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'safe-password-123',
        ])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Dashboard Admin');
    }

    public function test_customer_login_rejects_admin_account_and_admin_routes_reject_customers(): void
    {
        $admin = User::create([
            'name' => 'Pemilik Hotel',
            'email' => 'owner@example.com',
            'password' => 'safe-password-123',
            'role' => 'admin',
        ]);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'safe-password-123',
        ])->assertSessionHasErrors('email');

        $customer = User::create([
            'name' => 'Pelanggan',
            'email' => 'customer@example.com',
            'password' => 'safe-password-123',
        ]);
        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_allowlisted_account_can_be_promoted_to_admin_from_console(): void
    {
        config(['payments.admin_emails' => ['owner@example.com']]);
        $owner = User::create([
            'name' => 'Pemilik Hotel',
            'email' => 'owner@example.com',
            'password' => 'safe-password-123',
        ]);

        $this->artisan('app:make-admin owner@example.com')
            ->expectsOutput('Akun berhasil diberi peran admin. Masuk melalui /admin/login.')
            ->assertExitCode(0);

        $this->assertSame('admin', $owner->fresh()->role);
    }

    public function test_non_allowlisted_account_cannot_be_promoted_to_admin(): void
    {
        config(['payments.admin_emails' => ['approved-owner@example.com']]);
        $user = User::create([
            'name' => 'Pengguna',
            'email' => 'user@example.com',
            'password' => 'safe-password-123',
        ]);

        $this->artisan('app:make-admin user@example.com')
            ->expectsOutput('Email tidak tercantum dalam PAYMENT_ADMIN_EMAILS di .env.')
            ->assertExitCode(1);

        $this->assertSame('customer', $user->fresh()->role);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::create([
            'name' => 'Ayu Putri',
            'email' => 'ayu@example.com',
            'password' => 'safe-password-123',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'ayu@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logged_in_user_can_log_out(): void
    {
        $user = User::create([
            'name' => 'Ayu Putri',
            'email' => 'ayu@example.com',
            'password' => 'safe-password-123',
        ]);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_registered_email_must_be_unique(): void
    {
        User::create([
            'name' => 'Ayu Putri',
            'email' => 'ayu@example.com',
            'password' => 'safe-password-123',
        ]);

        $this->from(route('register'))
            ->followingRedirects()
            ->post(route('register.store'), [
            'name' => 'Ayu Lain',
            'email' => 'AYU@example.com',
            'password' => 'safe-password-123',
            'password_confirmation' => 'safe-password-123',
        ])
            ->assertOk()
            ->assertSee('Alamat email ini sudah terdaftar. Silakan masuk dengan akun yang sudah ada.')
            ->assertSee('Masuk dengan email ini')
            ->assertSee(route('login', ['email' => 'ayu@example.com']), false);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_shows_a_clear_indonesian_password_confirmation_error(): void
    {
        $this->from(route('register'))
            ->followingRedirects()
            ->post(route('register.store'), [
                'name' => 'Ayu Putri',
                'email' => 'ayu@example.com',
                'password' => 'safe-password-123',
                'password_confirmation' => 'different-password',
            ])
            ->assertOk()
            ->assertSee('Konfirmasi kata sandi tidak sama. Ketik kata sandi yang sama pada kedua kolom.')
            ->assertSee('name="password_confirmation"', false);

        $this->assertDatabaseCount('users', 0);
    }
}
