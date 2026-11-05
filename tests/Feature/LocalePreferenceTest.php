<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LocalePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_switch_persists_in_session_and_localizes_the_next_page(): void
    {
        app()->setLocale('id');
        $this->assertSame('Beranda', __('Beranda'));
        app()->setLocale('en');
        $this->assertSame('Home', __('Beranda', [], 'en'));

        $this->from(route('login'))
            ->get(route('locale.switch', ['locale' => 'en']))
            ->assertRedirect(route('login'))
            ->assertSessionHas('locale', 'en');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('Sign in to your guest account');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Guest favorites')
            ->assertSee('Hotel location')
            ->assertSee("It's time to")
            ->assertSee('stayvia-theme');
    }

    public function test_locale_switch_rejects_unsupported_locales(): void
    {
        $this->get('/locale/fr')->assertNotFound();
    }
}
