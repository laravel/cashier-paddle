<?php
$transactionId = data_get($checkout->getOptions(), 'is_from_catalog', true) ? null : $checkout->getTransactionId();
$items = $checkout->getItems();
$customer = $checkout->getCustomer();
$custom = $checkout->getCustomData();
?>

<a
    href='#!'
    @if($transactionId)
        data-transaction-id='{{$transactionId}}'
    @else
        data-items='{!! json_encode($items) !!}'
    @endif
    data-allow-logout='false'
    @if ($customer) data-customer-id='{{ $customer->paddle_id }}' @endif
    @if ($custom) data-custom-data='{{ json_encode($custom) }}' @endif
    @if ($returnUrl = $checkout->getReturnUrl()) data-success-url='{{ $returnUrl }}' @endif
    {{ $attributes->merge(['class' => 'paddle_button']) }}
>
    {{ $slot }}
</a>
