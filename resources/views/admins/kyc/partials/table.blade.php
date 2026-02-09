<div style="overflow:auto;">
  <table style="width:100%; border-collapse:collapse;">
    <thead>
      <tr style="text-align:left; border-bottom:1px solid #1f335c;">
        <th style="padding:10px;">ID</th>
        <th style="padding:10px;">User</th>
        <th style="padding:10px;">Bank</th>
        <th style="padding:10px;">Acc Name</th>
        <th style="padding:10px;">Acc No</th>
        <th style="padding:10px;">Status</th>
        <th style="padding:10px;">Submitted</th>
        <th style="padding:10px;">Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($subs as $s)
        <tr style="border-bottom:1px solid #162a52;">
          <td style="padding:10px;">{{ $s->id }}</td>
          <td style="padding:10px;">{{ $s->user?->username ?? $s->user_id }}</td>
          <td style="padding:10px;">{{ $s->bank_name ?? '-' }}</td>
          <td style="padding:10px;">{{ $s->account_holder_name ?? '-' }}</td>
          <td style="padding:10px;">{{ method_exists($s, 'maskedAccountNumber') ? $s->maskedAccountNumber() : ($s->account_number ?? '-') }}</td>
          <td style="padding:10px;">{{ $s->status }}</td>
          <td style="padding:10px;">{{ $s->submitted_at ?? $s->created_at }}</td>
          <td style="padding:10px;">
            @if($s->status === 'pending')
              <button class="btn" type="button" data-approve="1" data-url="{{ route('admin.kyc.approve', $s->id) }}">Approve</button>
              <button class="btn" type="button" data-reject="1" data-url="{{ route('admin.kyc.reject', $s->id) }}" style="margin-left:8px;">Reject</button>
            @else
              <span style="opacity:.8;">-</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="8" style="padding:14px; opacity:.8;">No submissions.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
