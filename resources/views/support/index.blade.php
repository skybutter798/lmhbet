{{-- /home/lmh/app/resources/views/support/index.blade.php --}}
@extends('layouts.app')

@section('body')
  @include('partials.header')

  <main class="accPage">
    <div class="wrap accGrid accDesktop">
      @include('partials.account_sidebar', ['active' => 'profile', 'activeSub' => 'message'])

      <section class="accMain">
        <div class="profileTop">
          <div class="profileTop__title">Message</div>
        </div>

        @if(session('success'))
          <div class="wdOk">{{ session('success') }}</div>
        @endif

        <div class="wdDesktopGrid">
          <div>
            <div class="wdCard wdCard--desk">
              <div class="wdHead">
                <div class="wdTitle">My Tickets</div>
                <button class="wdAddAcc" type="button" data-open-new-ticket>+ New Ticket</button>
              </div>

              @if($tickets->count())
                <div class="wdHistList">
                  @foreach($tickets as $t)
                    <a class="wdHistRow" href="{{ route('support.show', $t) }}" style="text-decoration:none;color:inherit;">
                      <div class="wdHistLeft">
                        <div class="wdHistAmt">{{ $t->subject }}</div>
                        <div class="wdHistMeta">{{ optional($t->last_message_at)->format('Y-m-d H:i') }}</div>
                      </div>
                      <div class="wdHistRight">
                        <span class="wdStatus wdStatus--{{ $t->status }}">{{ ucfirst($t->status) }}</span>
                      </div>
                    </a>
                  @endforeach
                </div>

                <div style="margin-top:12px;">
                  {{ $tickets->links() }}
                </div>
              @else
                <div class="wdEmpty">
                  <div class="wdEmptyIco">📄</div>
                  <div class="wdEmptyTitle">No Data</div>
                  <div class="wdEmptySub">No tickets yet.</div>
                </div>
              @endif
            </div>
          </div>

          <div>
            <div class="wdCard wdCard--desk">
              <div class="wdHead">
                <div class="wdTitle">Create Ticket</div>
              </div>

              <form method="post" action="{{ route('support.store') }}" class="wdForm">
                @csrf

                <label class="wdLabel">Subject <span class="req">*</span></label>
                <input class="wdAmount" type="text" name="subject" value="{{ old('subject') }}" required />

                @if($errors->has('subject'))
                  <div class="wdFieldErr">{{ $errors->first('subject') }}</div>
                @endif

                <label class="wdLabel">Message <span class="req">*</span></label>
                <textarea class="wdAmount" name="message" rows="6" required>{{ old('message') }}</textarea>

                @if($errors->has('message'))
                  <div class="wdFieldErr">{{ $errors->first('message') }}</div>
                @endif

                <button class="wdSubmit" type="submit">Send</button>
              </form>
            </div>
          </div>
        </div>

      </section>
    </div>

    @include('support.partials.new_ticket_modal')
  </main>
@endsection