<?php $items = $checkout->items(); ?>

<a href='#!' data-items='{!! json_encode($items) !!}' {{ $attributes->merge(['class' => 'paddle_button']) }}>
    {{ $slot }}
</a>
