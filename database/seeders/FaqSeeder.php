<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question_id' => 'Bagaimana cara memesan kamar?',
                'question_en' => 'How do I book a room?',
                'answer_id' => 'Pilih hotel dan tanggal, pilih kamar, lalu lanjutkan ke pembayaran. Kamar yang Anda pilih dikunci selama 10 menit.',
                'answer_en' => 'Choose a hotel and dates, pick a room, then continue to payment. Your selected room is held for 10 minutes.',
            ],
            [
                'question_id' => 'Kapan check-in dan check-out?',
                'question_en' => 'When are check-in and check-out?',
                'answer_id' => 'Waktu check-in dan check-out disesuaikan dengan pilihan jam yang Anda tentukan pada saat memesan kamar.',
                'answer_en' => 'Check-in and check-out times are customized according to the hours you selected when booking your room.',
            ],
            [
                'question_id' => 'Bagaimana cara membatalkan pembayaran?',
                'question_en' => 'How do I cancel my payment?',
                'answer_id' => 'Buka halaman pesanan, lalu klik Batalkan pembayaran. Refund 100% lebih dari 24 jam sebelum check-in, 50% untuk 6–24 jam, dan tanpa refund jika kurang dari 6 jam.',
                'answer_en' => 'Open your order page and click Cancel payment. You get a 100% refund more than 24 hours before check-in, 50% for 6–24 hours, and no refund under 6 hours.',
            ],
            [
                'question_id' => 'Berapa lama proses refund?',
                'question_en' => 'How long does a refund take?',
                'answer_id' => 'Refund diproses dalam 3–7 hari kerja ke metode pembayaran Anda.',
                'answer_en' => 'Refunds are processed within 3–7 business days to your payment method.',
            ],
            [
                'question_id' => 'Bagaimana memakai e-voucher?',
                'question_en' => 'How do I use my e-voucher?',
                'answer_id' => 'Tunjukkan kode e-voucher di pesanan Anda kepada petugas resto & cafe, sesuai voucher kamar Anda.',
                'answer_en' => "Show the e-voucher code in your order to the restaurant and cafe staff, depending on your room's voucher.",
            ],
        ];

        foreach ($faqs as $sortOrder => $faq) {
            Faq::updateOrCreate(
                ['question_id' => $faq['question_id']],
                [...$faq, 'sort_order' => $sortOrder + 1, 'is_active' => true],
            );
        }
    }
}
