<?php

namespace Tests\Feature;

use App\Models\Faq;
use Database\Seeders\FaqSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_faqs_render_in_the_selected_locale(): void
    {
        Faq::create([
            'question_id' => 'Bagaimana cara memesan kamar?',
            'question_en' => 'How do I book a room?',
            'answer_id' => 'Pilih tanggal dan kamar.',
            'answer_en' => 'Choose dates and a room.',
            'is_active' => true,
        ]);

        $this->withSession(['locale' => 'id'])
            ->get(route('login'))
            ->assertSee('Pusat bantuan')
            ->assertDontSee('help.title')
            ->assertSee('Bagaimana cara memesan kamar?')
            ->assertSee('Pilih tanggal dan kamar.');

        $this->withSession(['locale' => 'en'])
            ->get(route('login'))
            ->assertSee('Help center')
            ->assertDontSee('help.title')
            ->assertSee('How do I book a room?')
            ->assertSee('Choose dates and a room.');
    }

    public function test_inactive_faqs_are_not_rendered(): void
    {
        Faq::create([
            'question_id' => 'Pertanyaan tidak aktif',
            'question_en' => 'Inactive question',
            'answer_id' => 'Jawaban tidak aktif',
            'answer_en' => 'Inactive answer',
            'is_active' => false,
        ]);

        $this->get(route('login'))
            ->assertDontSee('Pertanyaan tidak aktif')
            ->assertDontSee('Inactive question');
    }

    public function test_e_voucher_faq_shows_restaurant_and_cafe_copy(): void
    {
        $this->seed(FaqSeeder::class);

        $this->withSession(['locale' => 'id'])
            ->get(route('login'))
            ->assertSee('Tunjukkan kode e-voucher di pesanan Anda kepada petugas resto &amp; cafe, sesuai voucher kamar Anda.', false)
            ->assertDontSee('kolam renang')
            ->assertDontSee('gym');
    }

    public function test_contact_actions_follow_configuration_and_encode_whatsapp_message(): void
    {
        config([
            'hotel.whatsapp' => '628123456789',
            'hotel.support_email' => 'support@example.com',
            'hotel.whatsapp_message.id' => 'Halo Admin, bantu saya?',
        ]);

        $this->get(route('login'))
            ->assertSee('https://wa.me/628123456789?text=Halo+Admin%2C+bantu+saya%3F', false)
            ->assertSee(
                'mailto:support@example.com?subject=Pertanyaan%20reservasi%20Stayvia&amp;body=Halo%20Admin%2C%20bantu%20saya%3F',
                false,
            )
            ->assertSee(
                'https://mail.google.com/mail/?view=cm&amp;fs=1&amp;to=support%40example.com&amp;su=Pertanyaan%20reservasi%20Stayvia&amp;body=Halo%20Admin%2C%20bantu%20saya%3F',
                false,
            );

        config(['hotel.whatsapp' => '', 'hotel.support_email' => '']);

        $this->get(route('login'))
            ->assertDontSee('help-whatsapp')
            ->assertDontSee('help-email');
    }
}
