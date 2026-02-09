<div style="overflow:auto;">
  <table style="width:100%; border-collapse:collapse;">
    <thead>
      <tr style="text-align:left; border-bottom:1px solid #1f335c;">
        <th style="padding:10px;">ID</th>
        <th style="padding:10px;">Username</th>
        <th style="padding:10px;">Email</th>
        <th style="padding:10px;">Phone</th>
        <th style="padding:10px;">Country</th>
        <th style="padding:10px;">VIP</th>
        <th style="padding:10px;">KYC</th>
        <th style="padding:10px;">Main</th>
        <th style="padding:10px;">Chips</th>
        <th style="padding:10px;">Bonus</th>
        <th style="padding:10px;">Last Login</th>
        <th style="padding:10px;">Flags</th>
        <th style="padding:10px; width:520px;">Actions</th>
      </tr>
    </thead>

    <tbody>
      @forelse($users as $u)
        @php
          $isBanned = !is_null($u->banned_at);
          $isLocked = !is_null($u->locked_until) && \Carbon\Carbon::parse($u->locked_until)->gt(now());
        @endphp

        <tr style="border-bottom:1px solid #162a52;">
          <td style="padding:10px;">{{ $u->id }}</td>

          <td style="padding:10px;">
            <a href="{{ route('admin.users.edit', $u->id) }}" style="color:#7fb0ff;">
              {{ $u->username }}
            </a>
          </td>

          <td style="padding:10px;">{{ $u->email ?? '-' }}</td>
          <td style="padding:10px;">{{ trim(($u->phone_country ?? '').' '.($u->phone ?? '')) ?: '-' }}</td>
          <td style="padding:10px;">{{ $u->country ?? '-' }}</td>
          <td style="padding:10px;">{{ $u->vip_name ?? '-' }}</td>
          <td style="padding:10px;">{{ is_null($u->kyc_status) ? '-' : (string)$u->kyc_status }}</td>

          <td style="padding:10px;">{{ number_format((float)$u->main_balance, 2) }}</td>
          <td style="padding:10px;">{{ number_format((float)$u->chips_balance, 2) }}</td>
          <td style="padding:10px;">{{ number_format((float)$u->bonus_balance, 2) }}</td>

          <td style="padding:10px;">{{ $u->updated_at ?? '-' }}</td>

          <td style="padding:10px;">
            {!! $u->is_active ? '<span style="color:#6dff8f;">Active</span>' : '<span style="color:#ff8a8a;">Inactive</span>' !!}
            @if($isBanned) <div style="color:#ff8a8a; font-size:12px;">BANNED</div> @endif
            @if($isLocked) <div style="color:#ffd36d; font-size:12px;">LOCKED</div> @endif
          </td>

          <td style="padding:10px; white-space:nowrap;">
            <button class="btn" style="padding:8px 10px;"
              type="button" data-view-user="1" data-user-id="{{ $u->id }}">
              View
            </button>

            <a class="btn" style="padding:8px 10px; display:inline-block; margin-left:8px;"
              href="{{ route('admin.users.edit', $u->id) }}">
              Edit
            </a>

            <button class="btn" style="padding:8px 10px; margin-left:8px;"
              type="button" data-toggle-active="1" data-url="{{ route('admin.users.toggleActive', $u->id) }}">
              {{ $u->is_active ? 'Disable' : 'Enable' }}
            </button>

            @if(!$isBanned)
              <button class="btn" style="padding:8px 10px; margin-left:8px;"
                type="button" data-ban="1" data-url="{{ route('admin.users.ban', $u->id) }}">
                Ban
              </button>
            @else
              <button class="btn" style="padding:8px 10px; margin-left:8px;"
                type="button" data-unban="1" data-url="{{ route('admin.users.unban', $u->id) }}">
                Unban
              </button>
            @endif

            @if(!$isLocked)
              <button class="btn" style="padding:8px 10px; margin-left:8px;"
                type="button" data-lock="1" data-url="{{ route('admin.users.lock', $u->id) }}">
                Lock
              </button>
            @else
              <button class="btn" style="padding:8px 10px; margin-left:8px;"
                type="button" data-unlock="1" data-url="{{ route('admin.users.unlock', $u->id) }}">
                Unlock
              </button>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="13" style="padding:14px; opacity:.8;">No users found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
