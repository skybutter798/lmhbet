document.addEventListener("DOMContentLoaded", () => {
  const grid = document.getElementById("gamesGrid");
  const sentinel = document.getElementById("gridSentinel");
  const status = document.getElementById("gridStatus");

  const setStatus = (msg) => {
    if (!status) return;
    status.textContent = msg || "";
  };

  if (grid && sentinel) {
    let nextUrl = grid.dataset.next || "";
    let loading = false;
    let done = false;

    const fetchMore = async () => {
      if (done || loading) return;
      if (!nextUrl) {
        done = true;
        setStatus("");
        return;
      }

      loading = true;
      setStatus("Loading more games...");

      try {
        const res = await fetch(nextUrl, {
          headers: { "X-Requested-With": "XMLHttpRequest" },
          credentials: "same-origin",
        });

        if (!res.ok) throw new Error("Failed to load");
        const data = await res.json();

        if (data && data.html) {
          const tmp = document.createElement("div");
          tmp.innerHTML = data.html;

          while (tmp.firstChild) {
            grid.appendChild(tmp.firstChild);
          }
        }

        nextUrl = data && data.next ? data.next : "";
        grid.dataset.next = nextUrl;

        if (!nextUrl) done = true;
        setStatus("");
      } catch (e) {
        setStatus("Could not load more. Scroll to retry.");
        loading = false;
        return;
      }

      loading = false;
    };

    const io = new IntersectionObserver(
      (entries) => {
        const ent = entries[0];
        if (ent && ent.isIntersecting) fetchMore();
      },
      { root: null, rootMargin: "900px 0px", threshold: 0.01 }
    );

    io.observe(sentinel);
  }
});
