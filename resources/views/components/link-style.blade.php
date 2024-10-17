@props(['link', 'label', 'active' => 'false'])

@php
    $classes = ($active ?? false)
        ? 'w-full ps-2 py-1 border-s-2 border-primary-500 text-primary-500 dark:text-primary-300'
        : 'w-full ps-2 py-1 border-s-2 border-transparent hover:border-primary-300 hover:text-primary-300 dark:hover:text-primary-300';
@endphp

<li class="flex w-full h-fit">
    <a href="{{ $link }}" {{ $attributes->merge(['class' => $classes]) }} wire:navigate>{{ $label }}</a>
</li>