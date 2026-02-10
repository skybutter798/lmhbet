// /home/lmh/app/public/js/app.js
document.addEventListener("DOMContentLoaded", () => {
  // Slider
  const slider = document.querySelector("[data-slider]");
  if (slider) {
    const slides = Array.from(slider.querySelectorAll(".hero__slide"));
    const prev = slider.querySelector("[data-prev]");
    const next = slider.querySelector("[data-next]");
    const dotsWrap = slider.querySelector("[data-dots]");
    const dots = dotsWrap ? Array.from(dotsWrap.querySelectorAll("[data-dot]")) : [];
    let idx = slides.findIndex((s) => s.classList.contains("is-active"));
    if (idx < 0) idx = 0;

    const setActive = (n) => {
      idx = (n + slides.length) % slides.length;
      slides.forEach((s, i) => s.classList.toggle("is-active", i === idx));
      dots.forEach((d, i) => d.classList.toggle("is-active", i === idx));
    };

    prev?.addEventListener("click", () => setActive(idx - 1));
    next?.addEventListener("click", () => setActive(idx + 1));
    dots.forEach((d) => {
      d.addEventListener("click", () => setActive(parseInt(d.dataset.dot, 10) || 0));
    });

    let timer = setInterval(() => setActive(idx + 1), 7000);
    slider.addEventListener("mouseenter", () => clearInterval(timer));
    slider.addEventListener("mouseleave", () => (timer = setInterval(() => setActive(idx + 1), 7000)));
  }

  // Tabs filter (provider grid)
  const tabs = document.querySelector("[data-tabs]");
  if (tabs) {
    const buttons = Array.from(tabs.querySelectorAll("[data-filter]"));
    const tiles = Array.from(document.querySelectorAll(".tile[data-cat]"));

    const apply = (filter) => {
      const f = (filter || "all").trim();

      buttons.forEach((b) => b.classList.toggle("is-active", (b.dataset.filter || "all") === f));

      tiles.forEach((t) => {
        const raw = (t.dataset.cat || "").trim();
        const cats = raw.split(/\s+/).filter(Boolean);

        const show = f === "all" || cats.includes(f);
        t.style.display = show ? "" : "none";
      });
    };

    buttons.forEach((b) => {
      b.addEventListener("click", () => apply(b.dataset.filter || "all"));
    });

    apply("all");
  }

  // Back to top
  document.querySelector("[data-to-top]")?.addEventListener("click", (e) => {
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: "smooth" });
  });

  // Header dropdowns (wallet + user)
  const userBtn = document.querySelector("[data-user-menu-btn]");
  const walletBtn = document.querySelector("[data-wallet-menu-btn]");
  const userMenu = document.getElementById("userMenu");
  const walletMenu = document.getElementById("walletMenu");

  const hideMenus = () => {
    if (userMenu) userMenu.hidden = true;
    if (walletMenu) walletMenu.hidden = true;
  };

  userBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    if (!userMenu) return;
    const willShow = userMenu.hidden;
    hideMenus();
    userMenu.hidden = !willShow;
  });

  walletBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    if (!walletMenu) return;
    const willShow = walletMenu.hidden;
    hideMenus();
    walletMenu.hidden = !willShow;
  });

  userMenu?.addEventListener("click", (e) => e.stopPropagation());
  walletMenu?.addEventListener("click", (e) => e.stopPropagation());

  document.addEventListener("click", hideMenus);
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") hideMenus();
  });

  // ----------------------------
  // MOBILE DRAWER (hamburger menu)
  // ----------------------------
  const drawer = document.getElementById("mDrawer");
  const openDrawerBtn = document.querySelector("[data-mdrawer-open]");

  const openDrawer = () => {
    if (!drawer) return;
    drawer.hidden = false;
    drawer.classList.add("is-open");
    drawer.setAttribute("aria-hidden", "false");
    document.body.classList.add("drawer-open");
  };

  const closeDrawer = () => {
    if (!drawer) return;
    drawer.classList.remove("is-open");
    drawer.setAttribute("aria-hidden", "true");
    document.body.classList.remove("drawer-open");

    window.setTimeout(() => {
      if (!drawer.classList.contains("is-open")) drawer.hidden = true;
    }, 250);
  };

  openDrawerBtn?.addEventListener("click", (e) => {
    e.preventDefault();
    openDrawer();
  });

  drawer?.querySelectorAll("[data-mdrawer-close]").forEach((el) => {
    el.addEventListener("click", (e) => {
      e.preventDefault();
      closeDrawer();
    });
  });

  drawer?.querySelector(".mDrawer__panel")?.addEventListener("click", (e) => {
    e.stopPropagation();
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeDrawer();
  });

  drawer?.addEventListener("click", (e) => {
    const a = e.target.closest("a");
    if (!a) return;
    closeDrawer();
  });

  // Wallet dropdown inside drawer
  const mWalletBtn = drawer?.querySelector("[data-mwallet-btn]");
  const mWalletMenu = document.getElementById("mWalletMenu");

  const hideMWallet = () => {
    if (mWalletMenu) mWalletMenu.hidden = true;
  };

  mWalletBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    if (!mWalletMenu) return;
    mWalletMenu.hidden = !mWalletMenu.hidden;
  });

  drawer?.addEventListener("click", hideMWallet);

  // AUTH MODAL open/close + tabs + otp tabs + password toggle
  (() => {
    const modal = document.getElementById("authModal");
    if (!modal) return;

    const body = document.body;
    const title = modal.querySelector("#authTitle");
    const sub = modal.querySelector(".modal__sub");

    const tabBtns = Array.from(modal.querySelectorAll("[data-auth-tab]"));
    const panes = Array.from(modal.querySelectorAll("[data-auth-pane]"));

    const otpTabBtns = Array.from(modal.querySelectorAll("[data-otp-tab]"));
    const otpPanes = Array.from(modal.querySelectorAll("[data-otp-pane]"));

    const setMode = (mode) => {
      tabBtns.forEach((b) => b.classList.toggle("is-active", b.dataset.authTab === mode));
      panes.forEach((p) => p.classList.toggle("is-active", p.dataset.authPane === mode));

      if (title) title.textContent = mode === "register" ? "Create Account" : "Sign In";
      if (sub)
        sub.textContent =
          mode === "register" ? "Fill in your details to register." : "Use your username and password.";
    };

    const open = (mode) => {
      modal.classList.add("is-open");
      modal.setAttribute("aria-hidden", "false");
      body.classList.add("modal-open");
      body.style.overflow = "hidden";
      setMode(mode || "login");
    };

    const close = () => {
      modal.classList.remove("is-open");
      modal.setAttribute("aria-hidden", "true");
      body.classList.remove("modal-open");
      body.style.overflow = "";
    };

    document.querySelectorAll("[data-open-modal]").forEach((el) => {
      el.addEventListener("click", () => open(el.dataset.openModal));
    });

    modal.querySelectorAll("[data-close-modal]").forEach((el) => {
      el.addEventListener("click", close);
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && modal.classList.contains("is-open")) close();
    });

    tabBtns.forEach((b) => {
      b.addEventListener("click", () => setMode(b.dataset.authTab));
    });

    const setOtp = (mode) => {
      otpTabBtns.forEach((b) => b.classList.toggle("is-active", b.dataset.otpTab === mode));
      otpPanes.forEach((p) => p.classList.toggle("is-active", p.dataset.otpPane === mode));
      const otpTypeInput = modal.querySelector('input[name="otp_type"]');
      if (otpTypeInput) otpTypeInput.value = mode;
    };

    otpTabBtns.forEach((b) => {
      b.addEventListener("click", () => setOtp(b.dataset.otpTab));
    });

    modal.querySelectorAll("[data-toggle-pass]").forEach((btn) => {
      btn.addEventListener("click", () => {
        const input = btn.parentElement?.querySelector("input");
        if (!input) return;
        input.type = input.type === "password" ? "text" : "password";
      });
    });

    setOtp("mobile");

    if (window.__OPEN_AUTH_MODAL__) {
      open(window.__OPEN_AUTH_MODAL__);
      window.__OPEN_AUTH_MODAL__ = null;
    }
  })();

  // Accordion
  document.querySelectorAll("[data-accordion]").forEach((acc) => {
    acc.addEventListener("click", (e) => {
      const btn = e.target.closest(".ddAcc__btn");
      if (!btn) return;

      e.stopPropagation();

      const item = btn.closest(".ddAcc__item");
      const panel = item.querySelector(".ddAcc__panel");
      const isOpen = btn.getAttribute("aria-expanded") === "true";

      acc.querySelectorAll(".ddAcc__item").forEach((it) => {
        const b = it.querySelector(".ddAcc__btn");
        const p = it.querySelector(".ddAcc__panel");
        if (!b || !p) return;
        b.setAttribute("aria-expanded", "false");
        p.hidden = true;
      });

      if (!isOpen) {
        btn.setAttribute("aria-expanded", "true");
        panel.hidden = false;
      } else {
        btn.setAttribute("aria-expanded", "false");
        panel.hidden = true;
      }
    });
  });

  // ----------------------------
  // Launch game (shared: Home + Games page)
  // ----------------------------
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
  const launching = new Set();
  const launchGame = async (gameId, el) => {
    if (!gameId) return;
    if (launching.has(gameId)) return;

    launching.add(gameId);

    const oldTitle = el?.getAttribute("title") || "";
    if (el) {
      el.classList.add("is-loading");
      el.setAttribute("title", "Launching...");
      el.style.pointerEvents = "none";
    }

    try {
      const res = await fetch("/games/launch", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrf,
          "X-Requested-With": "XMLHttpRequest",
          "Accept": "application/json",
        },
        credentials: "same-origin",
        body: JSON.stringify({ game_id: Number(gameId) }),
      });

      if (res.status === 401) {
        alert("Please register or login");
        document.querySelector('[data-open-modal="login"]')?.click();
        return;
      }

      const data = await res.json().catch(() => null);
      const url = data?.url || data?.data?.url;
      const ok = data?.ok === true || data?.code === 0;

      if (!res.ok || !ok || !url) {
        alert(data?.message || data?.msg || "Launch failed");
        return;
      }

      window.location.href = `/play/${gameId}`;
    } catch (e) {
      alert("Network error launching game.");
    } finally {
      launching.delete(gameId);
      if (el) {
        el.classList.remove("is-loading");
        el.setAttribute("title", oldTitle);
        el.style.pointerEvents = "";
      }
    }
  };

  document.body.addEventListener("click", (e) => {
    const card = e.target.closest("[data-launch-game]");
    if (!card) return;

    e.preventDefault();

    const gameId = card.getAttribute("data-game-id");
    launchGame(gameId, card);
  });

  // ----------------------------
  // Deterministic placeholder colors
  // ----------------------------
  const hash32 = (str) => {
    let h = 0x811c9dc5;
    for (let i = 0; i < str.length; i++) {
      h ^= str.charCodeAt(i);
      h = Math.imul(h, 0x01000193);
    }
    return h >>> 0;
  };

  const hslToHex = (h, s, l) => {
    s /= 100;
    l /= 100;
    const k = (n) => (n + h / 30) % 12;
    const a = s * Math.min(l, 1 - l);
    const f = (n) => l - a * Math.max(-1, Math.min(k(n) - 3, Math.min(9 - k(n), 1)));
    const toHex = (x) => Math.round(255 * x).toString(16).padStart(2, "0");
    return `#${toHex(f(0))}${toHex(f(8))}${toHex(f(4))}`;
  };

  const pickColor = (key) => {
    const h = hash32(String(key || "x"));
    const hue = h % 360;
    const sat = 62 + (h % 18);
    const light = 38 + ((h >>> 8) % 14);
    return hslToHex(hue, sat, light);
  };

  document.querySelectorAll("[data-ph]").forEach((el) => {
    const key = el.getAttribute("data-ph") || "";
    el.style.setProperty("--ph", pickColor(key));
  });

  // ----------------------------
  // Wallet strip accordion (collapse / expand) - MOBILE ONLY
  // ----------------------------
  (() => {
    const walletAcc = document.querySelector("[data-wallet-acc]");
    if (!walletAcc) return;

    const btn = walletAcc.querySelector("[data-wallet-acc-btn]");
    const body = walletAcc.querySelector("[data-wallet-acc-body]");
    if (!btn || !body) return;

    const mq = window.matchMedia("(max-width: 900px)");

    const setOpen = (open) => {
      btn.setAttribute("aria-expanded", open ? "true" : "false");
      walletAcc.classList.toggle("is-collapsed", !open);
      body.style.maxHeight = open ? body.scrollHeight + "px" : "0px";
    };

    const syncMode = () => {
      if (mq.matches) {
        requestAnimationFrame(() => setOpen(true));
        btn.style.pointerEvents = "";
      } else {
        walletAcc.classList.remove("is-collapsed");
        btn.setAttribute("aria-expanded", "true");
        body.style.maxHeight = "";
        btn.style.pointerEvents = "none";
      }
    };

    btn.addEventListener("click", () => {
      if (!mq.matches) return;
      const isOpen = btn.getAttribute("aria-expanded") === "true";
      setOpen(!isOpen);
    });

    window.addEventListener("resize", () => {
      if (!mq.matches) return;
      const isOpen = btn.getAttribute("aria-expanded") === "true";
      if (isOpen) body.style.maxHeight = body.scrollHeight + "px";
    });

    mq.addEventListener?.("change", syncMode);
    syncMode();
  })();

  // ----------------------------
  // ✅ LIVE WALLET BALANCES (Header + Mobile Drawer)
  // ----------------------------
  (() => {
    const anyHook =
      document.querySelector("[data-wallet-main]") ||
      document.querySelector("[data-wallet-chips]") ||
      document.querySelector("[data-wallet-bonus]");
    if (!anyHook) return;

    const moneyFmt = new Intl.NumberFormat("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });

    const setTextAll = (selector, val) => {
      document.querySelectorAll(selector).forEach((el) => {
        el.textContent = val;
      });
    };

    const state = {
      main: null,
      chips: null,
      bonus: null,
      currency: null,
    };

    const applyBalances = (payload) => {
      const main = Number(payload?.main);
      const chips = Number(payload?.chips);
      const bonus = Number(payload?.bonus);

      if (Number.isFinite(main) && state.main !== main) {
        state.main = main;
        setTextAll("[data-wallet-main]", moneyFmt.format(main));
      }

      if (Number.isFinite(chips) && state.chips !== chips) {
        state.chips = chips;
        setTextAll("[data-wallet-chips]", moneyFmt.format(chips));
      }

      if (Number.isFinite(bonus) && state.bonus !== bonus) {
        state.bonus = bonus;
        setTextAll("[data-wallet-bonus]", moneyFmt.format(bonus));
      }
    };

    let pollTimer = null;

    const fetchAll = async () => {
      try {
        const res = await fetch("/wallet/balances", {
          method: "GET",
          headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
          },
          credentials: "same-origin",
          cache: "no-store",
        });

        if (res.status === 401) return;
        const data = await res.json().catch(() => null);
        if (!res.ok || !data?.ok) return;

        applyBalances(data);
      } catch (e) {}
    };

    const start = () => {
      if (pollTimer) return;
      fetchAll();
      pollTimer = setInterval(fetchAll, 2500);
    };

    const stop = () => {
      if (!pollTimer) return;
      clearInterval(pollTimer);
      pollTimer = null;
    };

    document.addEventListener("visibilitychange", () => {
      if (document.hidden) stop();
      else start();
    });

    window.addEventListener("focus", fetchAll);

    start();
  })();
  
    (() => {
      const openBtn = document.querySelector("[data-open-new-ticket]");
      const modal = document.getElementById("newTicketModal");
      if (!modal) return;
    
      const open = () => {
        modal.hidden = false;
        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("modal-open");
      };
    
      const close = () => {
        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("modal-open");
        setTimeout(() => {
          if (!modal.classList.contains("is-open")) modal.hidden = true;
        }, 200);
      };
    
      openBtn?.addEventListener("click", (e) => {
        e.preventDefault();
        open();
      });
    
      modal.querySelectorAll("[data-close-new-ticket]").forEach((el) => {
        el.addEventListener("click", (e) => {
          e.preventDefault();
          close();
        });
      });
    
      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && !modal.hidden) close();
      });
    
      if (window.__OPEN_NEW_TICKET__) {
        open();
        window.__OPEN_NEW_TICKET__ = null;
      }
    })();
});