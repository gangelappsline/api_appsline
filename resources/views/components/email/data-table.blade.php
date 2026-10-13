@props([
    'head'   => [],     // ['Concepto', 'Qty', 'Importe']
    'rows'   => [],     // [['Plan Pro', '1', '129,00 €'], ...]
    'footer' => [],     // [['Subtotal', '149,00 €'], ['Total', '180,29 €']]
])

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" class="data-table">
    <tr>
        @foreach ($head as $i => $column)
            <th class="{{ $i > 0 ? 'right' : '' }}"
                style="font-family:{{ config('appsline.font') }}; font-size:12px; letter-spacing:.8px; text-transform:uppercase; color:{{ config('appsline.colors.muted') }}; font-weight:700; text-align:{{ $i > 0 ? 'right' : 'left' }}; padding:0 0 10px 0; border-bottom:1px solid {{ config('appsline.colors.border') }};">
                {{ $column }}
            </th>
        @endforeach
    </tr>

    @foreach ($rows as $row)
    <tr>
        @foreach ($row as $i => $cell)
            <td class="{{ $i > 0 ? 'right' : '' }}"
                style="font-family:{{ config('appsline.font') }}; font-size:15px; line-height:24px; color:{{ config('appsline.colors.body') }}; text-align:{{ $i > 0 ? 'right' : 'left' }}; padding:13px 0; border-bottom:1px solid #EDF1F7;">
                {!! $cell !!}
            </td>
        @endforeach
    </tr>
    @endforeach

    {{-- Totales: se rellenan las columnas que faltan para alinear a la derecha --}}
    @foreach ($footer as $row)
        @php($pad = max(0, count($head) - count($row)))
        @php($isLast = $loop->last)
        <tr>
            @for ($p = 0; $p < $pad; $p++)
                <td style="padding:13px 0; border-bottom:{{ $isLast ? 'none' : '1px solid #EDF1F7' }};">&nbsp;</td>
            @endfor
            @foreach ($row as $i => $cell)
                <td @if ($loop->last) align="right" @endif
                    style="font-family:{{ config('appsline.font') }}; font-size:{{ $loop->last && $isLast ? '20px' : '15px' }}; line-height:{{ $loop->last && $isLast ? '26px' : '24px' }}; font-weight:{{ $loop->last ? '700' : '400' }}; color:{{ $loop->last && $isLast ? config('appsline.colors.primary') : config('appsline.colors.ink') }}; text-align:{{ $loop->last ? 'right' : 'left' }}; padding:13px 0; border-bottom:{{ $isLast ? 'none' : '1px solid #EDF1F7' }};">
                    {!! $cell !!}
                </td>
            @endforeach
        </tr>
    @endforeach
</table>
