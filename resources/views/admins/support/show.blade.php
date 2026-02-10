{{-- /home/lmh/app/resources/views/admins/support/show.blade.php --}}
@extends('admins.layout')

@section('title', 'Support Ticket')

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

  .pageHead{
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    gap:12px;
    margin-bottom:14px;
  }
  .pageHead h1{
    margin:0;
    font-size:22px;
    font-weight:900;
    color:var(--text);
    letter-spacing:.2px;
  }
  .headActions{
    display:flex;
    gap:10px;
    align-items:center;
  }

  .cardX{
    background: linear-gradient(180deg, rgba(255,255,255,.055), rgba(255,255,255,.03));
    border:1px solid var(--stroke);
    border-radius:var(--r);
    box-shadow: var(--shadow);
    overflow:hidden;
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
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
  }
  .btnX:hover{ background: rgba(255,255,255,.09); transform: translateY(-1px); }
  .btnX:active{ transform: translateY(0px); }
  .btnDanger{
    border-color: rgba(255,90,90,.45);
    background: rgba(255,90,90,.12);
  }
  .btnDanger:hover{ background: rgba(255,90,90,.18); }
  .btnPri{
    border-color: rgba(79,140,255,.55);
    background: rgba(79,140,255,.18);
  }
  .btnPri:hover{ background: rgba(79,140,255,.24); }

  .metaGrid{
    padding:14px;
    display:grid;
    grid-template-columns: repeat(12, 1fr);
    gap:10px;
  }
  .metaItem{
    grid-column: span 6;
    background: rgba(0,0,0,.18);
    border:1px solid rgba(148,163,184,.14);
    border-radius:14px;
    padding:12px;
  }
  @media (max-width: 980px){
    .metaItem{ grid-column: span 12; }
  }
  .metaLabel{
    font-size:12px;
    font-weight:900;
    color: var(--muted);
    text-transform:uppercase;
    letter-spacing:.7px;
    margin-bottom:6px;
  }
  .metaVal{
    color: var(--text);
    font-weight:900;
    line-height:1.2;
  }
  .metaSub{
    margin-top:6px;
    color: var(--muted2);
    font-size:12px;
    font-weight:800;
  }

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
  .dot{ width:8px; height:8px; border-radius:99px; display:inline-block; }
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

  /* Chat */
  .chatShell{
    padding:14px;
  }
  .chatBox{
    height: min(62vh, 620px);
    overflow:auto;
    border-radius: 16px;
    border:1px solid rgba(148,163,184,.14);
    background: radial-gradient(1200px 600px at 20% -10%, rgba(79,140,255,.12), transparent 60%),
                radial-gradient(1200px 600px at 100% 0%, rgba(0,200,120,.08), transparent 55%),
                rgba(0,0,0,.18);
    padding: 14px;
    display:flex;
    flex-direction:column;
    gap:10px;
  }

  .msgRow{
    display:flex;
    width:100%;
  }
  .msgRow.admin{ justify-content:flex-end; }
  .msgRow.user{ justify-content:flex-start; }

  .msg{
    max-width: min(840px, 78%);
    border-radius: 18px;
    padding: 10px 12px;
    border: 1px solid rgba(148,163,184,.14);
    box-shadow: 0 10px 26px rgba(0,0,0,.22);
    backdrop-filter: blur(10px);
  }
  .msg.admin{
    background: linear-gradient(180deg, rgba(79,140,255,.22), rgba(79,140,255,.10));
    border-color: rgba(79,140,255,.30);
  }
  .msg.user{
    background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03));
  }

  .msgTop{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:8px;
  }
  .who{
    display:flex;
    align-items:center;
    gap:8px;
    font-weight:900;
    color: var(--text);
  }
  .who .pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 12px;
    border:1px solid rgba(148,163,184,.18);
    background: rgba(255,255,255,.05);
    color: var(--text);
  }
  .who .pill.admin{
    border-color: rgba(79,140,255,.32);
    background: rgba(79,140,255,.16);
  }
  .when{
    font-size:12px;
    font-weight:800;
    color: var(--muted);
    white-space:nowrap;
  }
  .body{
    white-space:pre-wrap;
    word-break:break-word;
    color: var(--text);
    font-weight:800;
    line-height:1.35;
  }

  .replyBox{
    padding:14px;
    border-top: 1px solid rgba(148,163,184,.12);
    background: rgba(0,0,0,.12);
  }
  .replyGrid{
    display:grid;
    grid-template-columns: 1fr auto;
    gap:10px;
    align-items:end;
  }
  .ta{
    width:100%;
    min-height: 90px;
    resize: vertical;
    border-radius: 14px;
    padding: 12px;
    outline:none;
    border:1px solid rgba(148,163,184,.18);
    background: rgba(0,0,0,.25);
    color: var(--text);
    font-weight:800;
  }
  .ta:focus{
    border-color: rgba(79,140,255,.65);
    box-shadow: 0 0 0 4px rgba(79,140,255,.15);
  }

  .err{
    margin-top:8px;
    color:#ff7b7b;
    font-weight:900;
  }
</style>

<div class="app">
  @include('admins.partials.sidebar')

  <div class="content">
    <div class="pageHead">
      <h1>Ticket #{{ $ticket->id }}</h1>
      <div class="headActions">
        <a class="btnX" href="{{ route('admin.support.index') }}">← Back</a>

        @if($ticket->status === 'open')
          <form method="post" action="{{ route('admin.support.close', $ticket) }}">
            @csrf
            <button class="btnX btnDanger" type="submit">Close</button>
          </form>
        @else
          <form method="post" action="{{ route('admin.support.reopen', $ticket) }}">
            @csrf
            <button class="btnX btnPri" type="submit">Reopen</button>
          </form>
        @endif
      </div>
    </div>

    @if(session('success'))
      <div class="cardX" style="margin-bottom:14px;">
        <div style="padding:12px 14px; color:#35e6a6; font-weight:900;">
          {{ session('success') }}
        </div>
      </div>
    @endif

    <div class="cardX" style="margin-bottom:14px;">
      <div class="metaGrid">
        <div class="metaItem">
          <div class="metaLabel">User</div>
          <div class="metaVal">{{ $ticket->user?->username ?? 'User' }}</div>
          <div class="metaSub">User ID: {{ $ticket->user_id }}</div>
        </div>

        <div class="metaItem">
          <div class="metaLabel">Subject</div>
          <div class="metaVal">{{ $ticket->subject }}</div>
        </div>

        <div class="metaItem">
          <div class="metaLabel">Status</div>
          <div class="metaVal">
            @if($ticket->status === 'open')
              <span class="badge badge-open"><span class="dot"></span>Open</span>
            @else
              <span class="badge badge-closed"><span class="dot"></span>Closed</span>
            @endif
          </div>
        </div>

        <div class="metaItem">
          <div class="metaLabel">Last Message</div>
          <div class="metaVal">{{ optional($ticket->last_message_at)->format('Y-m-d H:i') }}</div>
        </div>
      </div>
    </div>

    <div class="cardX" style="margin-bottom:14px;">
      <div class="chatShell">
        <div class="chatBox" id="chatBox">
          @foreach($ticket->messages as $m)
            @php $isAdmin = $m->sender_role === 'admin'; @endphp
            <div class="msgRow {{ $isAdmin ? 'admin' : 'user' }}">
              <div class="msg {{ $isAdmin ? 'admin' : 'user' }}">
                <div class="msgTop">
                  <div class="who">
                    <span class="pill {{ $isAdmin ? 'admin' : '' }}">{{ $isAdmin ? 'Admin' : 'User' }}</span>
                  </div>
                  <div class="when">{{ optional($m->created_at)->format('Y-m-d H:i') }}</div>
                </div>
                <div class="body">{{ $m->body }}</div>
              </div>
            </div>
          @endforeach
        </div>
      </div>

      @if($ticket->status === 'open')
        <div class="replyBox">
          <form method="post" action="{{ route('admin.support.reply', $ticket) }}">
            @csrf
            <div class="replyGrid">
              <div>
                <textarea class="ta" name="message" placeholder="Type your reply..." required>{{ old('message') }}</textarea>
                @if($errors->has('message'))
                  <div class="err">{{ $errors->first('message') }}</div>
                @endif
              </div>
              <button class="btnX btnPri" type="submit" style="height:44px; border-radius:14px;">Send</button>
            </div>
          </form>
        </div>
      @endif
    </div>

  </div>
</div>

<script>
(function () {
  const box = document.getElementById('chatBox');
  if (box) box.scrollTop = box.scrollHeight;
})();
</script>
@endsection