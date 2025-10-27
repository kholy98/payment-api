<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService)
    {
    }

    public function pay(PaymentRequest $request, Order $order): JsonResponse
    {
        try {
            $result = $this->paymentService->processPayment($order, $request->user_id);

            return response()->json([
                'message' => 'Payment processed successfully.',
                'order' => new OrderResource($result['order']),
                'user_credit_points' => $result['user_credit_points'],
            ], 200);

        } catch (ValidationException $e) {

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
