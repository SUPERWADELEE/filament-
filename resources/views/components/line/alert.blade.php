@props([
    'type' => 'info',
    'icon' => 'info-circle',
    'title' => null
])

@php
    $bgColor = [
        'info' => 'bg-blue-50',
        'success' => 'bg-green-50',
        'warning' => 'bg-yellow-50',
        'error' => 'bg-red-50'
    ][$type] ?? 'bg-blue-50';
    
    $borderColor = [
        'info' => 'border-blue-400',
        'success' => 'border-green-400',
        'warning' => 'border-yellow-400',
        'error' => 'border-red-400'
    ][$type] ?? 'border-blue-400';
    
    $textColor = [
        'info' => 'text-blue-700',
        'success' => 'text-green-700',
        'warning' => 'text-yellow-700',
        'error' => 'text-red-700'
    ][$type] ?? 'text-blue-700';
    
    $iconColor = [
        'info' => 'text-blue-400',
        'success' => 'text-green-400',
        'warning' => 'text-yellow-400',
        'error' => 'text-red-400'
    ][$type] ?? 'text-blue-400';
@endphp

<div class="{{ $bgColor }} border-l-4 {{ $borderColor }} p-4 rounded-lg mb-6">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-{{ $icon }} {{ $iconColor }}"></i>
        </div>
        <div class="ml-3">
            @if($title)
                <p class="text-sm {{ $textColor }} font-bold">{{ $title }}</p>
            @endif
            <p class="text-sm {{ $textColor }}">
                {{ $slot }}
            </p>
        </div>
    </div>
</div> 