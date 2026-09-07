<?php

namespace Tests\Unit;

use App\Http\Requests\StoreCurrencyRequest;
use App\Http\Requests\UpdateCurrencyRequest;
use App\Models\Currency;
use App\Models\User;
use App\Policies\CurrencyPolicy;
use Tests\TestCase;

class ApplicationScaffoldingTest extends TestCase
{
    public function test_currency_policy_denies_all_operations(): void
    {
        $policy = new CurrencyPolicy();
        $user = new User();
        $currency = new Currency();

        $this->assertFalse($policy->viewAny($user));
        $this->assertFalse($policy->view($user, $currency));
        $this->assertFalse($policy->create($user));
        $this->assertFalse($policy->update($user, $currency));
        $this->assertFalse($policy->delete($user, $currency));
        $this->assertFalse($policy->restore($user, $currency));
        $this->assertFalse($policy->forceDelete($user, $currency));
    }

    public function test_currency_requests_are_unauthorized_and_have_no_rules(): void
    {
        $storeRequest = new StoreCurrencyRequest();
        $updateRequest = new UpdateCurrencyRequest();

        $this->assertFalse($storeRequest->authorize());
        $this->assertSame([], $storeRequest->rules());
        $this->assertFalse($updateRequest->authorize());
        $this->assertSame([], $updateRequest->rules());
    }

    public function test_user_model_defines_expected_attributes_and_casts(): void
    {
        $user = new User();

        $this->assertSame(['name', 'email', 'password'], $user->getFillable());
        $this->assertSame(['password', 'remember_token'], $user->getHidden());
        $this->assertSame('datetime', $user->getCasts()['email_verified_at']);
        $this->assertSame('hashed', $user->getCasts()['password']);
    }
}
