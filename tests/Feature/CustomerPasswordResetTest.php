<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class CustomerPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_forgot_password_page_can_be_rendered(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Recuperar contraseña');
    }

    public function test_a_reset_link_is_sent_to_the_customer(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create();

        $this->post(route('password.email'), ['email' => $customer->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($customer, ResetPassword::class);
    }

    public function test_a_customer_can_reset_their_password(): void
    {
        $customer = Customer::factory()->create();
        $token = Password::broker('customers')->createToken($customer);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $customer->email,
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('nueva-clave-segura', $customer->fresh()->password));
    }

    public function test_an_invalid_token_does_not_change_the_password(): void
    {
        $customer = Customer::factory()->create();

        $this->post(route('password.store'), [
            'token' => 'token-invalido',
            'email' => $customer->email,
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $customer->fresh()->password));
    }
}
