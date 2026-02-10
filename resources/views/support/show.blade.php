{{-- /home/lmh/app/resources/views/support/show.blade.php --}}
@extends('layouts.app')

@section('body')
  @include('partials.header')

  @php
    $fmtDt = fn($d) => optional($d)->format('Y-m-d H:i');
  @endphp

  <main class="accPage">
    <div class="wrap accGrid accDesktop">
      @include('partials.account_sidebar', ['active' => 'profile', 'activeSub' => 'message'])

      <section class="accMain">
        <div class="profileTop" style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
          <div class="profileTop__title">Ticket: {{ $ticket->subject }}</div>
          <a class="btn btn--primary" href="{{ route('support.index') }}">Back</a>
        </div>

        @if(session('success'))
          <div class="wdOk">{{ session('success') }}</div>
        @endif

        <div class="wdDesktopGrid">
          <div style="grid-column:1 / -1;">
            <div class="wdCard wdCard--desk">
              <div class="wdHead">
                <div class="wdTitle">Conversation</div>
                <div>
                  <span class="wdStatus wdStatus--{{ $ticket->status }}">{{ ucfirst($ticket->status) }}</span>
                </div>
              </div>

              <div style="padding:14px;">
                @foreach($messages as $m)
                  @php
                    $isUser = $m->sender_role === 'user';
                  @endphp

                  <div style="margin-bottom:12px;display:flex;justify-content:{{ $isUser ? 'flex-end' : 'flex-start' }};">
                    <div style="max-width:70%;padding:10px 12px;border-radius:12px;background:{{ $isUser ? '#1f6feb' : '#2b2b2b' }};color:#fff;">
                      <div style="font-size:12px;opacity:.85;margin-bottom:6px;">
                        {{ $isUser ? 'You' : 'Support' }} • {{ $fmtDt($m->created_at) }}
                      </div>
                      <div style="white-space:pre-wrap;word-break:break-word;">{{ $m->body }}</div>
                    </div>
                  </div>
                @endforeach
              </div>

              <div style="padding:14px;border-top:1px solid rgba(255,255,255,.08);">
                @if($ticket->status === 'closed')
                  <div class="wdHint">This ticket is closed.</div>
                @else
                  <form method="post" action="{{ route('support.reply', $ticket) }}" class="wdForm">
                    @csrf
                    <label class="wdLabel">Reply <span class="req">*</span></label>
                    <textarea class="wdAmount" name="message" rows="4" required>{{ old('message') }}</textarea>
                    @if($errors->has('message'))
                      <div class="wdFieldErr">{{ $errors->first('message') }}</div>
                    @endif
                    <button class="wdSubmit" type="submit">Send Reply</button>
                  </form>
                @endif
              </div>

            </div>
          </div>
        </div>

      </section>
    </div>
  </main>
@endsection