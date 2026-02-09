@extends('admins.layout')

@section('title','Audit Logs')

@section('body')
<div class="app">
  @include('admins.partials.sidebar')

  <div class="content">
    <div class="topbar">
      <div style="font-size:22px; font-weight:800;">Audit Logs</div>
      <div style="opacity:.85;">Latest actions</div>
    </div>

    <div class="card">
      <div style="overflow:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr style="text-align:left; border-bottom:1px solid #1f335c;">
              <th style="padding:10px;">ID</th>
              <th style="padding:10px;">Admin</th>
              <th style="padding:10px;">Action</th>
              <th style="padding:10px;">Target User</th>
              <th style="padding:10px;">Target</th>
              <th style="padding:10px;">IP</th>
              <th style="padding:10px;">At</th>
            </tr>
          </thead>
          <tbody>
            @forelse($logs as $l)
              <tr style="border-bottom:1px solid #162a52;">
                <td style="padding:10px;">{{ $l->id }}</td>
                <td style="padding:10px;">{{ $l->admin?->username ?? $l->admin_id }}</td>
                <td style="padding:10px;">{{ $l->action }}</td>
                <td style="padding:10px;">{{ $l->target_user_id ?? '-' }}</td>
                <td style="padding:10px;">{{ ($l->target_type ?? '-') }} {{ $l->target_id ? ('#'.$l->target_id) : '' }}</td>
                <td style="padding:10px;">{{ $l->ip ?? '-' }}</td>
                <td style="padding:10px;">{{ $l->created_at }}</td>
              </tr>
            @empty
              <tr><td colspan="7" style="padding:14px; opacity:.8;">No logs.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div style="margin-top:12px;">{{ $logs->links() }}</div>
    </div>
  </div>
</div>
@endsection
