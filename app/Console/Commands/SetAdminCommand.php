<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetAdminCommand extends Command
{
    protected $signature = 'user:set-admin
        {identifier : User ID, phone number, or email}
        {--remove : Remove admin rights instead of granting them}';

    protected $description = 'Grant or remove administrator rights for a user.';

    public function handle(): int
    {
        $identifier = (string) $this->argument('identifier');
        $user = $this->findUser($identifier);

        if (! $user) {
            $this->error('Користувача не знайдено.');

            return self::FAILURE;
        }

        $makeAdmin = ! $this->option('remove');
        $user->forceFill(['is_admin' => $makeAdmin])->save();

        $status = $makeAdmin ? 'призначено адміністратором' : 'позбавлено прав адміністратора';
        $this->info("Користувача {$user->name} ({$user->phone}) {$status}.");

        return self::SUCCESS;
    }

    private function findUser(string $identifier): ?User
    {
        return User::query()
            ->where('id', ctype_digit($identifier) ? (int) $identifier : 0)
            ->orWhere('phone', $identifier)
            ->orWhere('email', $identifier)
            ->first();
    }
}
