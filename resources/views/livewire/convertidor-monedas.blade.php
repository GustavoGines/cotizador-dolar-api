<div class="max-w-md mx-auto bg-white dark:bg-zinc-800 rounded-2xl p-6 shadow-lg border border-zinc-700">
    <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
        💱 Convertidor de Monedas
    </h2>

    <div class="space-y-4">
        <!-- Dirección de conversión -->
        <div>
            <label for="direccion" class="block text-sm font-medium">Conversión</label>
            <select id="direccion" wire:model.live="direccion"
                class="mt-1 w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 p-2">
                <option value="usd_a_pesos">USD → ARS</option>
                <option value="pesos_a_usd">ARS → USD</option>
            </select>
        </div>

        <!-- Monto -->
        <div>
            <label for="valor" class="block text-sm font-medium">
                @if($direccion === 'usd_a_pesos')
                    Monto en USD
                @else
                    Monto en ARS
                @endif
            </label>
            <input type="number" step="0.01" id="valor" wire:model.live="valor"
                class="mt-1 w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 p-2" />
            @error('valor') 
                <p class="text-red-500 text-sm">{{ $message }}</p> 
            @enderror
        </div>

        <!-- Tipo de dólar -->
        <div>
            <label for="tipo" class="block text-sm font-medium">Tipo de Dólar</label>
            <select id="tipo" wire:model.live="tipo"
                class="mt-1 w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 p-2">
                <option value="oficial">Oficial</option>
                <option value="blue">Blue</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="mep">MEP</option>
                <option value="ccl">CCL</option>
            </select>
        </div>
    </div>

    <!-- Mostrar cotización -->
    @if($cotizacion)
        <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            💵 Cotización actual ({{ ucfirst($tipo) }}): 
            <span class="font-bold">${{ number_format($cotizacion, 2, ',', '.') }}</span> ARS
        </div>
    @endif

    <!-- Resultado -->
    @if($resultado)
        <div class="mt-4 p-3 bg-emerald-50 dark:bg-zinc-700 rounded-lg animate-fadeIn">
            <p class="text-lg">
                Resultado: 
                <span class="font-bold">
                    @if($direccion === 'usd_a_pesos')
                        ${{ number_format($resultado, 2, ',', '.') }} ARS
                    @else
                        {{ number_format($resultado, 2, ',', '.') }} USD
                    @endif
                </span>
            </p>
        </div>
    @endif

    <!-- Botón de promedios -->
    <div class="mt-6">
        <div class="flex gap-2 items-center mb-3">
            <select wire:model.live="anio" class="rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 p-2 text-sm">
                <option value="">Todos los Años</option>
                @for($y = now()->year; $y >= 2020; $y--)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>

            <select wire:model.live="mes" class="rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 p-2 text-sm">
                <option value="">Todos los Meses</option>
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->locale('es')->monthName }}</option>
                @endfor
            </select>

            <button wire:click="cargarPromedios"
                class="px-3 py-2 bg-emerald-600 text-white rounded-lg shadow hover:bg-emerald-700 text-sm">
                📊 Ver promedios
            </button>
        </div>

        <!-- Mostrar tabla de promedios -->
        @if($promedios && count($promedios) > 0)
            <table class="w-full text-sm border border-gray-300 dark:border-zinc-700 rounded-lg overflow-hidden">
                <thead class="bg-gray-100 dark:bg-zinc-700">
                    <tr>
                        <th class="px-3 py-2 text-left">Año</th>
                        <th class="px-3 py-2 text-left">Mes</th>
                        <th class="px-3 py-2 text-left">Promedio (ARS)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($promedios as $p)
                        <tr class="border-t border-gray-200 dark:border-zinc-700">
                            <td class="px-3 py-2">{{ $p['anio'] ?? '-' }}</td>
                            <td class="px-3 py-2">
                                {{ isset($p['mes']) ? \Carbon\Carbon::create()->month($p['mes'])->locale('es')->monthName : '-' }}
                            </td>
                            <td class="px-3 py-2 font-bold">
                                ${{ number_format($p['promedio'] ?? 0, 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @elseif($promedios && count($promedios) === 0)
            <p class="text-sm text-gray-500 dark:text-gray-400">No hay datos para ese período.</p>
        @endif
    </div>

    <!-- Footer -->
    <div class="mt-6 text-center text-xs text-gray-500 dark:text-gray-400">
        <p>Desarrollado por <span class="font-semibold">Gustavo Ginés</span></p>
    </div>
</div>
