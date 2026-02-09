{{-- /home/lmh/app/resources/views/games/play.blade.php --}}
@extends('layouts.app')

@section('body')
  @include('partials.header')

  <main style="min-height: calc(100vh - 80px);">
    <div class="wrap" style="padding: 16px 0;">
      <h1 style="margin:0 0 8px;">{{ $game->name }}</h1>
      <div id="gameMsg" style="opacity:.8; margin-bottom:12px;">Preparing…</div>

      <div style="position:relative; width:100%; height:75vh; border-radius:12px; overflow:hidden; background:#0b0f1a;">
        <iframe
          id="gameFrame"
          title="{{ $game->name }}"
          style="width:100%; height:100%; border:0; display:block;"
          referrerpolicy="no-referrer-when-downgrade"
          allow="fullscreen; autoplay; clipboard-read; clipboard-write"
        ></iframe>

        @php
          $forceNewTabIds = [241, 1278, 1277];
        @endphp

        @if(in_array((int)$game->id, $forceNewTabIds, true))
          <div
            id="openInTabOverlay"
            style="
              position:absolute;
              inset:0;
              display:none;
              align-items:center;
              justify-content:center;
              z-index:5;
              background: rgba(0,0,0,.55);
              backdrop-filter: blur(2px);
              padding: 16px;
              text-align:center;
            "
          >
            <div style="max-width: 420px; width:100%;">
              <div style="margin-bottom:12px; font-size:14px; opacity:.9;">
                This game should be opened in a new tab.
              </div>

              <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
                <button
                  id="openInTabBtn"
                  type="button"
                  class="btn btn--primary"
                  style="border-radius:999px; padding:12px 16px; font-size:14px;"
                >
                  Open in new tab
                </button>

                <button
                  id="dismissOverlayBtn"
                  type="button"
                  class="btn btn--ghost"
                  style="border-radius:999px; padding:12px 16px; font-size:14px;"
                >
                  Continue here
                </button>
              </div>
            </div>
          </div>
        @endif
      </div>

      <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
        <a id="openNewTab" class="btn btn--ghost" target="_blank" rel="noopener">Open in new tab</a>
        <button id="goFull" class="btn btn--primary" type="button">Fullscreen</button>
      </div>

      <div style="margin-top:10px; opacity:.7; font-size:13px;">
        Game wallet (CHIPS):
        <b>
          <span id="chipsCurrency">{{ $currency }}</span>
          <span id="chipsBalance">{{ number_format((float)$chips, 2, '.', ',') }}</span>
        </b>
      </div>
    </div>
  </main>

  <script>
  document.addEventListener("DOMContentLoaded", () => {
    const GAME_ID = {{ (int)$game->id }};
    const FORCE_NEW_TAB_IDS = [241, 1278, 1277];
    const needsNewTab = FORCE_NEW_TAB_IDS.includes(GAME_ID);

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
    const msg = document.getElementById("gameMsg");
    const frame = document.getElementById("gameFrame");
    const openNewTab = document.getElementById("openNewTab");
    const goFull = document.getElementById("goFull");

    const chipsCurrencyEl = document.getElementById("chipsCurrency");
    const chipsBalanceEl = document.getElementById("chipsBalance");

    const overlay = document.getElementById("openInTabOverlay");
    const overlayBtn = document.getElementById("openInTabBtn");
    const dismissBtn = document.getElementById("dismissOverlayBtn");

    const setMsg = (t) => { if (msg) msg.textContent = t || ""; };
    const showOverlay = () => { if (overlay) overlay.style.display = "flex"; };
    const hideOverlay = () => { if (overlay) overlay.style.display = "none"; };

    const moneyFmt = new Intl.NumberFormat("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });

    // ---- back url (store previous /games?... url)
    const BACK_KEY = "lmh.back.games";
    const params = new URLSearchParams(window.location.search);

    let backCandidate = null;

    // prefer ?back=... (optional)
    const backParam = params.get("back");
    if (backParam) {
      try {
        const u = new URL(backParam, window.location.origin);
        if (u.origin === window.location.origin && u.pathname.startsWith("/games")) {
          backCandidate = u.href;
        }
      } catch (e) {}
    }

    // fallback to referrer
    if (!backCandidate) {
      try {
        const r = new URL(document.referrer);
        if (r.origin === window.location.origin && r.pathname.startsWith("/games")) {
          backCandidate = r.href;
        }
      } catch (e) {}
    }

    if (backCandidate) sessionStorage.setItem(BACK_KEY, backCandidate);

    const backUrl = sessionStorage.getItem(BACK_KEY) || (window.location.origin + "/games");

    // ---- detect iframe returning to our /games and redirect parent to backUrl
    const OUR_ORIGIN = window.location.origin;

    const tryGetIframeHref = () => {
      try {
        return frame?.contentWindow?.location?.href || null; // throws if cross-origin
      } catch (e) {
        return null;
      }
    };

    const iframeIsOurGames = (href) => {
      if (!href || href === "about:blank") return false;
      try {
        const u = new URL(href);
        return u.origin === OUR_ORIGIN && u.pathname.startsWith("/games");
      } catch (e) {
        return false;
      }
    };

    const redirectParentIfIframeReturned = () => {
      const href = tryGetIframeHref();
      if (href && iframeIsOurGames(href)) {
        window.location.replace(backUrl);
      }
    };

    // ---- chips polling
    let pollTimer = null;
    let lastChips = null;

    const fetchChipsBalance = async () => {
      try {
        const res = await fetch("/wallet/chips/balance", {
          method: "GET",
          headers: {
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest",
          },
          credentials: "same-origin",
          cache: "no-store",
        });

        const data = await res.json().catch(() => null);
        if (!res.ok || !data?.ok) return;

        if (chipsCurrencyEl && data.currency) chipsCurrencyEl.textContent = data.currency;

        const chips = Number(data.chips);
        if (!Number.isFinite(chips)) return;

        if (lastChips === null || Math.abs(lastChips - chips) > 0.000001) {
          lastChips = chips;
          if (chipsBalanceEl) chipsBalanceEl.textContent = moneyFmt.format(chips);
        }
      } catch (e) {}
    };

    const startPolling = () => {
      if (pollTimer) return;
      fetchChipsBalance();
      pollTimer = setInterval(fetchChipsBalance, 2500);
    };

    const stopPolling = () => {
      if (!pollTimer) return;
      clearInterval(pollTimer);
      pollTimer = null;
    };

    document.addEventListener("visibilitychange", () => {
      if (document.hidden) stopPolling();
      else startPolling();
    });

    window.addEventListener("focus", fetchChipsBalance);

    // ---- launch game
    let fallbackTimer = null;

    if (frame) {
      frame.addEventListener("load", () => {
        if (fallbackTimer) {
          clearTimeout(fallbackTimer);
          fallbackTimer = null;
        }
        redirectParentIfIframeReturned();
        setMsg("");
        if (!needsNewTab) hideOverlay();
      });
    }

    const launch = async () => {
      setMsg("Launching game…");

      try {
        const res = await fetch("/games/launch", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrf,
            "X-Requested-With": "XMLHttpRequest",
          },
          credentials: "same-origin",
          body: JSON.stringify({ game_id: GAME_ID }),
        });

        const data = await res.json().catch(() => null);
        const url = data?.url || data?.data?.url;
        const ok = data?.ok === true;

        if (!res.ok || !ok || !url) {
          const m = data?.message || data?.msg || "Launch failed";
          setMsg(m);
          console.error("Launch error:", data);
          return;
        }

        if (openNewTab) openNewTab.href = url;

        if (needsNewTab) {
          showOverlay();
          setMsg("Open in new tab to play.");
          if (overlayBtn) overlayBtn.onclick = () => window.open(url, "_blank", "noopener");
          if (dismissBtn) dismissBtn.onclick = () => hideOverlay();
        } else {
          hideOverlay();
        }

        setMsg("Loading game…");
        frame.src = url;

        if (!needsNewTab) {
          fallbackTimer = setTimeout(() => {
            setMsg("Provider blocks iframe. Redirecting…");
            window.location.href = url;
          }, 2500);
        }

        goFull?.addEventListener("click", () => {
          if (frame.requestFullscreen) frame.requestFullscreen().catch(() => {});
        });

      } catch (e) {
        console.error(e);
        setMsg("Network error.");
      }
    };

    startPolling();
    launch();
  });
  </script>

@endsection
