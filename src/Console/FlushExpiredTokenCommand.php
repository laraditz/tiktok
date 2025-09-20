<?php

namespace Laraditz\TikTok\Console;

use Illuminate\Console\Command;
use Laraditz\TikTok\Models\TiktokAccessToken;

class FlushExpiredTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok:flush-expired-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush expired access token.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->shouldDelete()) {
            $this->info('You have cancelled the command.');
            return;
        }

        $query = $this->getQuery();
        $count = 0;

        $query->lazy()->each(function ($item) use (&$count) {
            $this->info(__('<fg=yellow>Deleting :subjectable access token.</>', ['subjectable' => $item->subjectable?->name ?? '']));
            if ($item->delete()) {
                $count++;
                $this->info(__(':subjectable access token was deleted.', ['subjectable' => $item->subjectable?->name ?? 'The']));
            }
        });

        $this->newLine();

        if ($count > 0) {
            $this->info(__(':count expired access tokens were deleted.', ['count' => $count]));
        } else {
            $this->info(__('No expired token.'));
        }
    }

    private function getQuery()
    {
        $query = TiktokAccessToken::query();

        $query->where('expires_at', '<=', now());

        return $query;
    }

    private function shouldDelete()
    {
        return $this->confirm(
            'You are about to remove expired TikTok access tokens. Continue?',
            false
        );
    }
}
