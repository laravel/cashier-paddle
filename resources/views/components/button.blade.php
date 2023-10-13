<?php
$items = $checkout->getItems();
$customer = $checkout->getCustomer();
?>

<a
    href='#!'
    data-items='{!! json_encode($items) !!}'
    @if ($customer) data-customer-id='{{ $customer->paddle_id }}' @endif
    @if ($returnUrl = $checkout->getReturnUrl()) data-success-url='{{ $returnUrl }}' @endif
    {{ $attributes->merge(['class' => 'paddle_button']) }}
>
    {{ $slot }}
</a>
