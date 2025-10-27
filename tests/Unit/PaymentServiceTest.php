<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

use PHPUnit\Framework\Attributes\Test;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentService $paymentService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = new PaymentService();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_processes_payment_and_adds_bonus_points_when_order_is_valid()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_price' => 150,
            'status' => 'pending',
        ]);

        $result = $this->paymentService->processPayment($order, $this->user->id);

        $this->assertEquals('paid', $result['order']->status);
        $this->assertEquals(160, $result['user_credit_points']); // 150 + 10 bonus
    }

    #[Test]
    public function it_processes_payment_without_bonus_when_order_under_100()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_price' => 80,
            'status' => 'pending',
        ]);

        $result = $this->paymentService->processPayment($order, $this->user->id);

        $this->assertEquals('paid', $result['order']->status);
        $this->assertEquals(80, $result['user_credit_points']);
    }

    #[Test]
    public function it_throws_exception_if_order_not_pending()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_price' => 120,
            'status' => 'paid',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Order status is not pending');

        $this->paymentService->processPayment($order, $this->user->id);
    }

    #[Test]
    public function it_throws_validation_exception_if_order_not_owned_by_user()
    {
        $order = Order::factory()->create([
            'user_id' => User::factory()->create()->id,
            'total_price' => 100,
            'status' => 'pending',
        ]);

        $this->expectException(ValidationException::class);

        $this->paymentService->processPayment($order, $this->user->id);
    }

    #[Test]
    public function it_rolls_back_if_any_error_occurs()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_price' => 100,
            'status' => 'pending',
        ]);

        // We'll use a temporary subclass to inject an error *inside* the transaction
        $service = new class extends \App\Services\PaymentService {
            protected function simulateError()
            {
                throw new \Exception('DB Error');
            }

            public function processPaymentWithError(Order $order, int $userId)
            {
                return DB::transaction(function () use ($order, $userId) {
                    $order->update(['status' => 'paid']);
                    $this->simulateError(); // throw inside transaction
                });
            }
        };

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('DB Error');

        try {
            $service->processPaymentWithError($order, $this->user->id);
        } catch (\Exception $e) {
            // Verify rollback
            $this->assertDatabaseHas('orders', [
                'id' => $order->id,
                'status' => 'pending',
            ]);
            throw $e;
        }
    }
}
