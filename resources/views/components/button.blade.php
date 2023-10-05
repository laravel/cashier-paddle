<?php
$items = $checkout->getItems();
$customer = $checkout->getCustomer();
?>

<a
    href='#!'
    data-items='{!! json_encode($items) !!}'
    @if ($customer) data-customer-id='{{ $customer->paddle_id }}' @endif
    {{ $attributes->merge(['class' => 'paddle_button']) }}
>
    {{ $slot }}
</a>
