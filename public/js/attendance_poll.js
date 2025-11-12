(function () {
  const tbody = document.getElementById("tbody-registros");
  const totalsBox = document.getElementById("totales-empleado");
  const probe = document.getElementById("probe");
  if (!tbody || !probe) return;

  const url = probe.getAttribute("data-url");
  if (!url) return;

  let lastSig = null;

  async function refresh() {
    try {
      const res = await fetch(url, { cache: "no-store" });
      if (!res.ok) return;
      const json = await res.json();
      if (!json || !json.sig) return;

      // Si es el primer fetch o cambió la firma, inyecta HTML
      if (lastSig !== json.sig) {
        tbody.innerHTML = json.rows_html || "";
        if (totalsBox) totalsBox.innerHTML = json.totals_html || "";
        lastSig = json.sig;
      }
    } catch (e) {
      // Silencioso
    }
  }

  // Primer render + polling cada 5s
  refresh();
  setInterval(refresh, 5000);
})();
