<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    /**
     * Process payment for an order.
     *
     * @param Order $order
     * @param int $userId
     * @return array
     * @throws \Exception
     */
    public function processPayment(Order $order, int $userId): array
    {
        // Validate ownership
        if ($order->user_id !== $userId) {
            throw ValidationException::withMessages([
                'user_id' => ['This order does not belong to the given user.'],
            ]);
        }

        // Validate status
        if ($order->status !== 'pending') {
            throw new \Exception('Order status is not pending. Payment cannot be processed.');
        }

        // Transaction ensures atomicity
        return DB::transaction(function () use ($order, $userId) {

            // Update order status
            $order->update(['status' => 'paid']);

            // Update user points
            $user = User::findOrFail($userId);

            // Base points: total price
            $bonusPoints = $order->total_price;

            // Bonus points if total >= 100
            if ($order->total_price >= 100) {
                $bonusPoints += 10;
            }

            $user->credit_points += $bonusPoints;
            $user->save();

            // Return structured result
            return [
                'order' => $order->fresh(),
                'user_credit_points' => $user->credit_points,
            ];
        });
    }
}
