<?php

namespace Laraditz\TikTok\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Laraditz\TikTok\Models\TiktokAccessToken;
use Laraditz\TikTok\Models\TiktokShop;

class EncryptTokensCommand extends Command
{
    protected $signature = 'tiktok:encrypt-tokens';

    protected $description = 'Encrypt existing plaintext TikTok access tokens, refresh tokens and shop ciphers.';

    public function handle()
    {
        if (!config('tiktok.encrypt_tokens', false)) {
            $this->error('Enable tiktok.encrypt_tokens first.');

            return 1;
        }

        $count = 0;

        $count += $this->encryptModel(TiktokAccessToken::class, ['access_token', 'refresh_token']);
        $count += $this->encryptModel(TiktokShop::class, ['cipher']);

        $this->info("Encrypted {$count} value(s).");

        return 0;
    }

    /**
     * @param class-string<Model> $modelClass
     */
    private function encryptModel(string $modelClass, array $columns): int
    {
        $count = 0;

        $modelClass::query()->chunkById(100, function ($rows) use (&$count, $columns) {
            foreach ($rows as $row) {
                $updates = [];

                foreach ($columns as $column) {
                    $raw = $row->getRawOriginal($column);

                    if ($raw === null || $this->isEncrypted($raw)) {
                        continue;
                    }

                    $updates[$column] = Crypt::encryptString($raw);
                    $count++;
                }

                if ($updates) {
                    DB::table($row->getTable())
                        ->where($row->getKeyName(), $row->getKey())
                        ->update($updates);
                }
            }
        }, $modelClass === TiktokShop::class ? 'id' : null);

        return $count;
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException $e) {
            return false;
        }
    }
}
