<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Buku Tamu</title>
    <style>
        @page { margin: 16mm 10mm 14mm 10mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8pt; color: #111827; }
        h1 { font-size: 11pt; margin: 0 0 2mm; }
        .meta { margin-bottom: 3mm; color: #374151; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { border: 0.5pt solid #9ca3af; padding: 1mm; vertical-align: top; word-wrap: break-word; }
        th { background: #e5e7eb; font-weight: bold; text-align: center; vertical-align: middle; }
        td.no { text-align: center; }
        img { max-width: 100%; height: auto; }
    </style>
</head>
<body>
    <h1>BUKU TAMU — {{ config('sitamu.institution_name') }}</h1>
    <div class="meta">
        Lokasi: {{ $locationName }} ·
        @if (! empty($scopeNote))
            {{ $scopeNote }} ·
        @endif
        Dibuat: {{ now()->format('d/m/Y H:i') }} ·
        Jumlah baris: {{ $rowCount }}
        @if ($filters !== [])
            <br>Filter: {{ implode(' · ', $filters) }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th @isset($widths[$loop->index]) style="width: {{ $widths[$loop->index] }}%" @endisset>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($row['values'] as $value)
                        <td @if ($loop->first) class="no" @endif>{{ $value }}</td>
                    @endforeach
                    <td>
                        @if ($row['signature'])
                            <img src="{{ $row['signature'] }}" alt="Tanda tangan">
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
