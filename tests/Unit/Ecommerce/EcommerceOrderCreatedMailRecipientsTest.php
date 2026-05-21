<?php

namespace Tests\Unit\Ecommerce;

use App\Models\Shop;
use App\Models\Tenant;
use App\Services\DynamicMailSettingsService;
use App\Services\EcommerceOrderCreatedMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Src\Application\Settings\UseCases\GetStoreSettingsUseCase;
use Tests\TestCase;

class EcommerceOrderCreatedMailRecipientsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_collects_shop_and_store_settings_emails_without_duplicates(): void
    {
        $tenant = Tenant::factory()->create(['sector' => 'commerce']);
        $shop = Shop::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Boutique test',
            'code' => 'SHOP-MAIL-1',
            'type' => 'online',
            'email' => 'boutique@example.com',
        ]);

        $settingsMock = $this->createMock(GetStoreSettingsUseCase::class);
        $settingsEntity = $this->createMock(\Src\Domain\Settings\Entities\StoreSettings::class);
        $settingsEntity->method('getEmail')->willReturn('parametres@example.com');
        $settingsMock->method('execute')->with((string) $shop->id)->willReturn($settingsEntity);

        $service = new EcommerceOrderCreatedMailService(
            $this->createMock(DynamicMailSettingsService::class),
            $settingsMock
        );

        $ref = new ReflectionClass($service);
        $method = $ref->getMethod('resolveShopRecipientEmails');
        $method->setAccessible(true);

        $emails = $method->invoke($service, (string) $shop->id, 'client@example.com');

        $this->assertCount(2, $emails);
        $this->assertContains('boutique@example.com', $emails);
        $this->assertContains('parametres@example.com', $emails);
    }

    #[Test]
    public function it_excludes_customer_email_from_shop_recipients(): void
    {
        $tenant = Tenant::factory()->create(['sector' => 'commerce']);
        $shop = Shop::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Boutique test 2',
            'code' => 'SHOP-MAIL-2',
            'type' => 'online',
            'email' => 'client@example.com',
        ]);

        $settingsMock = $this->createMock(GetStoreSettingsUseCase::class);
        $settingsMock->method('execute')->willReturn(null);

        $service = new EcommerceOrderCreatedMailService(
            $this->createMock(DynamicMailSettingsService::class),
            $settingsMock
        );

        $ref = new ReflectionClass($service);
        $method = $ref->getMethod('resolveShopRecipientEmails');
        $method->setAccessible(true);

        $emails = $method->invoke($service, (string) $shop->id, 'client@example.com');

        $this->assertSame([], $emails);
    }
}
