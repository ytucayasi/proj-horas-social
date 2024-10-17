<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>
<nav class="max-w-7xl mx-auto flex justify-between items-center p-4 2xl:px-0 h-fit">

    <!-- Rigth Menu -->
    <div class="flex space-x-2 items-center h-fit">

        <!-- Brand -->
        <div class="rounded-full w-8 h-8 flex items-center justify-center">
            <span class="dark:text-white select-none">yt</span>
        </div>

        <!-- Manu Bar -->
        <ul class="flex space-x-2 items-center h-fit">
            @foreach (config('navbar') as $item)
                <li class="flex">
                    <a href="{{ $item['href'] }}" class=" p-2 rounded-md hover:text-primary-500 dark:hover:text-primary-300"
                        wire:navigate>
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <!-- Left Menu -->
    <div class="flex items-center space-x-2">
        @if (Auth::user())
            <!-- Actions -->
            <div class="flex items-center">
                <button class="p-2 rounded-md">
                    <x-icon name="sun" class="w-5 h-5 hover:text-secondary-500 dark:hover:text-yellow-500" solid mini
                        x-show="darkMode" @click="toggleTheme()" />
                    <x-icon name="moon" class="w-5 h-5 hover:text-info-700" solid mini x-show="!darkMode"
                        @click="toggleTheme()" />
                </button>
            </div>
            <!-- Dropdown -->
            <x-dropdown position="bottom-end" class="fit">
                <x-slot name="trigger">
                    <div
                        class="flex items-center space-x-1 h-fit select-none hover:text-primary-500 dark:hover:text-primary-300">
                        <div class="flex items-center">
                            <template x-if="!positionable.state">
                                <x-icon name="chevron-down" solid mini />
                            </template>
                            <template x-if="positionable.state">
                                <x-icon name="chevron-up" solid mini />
                            </template>
                        </div>
                        <span class="">{{ Auth::user()->name }}</span>
                    </div>
                </x-slot>
                <x-dropdown.item icon="link" label="Dashboard" href="/dashboard" wire:navigate
                    class="block sm:hidden dark:hover:text-white" />
                <x-dropdown.item icon="link" label="Usuarios" href="/usuarios" wire:navigate
                    class="block sm:hidden dark:hover:text-white" />
                <x-dropdown.item icon="link" label="Roles" href="/roles" wire:navigate
                    class="block sm:hidden dark:hover:text-white" />
                <x-dropdown.item icon="link" label="Permisos" href="/permisos" wire:navigate
                    class="block sm:hidden dark:hover:text-white" />
                <x-dropdown.item icon="link" label="Escuelas" href="/escuelas" wire:navigate
                    class="block sm:hidden dark:hover:text-white" />
                <x-dropdown.item icon="link" label="Periodos" href="/periodos" wire:navigate
                    class="block sm:hidden dark:hover:text-white" />
                <x-dropdown.item icon="link" href="/estudiantes" wire:navigate label="Estudiantes"
                    class="block sm:hidden dark:hover:text-white" />
                <x-dropdown.item icon="link" label="Actividades" href="/actividades" wire:navigate
                    class="block sm:hidden dark:hover:text-white" />

                <x-dropdown.item wire:click="logout" icon="power" label="Cerrar Sesión" class="dark:hover:text-white" />
            </x-dropdown>
        @else
            <x-link label="Admin" href="/login" wire:navigate />
        @endif
    </div>
</nav>