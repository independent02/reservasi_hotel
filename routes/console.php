 <?php

 use App\Models\User;
 use Illuminate\Support\Facades\Artisan;
 use Illuminate\Support\Str;
 use Symfony\Component\Console\Command\Command;

 Artisan::command('app:make-admin {email}', function (string $email): int {
     $email = Str::lower(trim($email));
     $allowedEmails = config('payments.admin_emails', []);

     if (! in_array($email, $allowedEmails, true)) {
         $this->error('Email tidak tercantum dalam PAYMENT_ADMIN_EMAILS di .env.');

         return Command::FAILURE;
     }

     $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
     if (! $user) {
         $this->error('Akun belum terdaftar. Daftarkan akun terlebih dahulu melalui portal pemesan.');

         return Command::FAILURE;
     }

     $user->forceFill(['role' => 'admin'])->save();
     $this->info('Akun berhasil diberi peran admin. Masuk melalui /admin/login.');

     return Command::SUCCESS;
 })->purpose('Promote an allowlisted registered account to hotel admin');
