<?php

namespace Laraditz\TikTok\Console;

use TikTok;
use Illuminate\Console\Command;
use Laraditz\TikTok\Models\TiktokAccessToken;
use Laraditz\TikTok\Exceptions\TikTokAPIError;

class RefreshTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok:refresh-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh existing access token before it expired.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = $this->getQuery();

        $query->lazy()->each(function ($item) {
            $this->info(__('<fg=yellow>Refreshing :subjectable access token.</>', ['subjectable' => $item->subjectable?->name ?? '']));
            try {
                TikTok::auth()->refreshToken();
            } catch (TikTokAPIError $th) {
                // dd($th->getResult());
            } catch (\Throwable $th) {
                //throw $th;
                // dd($th);
            }

            $this->info(__(':subjectable access token was refresh.', ['subjectable' => $item->subjectable?->name ?? 'The']));
        });
    }

    private function getQuery()
    {
        $query = TiktokAccessToken::query();

        $query->where('refresh_expires_at', '>', now());

        return $query;
    }
}
