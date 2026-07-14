<div class="space-y-4 text-sm">
    <p>{{ $classification['message'] }}</p>

    @if($classification['uses']['configuration'])
        <div>
            <h3 class="font-semibold">Configuración</h3>
            <ul class="mt-2 list-disc pl-5">
                @foreach($classification['uses']['configuration'] as $use)
                    <li>{{ $use['label'] }}. Sustitúyela desde Configuración antes de eliminar.</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($classification['uses']['active'])
        <div>
            <h3 class="font-semibold">Publicación activa</h3>
            <ul class="mt-2 list-disc pl-5">
                @foreach($classification['uses']['active'] as $use)
                    <li>Diseño "{{ $use['layout_name'] }}", zona {{ $use['zone_position'] }}.</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($classification['uses']['inactive'])
        <div>
            <h3 class="font-semibold">Diseños no activos</h3>
            <ul class="mt-2 list-disc pl-5">
                @foreach($classification['uses']['inactive'] as $use)
                    <li>
                        <a class="text-primary-600 underline" href="{{ route('visual-editor', $use['layout_id']) }}">
                            {{ $use['layout_name'] }}
                        </a>,
                        zona {{ $use['zone_position'] }}.
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!$classification['uses']['configuration'] && !$classification['uses']['active'] && !$classification['uses']['inactive'])
        <p>Este contenido no se utiliza en ningún diseño ni ajuste.</p>
    @endif
</div>
