<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Audit Log PIC</title>
    <style>
        @page { margin: 14mm 10mm 16mm 10mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #111827; }
        h1 { font-size: 12pt; margin: 0 0 1.5mm; }
        .meta { margin-bottom: 4mm; color: #374151; font-size: 7.5pt; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { border: 0.5pt solid #b8c2d6; padding: 1.8mm 2mm; vertical-align: top; word-wrap: break-word; }
        th { background: #1f3a68; color: #ffffff; font-weight: bold; text-align: left; }
        tbody tr:nth-child(even) td { background: #eef2f9; }
        td.no { text-align: center; }
        td.empty { text-align: center; }
        td.purpose, td.result { white-space: pre-line; }
    </style>
</head>
<body>
    <h1>Audit Log PIC — {{ config('sitamu.institution_name') }}</h1>
    <div class="meta">
        Jumlah baris: {{ $rowCount }}
        @if ($filters !== [])
            · Filter: {{ implode(' · ', $filters) }}
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
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row['values'] as $value)
                        @if ($loop->first)
                            <td class="no">{{ $value }}</td>
                        @elseif ($loop->last)
                            <td class="result {{ $value === '–' ? 'empty' : '' }}">{{ $value }}</td>
                        @elseif ($loop->remaining === 1)
                            <td class="purpose">{{ $value }}</td>
                        @else
                            <td>{{ $value }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="empty">Tidak ada aksi PIC yang cocok dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
