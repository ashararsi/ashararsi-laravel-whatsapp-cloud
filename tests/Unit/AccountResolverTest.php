<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Exceptions\AccountNotFoundException;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Services\AccountResolver;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class AccountResolverTest extends TestCase
{
    #[Test]
    public function it_resolves_default_account(): void
    {
        WhatsAppAccount::query()->create([
            'name' => 'primary',
            'phone_number' => '923001234567',
            'phone_number_id' => '123',
            'provider' => 'meta',
            'access_token' => 'token-a-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $resolver = new AccountResolver;
        $account = $resolver->resolve();

        $this->assertSame('primary', $account->name);
    }

    #[Test]
    public function it_resolves_account_by_name(): void
    {
        WhatsAppAccount::query()->create([
            'name' => 'marketing',
            'phone_number' => '923001234567',
            'phone_number_id' => '456',
            'provider' => 'meta',
            'access_token' => 'token-b-1234567890',
            'is_default' => false,
            'is_active' => true,
        ]);

        $resolver = new AccountResolver;
        $account = $resolver->resolve('marketing');

        $this->assertSame('marketing', $account->name);
    }

    #[Test]
    public function it_throws_when_account_not_found(): void
    {
        $this->expectException(AccountNotFoundException::class);

        (new AccountResolver)->resolve('missing');
    }
}
