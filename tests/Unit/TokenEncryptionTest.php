<?php

namespace Laraditz\TikTok\Tests\Unit;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laraditz\TikTok\Models\TiktokAccessToken;
use Laraditz\TikTok\Models\TiktokShop;
use Laraditz\TikTok\Tests\TestCase;
use Laraditz\TikTok\TikTok;
use PHPUnit\Framework\Attributes\Test;

class TokenEncryptionTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        $app['config']->set('app.cipher', 'AES-256-CBC');
    }

    private function make(): TiktokAccessToken
    {
        return $this->createAccessToken([
            'access_token' => 'AT',
            'refresh_token' => 'RT',
        ]);
    }

    private function rawToken(TiktokAccessToken $t): object
    {
        return DB::table('tiktok_access_tokens')->where('id', $t->id)->first();
    }

    private function rawShop(): object
    {
        return DB::table('tiktok_shops')->where('id', 'test_shop_id')->first();
    }

    #[Test]
    public function test_default_stores_plaintext()
    {
        $t = $this->make();
        $this->assertSame('AT', $this->rawToken($t)->access_token);
        $this->assertSame('RT', $this->rawToken($t)->refresh_token);
        $this->assertSame('test_cipher', $this->rawShop()->cipher);
    }

    #[Test]
    public function test_missing_config_key_stores_plaintext()
    {
        config()->set('tiktok.encrypt_tokens', null);
        $t = $this->make();
        $this->assertSame('AT', $this->rawToken($t)->access_token);
        $this->assertSame('test_cipher', $this->rawShop()->cipher);
        $this->assertSame('AT', $t->fresh()->access_token);
        $this->assertSame('test_cipher', TiktokShop::find('test_shop_id')->cipher);
    }

    #[Test]
    public function test_enabled_stores_ciphertext_and_reads_plaintext()
    {
        config()->set('tiktok.encrypt_tokens', true);
        $t = $this->make();
        $this->assertNotSame('AT', $this->rawToken($t)->access_token);
        $this->assertSame('AT', Crypt::decryptString($this->rawToken($t)->access_token));
        $this->assertSame('RT', Crypt::decryptString($this->rawToken($t)->refresh_token));
        $this->assertSame('test_cipher', Crypt::decryptString($this->rawShop()->cipher));
        $this->assertSame('AT', $t->fresh()->access_token);
        $this->assertSame('RT', $t->fresh()->refresh_token);
        $this->assertSame('test_cipher', TiktokShop::find('test_shop_id')->cipher);
    }

    #[Test]
    public function test_enabled_reads_legacy_plaintext_row()
    {
        $t = $this->make();
        config()->set('tiktok.encrypt_tokens', true);
        $this->assertSame('AT', $t->fresh()->access_token);
        $this->assertSame('test_cipher', TiktokShop::find('test_shop_id')->cipher);
    }

    #[Test]
    public function test_disabled_after_enabled_still_reads_encrypted_row()
    {
        config()->set('tiktok.encrypt_tokens', true);
        $t = $this->make();
        config()->set('tiktok.encrypt_tokens', false);
        $this->assertSame('AT', $t->fresh()->access_token);
        $this->assertSame('test_cipher', TiktokShop::find('test_shop_id')->cipher);
    }

    #[Test]
    public function test_command_refuses_when_disabled()
    {
        $this->make();
        $this->artisan('tiktok:encrypt-tokens')
            ->expectsOutput('Enable tiktok.encrypt_tokens first.')
            ->assertExitCode(1);

        $this->assertSame('test_cipher', $this->rawShop()->cipher);
    }

    #[Test]
    public function test_command_encrypts_plaintext_rows_once()
    {
        $t = $this->make();
        config()->set('tiktok.encrypt_tokens', true);

        $this->artisan('tiktok:encrypt-tokens')
            ->expectsOutput('Encrypted 3 value(s).')
            ->assertExitCode(0);

        $this->assertNotSame('AT', $this->rawToken($t)->access_token);
        $this->assertNotSame('RT', $this->rawToken($t)->refresh_token);
        $this->assertNotSame('test_cipher', $this->rawShop()->cipher);

        $this->artisan('tiktok:encrypt-tokens')
            ->expectsOutput('Encrypted 0 value(s).')
            ->assertExitCode(0);

        $fresh = $t->fresh();
        $this->assertSame('AT', $fresh->access_token);
        $this->assertSame('RT', $fresh->refresh_token);
        $this->assertSame('test_cipher', TiktokShop::find('test_shop_id')->cipher);
    }

    #[Test]
    public function test_shop_cipher_is_encrypted_and_signing_still_works()
    {
        config()->set('tiktok.encrypt_tokens', true);
        $shop = $this->createTikTokShop(['cipher' => 'plain_cipher_value']);

        $this->assertNotSame('plain_cipher_value', $this->rawShop()->cipher);

        $tiktok = new TikTok();
        $tiktok->setShop(TiktokShop::find($shop->id));

        $this->assertSame('plain_cipher_value', $tiktok->getShopCipher());
    }

    #[Test]
    public function test_cipher_column_is_text()
    {
        $this->assertSame('text', Schema::getColumnType('tiktok_shops', 'cipher'));
    }
}
