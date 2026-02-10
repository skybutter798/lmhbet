{{-- /home/lmh/app/resources/views/admins/support/index.blade.php --}}
@extends('admins.layout')

@section('title', 'Support Tickets')

@section('body')
<style>
  :root{
    --bg: #0b1220;
    --panel: rgba(255,255,255,.04);
    --panel2: rgba(255,255,255,.06);
    --stroke: rgba(148,163,184,.18);
    --stroke2: rgba(148,163,184,.28);
    --text: rgba(255,255,255,.92);
    --muted: rgba(255,255,255,.65);
    --muted2: rgba(255,255,255,.5);
    --shadow: 0 16px 50px rgba(0,0,0,.45);
    --r: 16px;
  }

  .pageWrap{ display:flex; gap:16px; }
  .content{ flex:1; }

  .pageHead{
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap:12px;
    margin-bottom:14px;
  }
  .pageHead h1{
    margin:0;
    font-size:22px;
    font-weight:900;
    letter-spacing:.2px;
    color:var(--text);
  }
  .pageMeta{ color:var(--muted); font-weight:700; }
  .pageMeta strong{ color:var(--text); }

  .cardX{
    background: linear-gradient(180deg, rgba(255,255,255,.055), rgba(255,255,255,.03));
    border:1px solid var(--stroke);
    border-radius:var(--r);
    box-shadow: var(--shadow);
    overflow:hidden;
  }

  .filters{
    padding:14px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:flex-end;
  }
  .field{
    display:flex;
    flex-direction:column;
    gap:6px;
  }
  .labelX{
    font-size:12px;
    font-weight:800;
    color:var(--muted);
    letter-spacing:.2px;
  }
  .inputX, .selectX{
    height:40px;
    border-radius:12px;
    padding:0 12px;
    outline:none;
    border:1px solid var(--stroke);
    background: rgba(0,0,0,.25);
    color: var(--text);
    min-width: 160px;
  }
  .inputX::placeholder{ color: rgba(255,255,255,.35); }
  .inputX:focus, .selectX:focus{
    border-color: rgba(79,140,255,.65);
    box-shadow: 0 0 0 4px rgba(79,140,255,.15);
  }
  .btnX{
    height:40px;
    border-radius:12px;
    padding:0 14px;
    border:1px solid var(--stroke2);
    background: rgba(255,255,255,.06);
    color: var(--text);
    font-weight:900;
    letter-spacing:.2px;
    cursor:pointer;
    transition: transform .12s ease, background .12s ease, border-color .12s ease;
    user-select:none;
  }
  .btnX:hover{ background: rgba(255,255,255,.09); transform: translateY(-1px); }
  .btnX:active{ transform: translateY(0px); }
  .btnPri{
    border-color: rgba(79,140,255,.55);
    background: rgba(79,140,255,.18);
  }
  .btnPri:hover{ background: rgba(79,140,255,.24); }

  .tableWrap{
    overflow:auto;
    -webkit-overflow-scrolling:touch;
  }

  table.tickets{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
    min-width: 980px;
  }
  table.tickets thead th{
    position:sticky;
    top:0;
    z-index:2;
    background: rgba(13, 20, 35, .92);
    backdrop-filter: blur(10px);
    border-bottom:1px solid var(--stroke);
    color: var(--muted);
    font-size:12px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.7px;
    padding:12px 12px;
    white-space:nowrap;
  }
  table.tickets tbody td{
    padding:12px 12px;
    border-bottom:1px solid rgba(148,163,184,.12);
    color: var(--text);
    vertical-align:middle;
    white-space:nowrap;
  }

  table.tickets tbody tr{
    background: transparent;
    transition: background .12s ease;
  }
  table.tickets tbody tr:hover{
    background: rgba(255,255,255,.045);
  }

  .clip{
    display:inline-block;
    max-width:360px;
    overflow:hidden;
    text-overflow:ellipsis;
    vertical-align:bottom;
  }

  .idPill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:6px 10px;
    border-radius:999px;
    font-weight:900;
    font-size:12px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(148,163,184,.22);
    color: var(--text);
  }

  .userLine{
    display:flex;
    align-items:center;
    gap:10px;
    min-width: 260px;
  }
  .avatar{
    width:30px;
    height:30px;
    border-radius:10px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-weight:900;
    color:#fff;
    background: rgba(79,140,255,.22);
    border:1px solid rgba(79,140,255,.35);
    flex: 0 0 auto;
  }
  .userMeta{
    display:flex;
    flex-direction:column;
    line-height:1.1;
  }
  .userName{ font-weight:900; }
  .userSub{ font-size:12px; color: var(--muted2); }

  .badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:900;
    line-height:1;
    border:1px solid transparent;
  }
  .dot{
    width:8px;
    height:8px;
    border-radius:99px;
    display:inline-block;
  }
  .badge-open{
    background: rgba(0,200,120,.12);
    border-color: rgba(0,200,120,.28);
    color: #35e6a6;
  }
  .badge-open .dot{ background:#35e6a6; box-shadow:0 0 0 4px rgba(53,230,166,.15); }
  .badge-closed{
    background: rgba(255,90,90,.10);
    border-color: rgba(255,90,90,.28);
    color: #ff7b7b;
  }
  .badge-closed .dot{ background:#ff7b7b; box-shadow:0 0 0 4px rgba(255,123,123,.12); }

  .time{
    color: var(--muted);
    font-weight:800;
    font-size:12px;
  }

  .rowActions{
    display:flex;
    justify-content:flex-end;
    gap:8px;
  }
  .aOpen{
    display:inline-flex;
    align-items:center;
    gap:8px;
    height:36px;
    padding:0 12px;
    border-radius:12px;
    border:1px solid rgba(79,140,255,.5);
    background: rgba(79,140,255,.16);
    color: var(--text);
    font-weight:900;
    text-decoration:none;
    transition: transform .12s ease, background .12s ease;
  }
  .aOpen:hover{ background: rgba(79,140,255,.22); transform: translateY(-1px); }

  .empty{
    padding:18px;
    color: var(--muted);
    font-weight:800;
  }

  .pagWrap{
    padding:12px 14px;
    border-top:1px solid var(--stroke);
  }
</style>

<div class="app">
  @include('admins.partials.sidebar')

  <div class="content">
    <div class="pageHead">
      <h1>Support Tickets</h1>
      <div class="pageMeta">Total: <strong>{{ $tickets->total() }}</strong></div>
    </div>

    @if(session('success'))
      <div class="cardX" style="margin-bottom:14px;">
        <div style="padding:12px 14px; color:#35e6a6; font-weight:900;">
          {{ session('success') }}
        </div>
      </div>
    @endif

    <div class="cardX" style="margin-bottom:14px;">
      <form method="get" action="{{ route('admin.support.index') }}" class="filters">
        <div class="field" style="flex:1; min-width:260px;">
          <div class="labelX">Search</div>
          <input class="inputX" name="q" value="{{ $q ?? '' }}" placeholder="username / email / phone / subject" />
        </div>

        <div class="field" style="width:180px;">
          <div class="labelX">Status</div>
          <select class="selectX" name="status">
            <option value="open" {{ ($status ?? 'open')==='open' ? 'selected' : '' }}>Open</option>
            <option value="closed" {{ ($status ?? '')==='closed' ? 'selected' : '' }}>Closed</option>
            <option value="all" {{ ($status ?? '')==='all' ? 'selected' : '' }}>All</option>
          </select>
        </div>

        <button class="btnX btnPri" type="submit">Filter</button>
      </form>
    </div>

    <div class="cardX">
      <div class="tableWrap" id="tableWrap">
        <table class="tickets">
          <thead>
            <tr>
              <th style="width:110px;">Ticket</th>
              <th style="width:330px;">User</th>
              <th>Subject</th>
              <th style="width:140px;">Status</th>
              <th style="width:190px;">Last</th>
              <th style="width:140px;"></th>
            </tr>
          </thead>
          <tbody>
            @forelse($tickets as $t)
              @php
                $uname = $t->user?->username ?? 'User';
                $letter = strtoupper(mb_substr($uname, 0, 1));
              @endphp
              <tr>
                <td>
                  <span class="idPill">#{{ $t->id }}</span>
                </td>

                <td>
                  <div class="userLine">
                    <span class="avatar">{{ $letter }}</span>
                    <div class="userMeta">
                      <div class="userName">{{ $uname }}</div>
                      <div class="userSub">
                        ID: {{ $t->user_id }}
                        @if($t->user?->email) • {{ $t->user->email }} @endif
                      </div>
                    </div>
                  </div>
                </td>

                <td>
                  <span class="clip">{{ $t->subject }}</span>
                </td>

                <td>
                  @if($t->status === 'open')
                    <span class="badge badge-open"><span class="dot"></span>Open</span>
                  @else
                    <span class="badge badge-closed"><span class="dot"></span>Closed</span>
                  @endif
                </td>

                <td class="time">
                  {{ optional($t->last_message_at)->format('Y-m-d H:i') }}
                </td>

                <td>
                  <div class="rowActions">
                    <a class="aOpen" href="{{ route('admin.support.show', $t) }}">Open →</a>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="empty">No tickets found.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="pagWrap">
        {!! $tickets->links('vendor.pagination.admin') !!}
      </div>
    </div>
  </div>
</div>
@endsection