# Payment API

A Laravel-based Payment API system that handles order payments and user credit points management.

## Prerequisites

- PHP >= 8.2
- Composer
- Node.js & NPM
- XAMPP (or similar local development environment)
- Postman (for API testing)

## Project Setup

1. Clone the repository:
```bash
git clone https://github.com/kholy98/payment-api.git
cd payment-api
```

2. Install PHP dependencies:
```bash
composer install
```

3. Create environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Configure your database in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=payment_api
DB_USERNAME=root
DB_PASSWORD=
```

6. Run database migrations:
```bash
php artisan migrate
```

7. Install frontend dependencies (if needed):
```bash
npm install
npm run build
```

8. Start the development server:
```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## API Testing

### Manual Testing with Postman

#### Payment Processing Endpoint

- **URL**: `POST /api/orders/{orderId}/pay`
- **Headers**:
  - `Accept: application/json`
  - `Content-Type: application/json`
- **Body**:
```json
{
    "user_id": 1
}
```

#### Expected Responses

1. **Successful Payment**
- Status: 200 OK
```json
{
    "message": "Payment processed successfully.",
    "order": {
        "status": "paid"
    }
}
```

2. **Invalid Order Status**
- Status: 400 Bad Request
```json
{
    "message": "Order status is not pending. Payment cannot be processed."
}
```

3. **Wrong User**
- Status: 400 Bad Request
```json
{
    "message": "Invalid user for this order.",
    "errors": {
        "user_id": ["The user ID does not match the order's user."]
    }
}
```

4. **Missing User ID**
- Status: 422 Unprocessable Entity
```json
{
    "message": "The user id field is required.",
    "errors": {
        "user_id": ["The user id field is required."]
    }
}
```

### Automated Testing

The project includes comprehensive automated tests. To run the tests:

```bash
php artisan test
```

#### Test Cases

1. `it_returns_successful_response_on_valid_payment`
   - Creates a pending order
   - Processes payment
   - Verifies order status change to 'paid'
   - Checks user credit points increase

2. `it_returns_error_if_order_not_pending`
   - Attempts to pay an already paid order
   - Verifies error response

3. `it_returns_validation_error_if_wrong_user`
   - Attempts to pay an order with wrong user
   - Verifies validation error response

4. `it_validates_user_id_field`
   - Attempts to pay without user_id
   - Verifies validation error response

## Business Rules

- Orders must be in 'pending' status to process payment
- Only the order's owner can process the payment
- User receives bonus credit points after successful payment
- Payment processing updates both order status and user credit points

## Error Handling

The API includes comprehensive error handling for:
- Invalid order status
- User validation
- Missing required fields
- Database transaction failures

## Development

For local development, you can use the following command to start all services:

```bash
composer run dev
```

This will start:
- Laravel development server
- Queue worker
- Log watcher
- Vite development server
