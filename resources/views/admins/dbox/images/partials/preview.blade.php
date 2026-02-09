{{-- /home/lmh/app/resources/views/admins/dbox/images/partials/preview.blade.php --}}

<div>
  <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
    <div style="font-weight:800;">
      Existing Images
      <span style="opacity:.75; font-size:12px; font-weight:600;">
        ({{ $images->count() }} found)
      </span>
    </div>
  </div>

  <div style="opacity:.8; font-size:12px; margin-top:6px;">
    {{ $type === 'game' ? 'Game' : 'Provider' }}:
    <span class="mono">{{ $target->code ?? '' }}</span> — {{ $target->name ?? '' }}
  </div>

  @if($images->isEmpty())
    <div style="margin-top:10px; opacity:.8;">No images uploaded yet.</div>
  @else
    <div style="margin-top:12px; display:grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap:12px;">
      @foreach($images as $img)
        <div class="card" style="padding:10px;">
          <div style="aspect-ratio: 16/10; border-radius:12px; overflow:hidden; border:1px solid rgba(255,255,255,.10); background:rgba(0,0,0,.25);">
            <img src="{{ asset($img->path) }}" alt="img" style="width:100%; height:100%; object-fit:cover;">
          </div>

          <div style="margin-top:8px; font-size:12px; opacity:.9;">
            <div class="mono" title="{{ $img->path }}" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
              #{{ $img->id }} • sort {{ $img->sort_order ?? 0 }}
            </div>
            <div style="margin-top:2px; opacity:.75;">
              label: {{ $img->label ?: '-' }}
            </div>
            <div style="margin-top:6px;">
              @if($img->is_primary)
                <span style="display:inline-flex; gap:6px; align-items:center; padding:3px 8px; border-radius:999px; border:1px solid rgba(52,211,153,.35); background:rgba(52,211,153,.12); font-size:12px;">
                  <span style="width:8px;height:8px;border-radius:999px;background:rgba(52,211,153,.9);"></span>
                  Primary
                </span>
              @else
                <span style="display:inline-flex; gap:6px; align-items:center; padding:3px 8px; border-radius:999px; border:1px solid rgba(255,255,255,.12); background:rgba(255,255,255,.06); font-size:12px;">
                  <span style="width:8px;height:8px;border-radius:999px;background:rgba(255,255,255,.45);"></span>
                  Secondary
                </span>
              @endif
            </div>
          </div>

          <div style="display:flex; gap:8px; margin-top:10px; flex-wrap:wrap;">
            @if(!$img->is_primary)
              <button
                class="btn"
                type="button"
                data-img-action="primary"
                data-img-type="{{ $type }}"
                data-target-id="{{ $target->id }}"
                data-img-id="{{ $img->id }}"
              >Set Primary</button>
            @endif

            <button
              class="btn btn-danger"
              type="button"
              data-img-action="delete"
              data-img-type="{{ $type }}"
              data-target-id="{{ $target->id }}"
              data-img-id="{{ $img->id }}"
            >Delete</button>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>
