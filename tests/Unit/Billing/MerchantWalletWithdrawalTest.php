<?php

namespace Tests\Unit\Billing;

use App\Models\Tenant;
use App\Models\User;
use App\Services\AppNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Infrastructure\Billing\Models\MerchantWalletBalance;
use Src\Infrastructure\Billing\Services\MerchantWalletService;
use Tests\TestCase;

class MerchantWalletWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    public function test_withdrawal_applies_plan_fee_percent_from_root_billing_plan(): void
    {
        $tenant = Tenant::factory()->create(['sector' => 'commerce']);
        $proPlanId = DB::table('billing_plans')->where('code', 'pro')->value('id');
        $this->assertNotNull($proPlanId);

        DB::table('tenant_plan_subscriptions')->insert([
            'tenant_id' => $tenant->id,
            'billing_plan_id' => $proPlanId,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => null,
            'trial_ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $feePercent = (float) DB::table('billing_plans')->where('id', $proPlanId)->value('withdrawal_fee_percent');
        $this->assertGreaterThan(0, $feePercent);

        MerchantWalletBalance::query()->create([
            'tenant_id' => (string) $tenant->id,
            'currency_code' => 'USD',
            'available_balance' => 500,
            'pending_balance' => 0,
            'locked_balance' => 0,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        $service = new MerchantWalletService($this->createMock(AppNotificationService::class));

        $estimate = $service->estimateWithdrawalNet((string) $tenant->id, 100);
        $this->assertSame($feePercent, $estimate['withdrawal_fee_percent']);
        $this->assertSame(round(100 * $feePercent / 100, 2), $estimate['fee_amount']);
        $this->assertSame(round(100 - $estimate['fee_amount'], 2), $estimate['net_amount']);

        $withdrawal = $service->createWithdrawalRequest($user, [
            'currency_code' => 'USD',
            'requested_amount' => 100,
            'destination_type' => 'mobile_money',
            'destination_reference' => '+243900000000',
        ]);

        $this->assertSame(100.0, (float) $withdrawal->requested_amount);
        $this->assertSame($estimate['fee_amount'], (float) $withdrawal->fee_amount);
        $this->assertSame($estimate['net_amount'], (float) $withdrawal->net_amount);
        $this->assertSame('pending', (string) $withdrawal->status);

        $balance = MerchantWalletBalance::query()
            ->where('tenant_id', (string) $tenant->id)
            ->where('currency_code', 'USD')
            ->first();

        $this->assertNotNull($balance);
        $this->assertSame(400.0, (float) $balance->available_balance);
        $this->assertSame(100.0, (float) $balance->locked_balance);
    }

    public function test_estimate_withdrawal_net_returns_zero_fee_without_active_plan(): void
    {
        $tenant = Tenant::factory()->create(['sector' => 'commerce']);
        $service = new MerchantWalletService($this->createMock(AppNotificationService::class));

        $estimate = $service->estimateWithdrawalNet((string) $tenant->id, 50);

        $this->assertSame(0.0, $estimate['withdrawal_fee_percent']);
        $this->assertSame(0.0, $estimate['fee_amount']);
        $this->assertSame(50.0, $estimate['net_amount']);
    }
}
