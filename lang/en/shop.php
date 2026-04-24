<?php

return [
    'cart' => [
        'profile_incomplete' => 'Please complete your address and contact information before adding items to your cart.',
        'not_available' => "':name' is not available for purchase.",
        'empty' => 'Your cart is empty.',
        'voucher_invalid' => 'Invalid or expired voucher code.',
        'voucher_wrong_event' => 'This voucher is not valid for this event.',
    ],
    'checkout' => [
        'payment_method_disabled' => 'The selected payment method is not enabled.',
    ],
    'order' => [
        'only_on_site' => 'Only on-site orders can be manually confirmed.',
        'already_paid' => 'This order has already been marked as paid.',
    ],
    'ticket_type' => [
        'unavailable_upcoming' => 'Upcoming',
        'unavailable_expired' => 'No longer available',
        'unavailable_out_of_stock' => 'Out of Stock',
    ],
    'notifications' => [
        'order_confirmation' => [
            'subject' => 'Order Confirmation #:id',
            'greeting' => 'Hello :name,',
            'intro' => 'Thank you for your purchase! Here is your order summary:',
            'event_line' => '**Event:** :name',
            'subtotal_line' => '**Subtotal:** :amount',
            'discount_line' => '**Discount:** -:amount',
            'discount_voucher_line' => '**Discount (:code):** -:amount',
            'total_line' => '**Total:** :amount',
            'payment_line' => '**Payment:** :method',
            'action' => 'View My Tickets',
        ],
    ],
];
