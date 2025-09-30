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

    <!-- Filtros (se mantienen) + Ver promedios -->
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

        <!-- Vista paginada por año (aparece recién al tocar Ver promedios) -->
        @if($promediosVisibles)
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <button
                        wire:click="prevYear"
                        class="h-8 w-8 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ $anioActual <= $anioMin ? 'opacity-40 pointer-events-none' : '' }}"
                        title="Año anterior"
                    >
                        ←
                    </button>

                    <div class="px-3 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-700 text-sm">
                        {{ $anioActual }}
                    </div>

                    <button
                        wire:click="nextYear"
                        class="h-8 w-8 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ $anioActual >= $anioMax ? 'opacity-40 pointer-events-none' : '' }}"
                        title="Año siguiente"
                    >
                        →
                    </button>
                </div>
            </div>

            <table class="w-full text-sm border border-gray-300 dark:border-zinc-700 rounded-lg overflow-hidden">
                <thead class="bg-gray-100 dark:bg-zinc-700">
                    <tr>
                        <th class="px-3 py-2 text-left">Mes</th>
                        <th class="px-3 py-2 text-left">Promedio (ARS)</th>
                    </tr>
                </thead>
                <tbody>
                    @if($mes) {{-- Mostrar SOLO el mes seleccionado --}}
                        @php
                            $row = $promedios[0] ?? null;
                            $nombreMes = \Carbon\Carbon::create($anioActual, (int)$mes, 1)->locale('es')->monthName;
                        @endphp
                        <tr class="border-t border-gray-200 dark:border-zinc-700 bg-emerald-50 dark:bg-emerald-900/20">
                            <td class="px-3 py-2 capitalize">
                                {{ $nombreMes }}
                                <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-emerald-200 text-emerald-900 dark:bg-emerald-800 dark:text-emerald-100">
                                    filtrado
                                </span>
                            </td>
                            <td class="px-3 py-2 font-bold">
                                @if($row && $row['promedio'] !== null)
                                    ${{ number_format($row['promedio'], 2, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @else {{-- Mostrar los 12 meses del año en pantalla --}}
                        @for($m = 1; $m <= 12; $m++)
                            @php
                                $row = collect($promedios)->firstWhere('mes', $m);
                                $nombreMes = \Carbon\Carbon::create($anioActual, $m, 1)->locale('es')->monthName;
                            @endphp
                            <tr class="border-t border-gray-200 dark:border-zinc-700">
                                <td class="px-3 py-2 capitalize">{{ $nombreMes }}</td>
                                <td class="px-3 py-2 font-bold">
                                    @if($row && $row['promedio'] !== null)
                                        ${{ number_format($row['promedio'], 2, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endfor
                    @endif
                </tbody>

            </table>
        @endif
    </div>

    <!-- Promo App Móvil -->
    <div class="mt-6 rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 p-4 md:p-5 flex flex-col sm:flex-row items-start gap-4">
      <div class="text-3xl md:text-4xl">📱</div>
      <div class="flex-1">
        <h3 class="text-base md:text-lg font-semibold">Descargá la App Móvil</h3>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
          Consultá cotizaciones al instante y convertí USD⇄ARS desde tu celular. Ligera, rápida y sin complicaciones.
        </p>

        <div class="mt-3 flex flex-wrap items-center gap-2">
          <a
            href="{{ config('app.apk_url') ?? 'https://github.com/GustavoGines/cotizador-dolar-api/releases/latest/download/app-release.apk' }}"
            rel="noopener"
            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm shadow"
          >
            ⬇️ Descargar APK Android
          </a>

          <span
            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 text-sm cursor-not-allowed"
            title="Próximamente"
            aria-disabled="true"
          >
            ▶️ Próximamente en Play Store
          </span>
        </div>

        <p class="mt-2 text-[11px] text-zinc-500 dark:text-zinc-400">
          *Si instalás el APK por primera vez, activá “Permitir apps de orígenes desconocidos” en tu Android.
        </p>
      </div>

      <!-- QR visible en mobile y desktop -->
      <div class="mt-4 w-full flex sm:w-auto sm:block">
          <img
            src="{{ route('qr.apk') }}"
            alt="QR descarga APK"
            class="w-28 h-28 sm:w-24 sm:h-24 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white p-1 mx-auto sm:mx-0"
            loading="lazy"
            decoding="async"
          />
          <p class="mt-1 text-[10px] text-center text-zinc-500 dark:text-zinc-400 w-full sm:w-auto">
            Escaneá el QR
          </p>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-6 text-center text-xs text-gray-500 dark:text-gray-400">
        <p>Desarrollado por <span class="font-semibold">Gustavo Ginés</span></p>
    </div>
</div>
