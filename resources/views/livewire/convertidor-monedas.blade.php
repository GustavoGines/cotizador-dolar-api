<div class="max-w-md mx-auto bg-white dark:bg-zinc-800 rounded-2xl p-6 shadow-lg border border-zinc-700">
    <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
        💱 Convertidor de Monedas
    </h2>

    <div class="space-y-4">
        <!-- Monto en USD -->
        <div>
            <label class="block text-sm font-medium">Monto en USD</label>
            <input type="number" step="0.01" wire:model.live="valorUsd"
                class="mt-1 w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 p-2" />
            @error('valorUsd') 
                <p class="text-red-500 text-sm">{{ $message }}</p> 
            @enderror
        </div>

        <!-- Tipo de dólar -->
        <div>
            <label class="block text-sm font-medium">Tipo de Dólar</label>
            <select wire:model.live="tipo"
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
                <span class="font-bold">${{ number_format($resultado, 2, ',', '.') }}</span> ARS
            </p>
        </div>
    @endif

    <!-- Footer -->
    <div class="mt-6 text-center text-xs text-gray-500 dark:text-gray-400">
        <p>Desarrollado por <span class="font-semibold">Gustavo Ginés</span></p>
    </div>

</div>
