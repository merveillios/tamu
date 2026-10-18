@extends('layouts.staff')

@section('title', 'Audit Log')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('admin.audit.index') }}" class="card card-body border-0 shadow mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label for="from" class="form-label small mb-1">Dari tanggal</label>
                <input type="date" name="from" id="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div class="col-6 col-md-2">
                <label for="to" class="form-label small mb-1">Sampai tanggal</label>
                <input type="date" name="to" id="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
            </div>
            <div class="col-12 col-md-3">
                <label for="user_id" class="form-label small mb-1">Akun</label>
                <select name="user_id" id="user_id" class="form-select">
                    <option value="">Semua akun</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((int) ($filters['user_id'] ?? 0) === $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label for="action" class="form-label small mb-1">Aksi</label>
                <select name="action" id="action" class="form-select">
                    <option value="">Semua aksi</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button type="submit" class="btn btn-gray-800">Terapkan</button>
            </div>
        </div>
    </form>

    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="border-gray-200">Waktu</th>
                    <th class="border-gray-200">Akun</th>
                    <th class="border-gray-200">Aksi</th>
                    <th class="border-gray-200">Data</th>
                    <th class="border-gray-200">Alamat IP</th>
                    <th class="border-gray-200">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="small text-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $log->user?->name ?? '–' }}</td>
                        <td><code>{{ $log->action }}</code></td>
                        <td class="small">{{ $log->entity_type ? $log->entity_type.' #'.$log->entity_id : '–' }}</td>
                        <td class="small">{{ $log->ip_address ?? '–' }}</td>
                        <td class="small text-break">{{ $log->detail ? json_encode($log->detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '–' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-gray-600 py-4">Belum ada catatan audit.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">{{ $logs->links() }}</div>
    </div>
@endsection
