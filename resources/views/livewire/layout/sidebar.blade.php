<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public function handleLinkClick($route)
    {
        if (!Auth::user()->can('mostrar ' . $route)) {
            return redirect()->route('dashboard'); // Redirige si no tiene permiso
        }
        return $this->redirect(route($route), navigate: true);
    }
}; ?>
<nav class="w-80 h-full overflow-hidden select-none hidden sm:block">
    <div class="overflow-y-auto h-full px-4 py-6 flex flex-col space-y-6">

        <ul class="flex flex-col space-y-2">
            <!-- Dashboard e Info -->
            <x-link-style :link="route('dashboard')" :label="'Dashboard'" :active="request()->routeIs('dashboard')"
                wire:navigate />
            @if(auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Super Admin'))
                <x-link-style :link="route('panel-control')" :label="'Panel de Control'"
                    :active="request()->routeIs('panel-control')" />
                <x-link-style :link="route('logs')" :label="'Logs'"
                    :active="request()->routeIs('logs')" />
            @endif
            @foreach (config('sidebar') as $group)
                        @php
                            $mostrarGrupo = false;
                            foreach ($group['links'] as $link) {
                                if (auth()->user()->can('mostrar ' . $link['route'])) {
                                    $mostrarGrupo = true;
                                    break;
                                }
                            }
                        @endphp
                        @if ($mostrarGrupo)
                            <li
                                cslass="flex w-full h-fit pt-2 dark:border-t-[1px] dark:border-slate-700 border-t-[1px] border-slate-200">
                                <h2 class="text-xs uppercase">{{ $group['title'] }}</h2>
                            </li>
                        @endif
                        @foreach ($group['links'] as $link)
                            @can('mostrar ' . $link['route'])
                                <x-dinamic-link :access="$link['route']" :link="route($link['route'])" :label="$link['label']"
                                    :active="request()->routeIs($link['route'])" />
                            @endcan
                        @endforeach
            @endforeach
        </ul>
    </div>
</nav>