<a href='#!' :data-items='$checkout->items()' {{ $attributes->merge(['class' => 'paddle_button']) }}>
    {{ $slot }}
</a>
