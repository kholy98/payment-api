<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use PHPUnit\Framework\Attributes\Test;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_returns_successful_response_on_valid_payment()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_price' => 120,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/orders/{$order->id}/pay", [
            'user_id' => $this->user->id,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Payment processed successfully.',
                     'order' => ['status' => 'paid'],
                 ]);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'credit_points' => 130]); // 120 + 10 bonus
    }

    #[Test]
    public function it_returns_error_if_order_not_pending()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_price' => 150,
            'status' => 'paid',
        ]);

        $response = $this->postJson("/api/orders/{$order->id}/pay", [
            'user_id' => $this->user->id,
        ]);

        $response->assertStatus(400)
                 ->assertJson(['message' => 'Order status is not pending. Payment cannot be processed.']);
    }

    #[Test]
    public function it_returns_validation_error_if_wrong_user()
    {
        $order = Order::factory()->create([
            'user_id' => User::factory()->create()->id,
            'total_price' => 150,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/orders/{$order->id}/pay", [
            'user_id' => $this->user->id,
        ]);

        $response->assertStatus(400)
                 ->assertJsonStructure(['message', 'errors' => ['user_id']]);
    }

    #[Test]
    public function it_validates_user_id_field()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/orders/{$order->id}/pay", []); // missing user_id

        $response->assertStatus(422)
                 ->assertJsonStructure(['message', 'errors' => ['user_id']]);
    }
}
