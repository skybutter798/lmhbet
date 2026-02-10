{{-- /home/lmh/app/resources/views/support/partials/new_ticket_modal.blade.php --}}
<div class="modal" id="newTicketModal" hidden>
  <div class="modal__backdrop" data-close-new-ticket></div>
  <div class="modal__panel" role="dialog" aria-label="New Ticket">
    <div class="modal__head">
      <div class="modal__title">New Ticket</div>
      <button class="modal__close" type="button" data-close-new-ticket>✕</button>
    </div>

    <form method="post" action="{{ route('support.store') }}" class="wdForm" style="padding:14px;">
      @csrf

      <label class="wdLabel">Subject <span class="req">*</span></label>
      <input class="wdAmount" type="text" name="subject" value="{{ old('subject') }}" required />

      <label class="wdLabel">Message <span class="req">*</span></label>
      <textarea class="wdAmount" name="message" rows="6" required>{{ old('message') }}</textarea>

      <div style="display:flex;gap:10px;margin-top:12px;">
        <button class="wdSubmit" type="submit" style="flex:1;">Send</button>
        <button class="wdSubmit" type="button" data-close-new-ticket style="flex:1;background:#333;">Cancel</button>
      </div>
    </form>
  </div>
</div>

@if ($errors->any() && ($errors->has('subject') || $errors->has('message')))
  <script>
    (function(){ window.__OPEN_NEW_TICKET__ = true; })();
  </script>
@endif