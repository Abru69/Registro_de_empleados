(function () {
  const inpNombre = document.getElementById("filterNombreSemanal");
  const inpSemana = document.getElementById("filterSemana");
  const btnFiltrar = document.getElementById("btnFiltrarSemanal");
  const btnMostrar = document.getElementById("btnMostrarTodoSemanal");
  const btnExportar = document.getElementById("btnExportarSemanal");

  let autoRefreshInterval = null;
  let lastDataSignature = null;

  // Componente personalizado de Loading
  class CustomLoadingOverlay {
    init(params) {
      this.eGui = document.createElement("div");
      this.eGui.innerHTML = `
                <div class="ag-overlay-loading-center">
                    <div class="custom-loading-spinner"></div>
                    <div class="custom-loading-text">${params.loadingMessage}</div>
                </div>
            `;
    }

    getGui() {
      return this.eGui;
    }
  }

  // Componente personalizado de No Rows
  class CustomNoRowsOverlay {
    init(params) {
      this.eGui = document.createElement("div");
      this.eGui.innerHTML = `
                <div class="ag-overlay-no-rows-center">
                    <span class="custom-no-data-icon"></span>
                    <div class="custom-no-data-message">
                        ${params.noRowsMessageFunc()}
                    </div>
                </div>
            `;
    }

    getGui() {
      return this.eGui;
    }
  }

  const columnDefs = [
    {
      field: "nombre",
      headerName: "Empleado",
      flex: 2,
      minWidth: 180,
      pinned: "left",
    },
    {
      field: "semana_iso",
      headerName: "Semana",
      flex: 1,
      minWidth: 120,
    },
    {
      field: "fecha_inicio",
      headerName: "Inicio (Lunes)",
      flex: 1.2,
      minWidth: 130,
    },
    {
      field: "fecha_fin",
      headerName: "Fin (Domingo)",
      flex: 1.2,
      minWidth: 130,
    },
    {
      field: "total_registros",
      headerName: "Días Trabajados",
      flex: 1,
      minWidth: 140,
      cellStyle: { textAlign: "center" },
    },
    {
      field: "total_horas_decimal",
      headerName: "Total por semana",
      flex: 1.5,
      minWidth: 150,
      valueFormatter: (params) => {
        // Prefer `total_minutos` (returned by the API) for precise H:MM formatting.
        const data = params.data || {};
        const minutos = data.total_minutos;

        function formatMinutesToHMM(totalMin) {
          if (totalMin === null || totalMin === undefined) return "0:00 (0 horas con 0 minutos)";
          const m = Number(totalMin);
          if (Number.isNaN(m) || m <= 0) return "0:00 (0 horas con 0 minutos)";
          const h = Math.floor(m / 60);
          const mm = m % 60;
          const minutosStr = mm.toString().padStart(2, "0");
          const horasTexto = h === 1 ? "1 hora" : `${h} horas`;
          const minutosTexto = mm === 1 ? "1 minuto" : `${mm} minutos`;
          return `${h}:${minutosStr} (${horasTexto} con ${minutosTexto})`;
        }

        // If total_minutos is present use it; otherwise fall back to decimal value
        if (minutos !== null && minutos !== undefined) {
          return formatMinutesToHMM(minutos);
        }

        const decimalVal = params.value;
        if (decimalVal || decimalVal === 0) {
          // convert decimal hours (e.g., 7.5) to minutes
          const totalMin = Math.round(Number(decimalVal) * 60);
          return formatMinutesToHMM(totalMin);
        }

        return "0:00 (0 horas con 0 minutos)";
      },
      cellStyle: { fontWeight: "bold", color: "#28a745", fontSize: "15px" },
    },
  ];

  const gridOptions = {
    columnDefs: columnDefs,
    defaultColDef: {
      sortable: true,
      resizable: true,
      filter: false,
    },
    pagination: true,
    paginationPageSize: 15,
    paginationPageSizeSelector: [15, 25, 50, 100],
    domLayout: "normal",
    rowData: [],
    suppressColumnVirtualisation: true,

    loadingOverlayComponent: CustomLoadingOverlay,
    loadingOverlayComponentParams: {
      loadingMessage: "Cargando...",
    },

    localeText: {
      page: "Página",
      more: "Más",
      to: "a",
      of: "de",
      next: "Siguiente",
      last: "Último",
      first: "Primero",
      previous: "Anterior",
      loadingOoo: "Cargando...",
      noRowsToShow:
        "No hay datos suficientes para mostrar. Espera que cumplas con la primera semana",
    },
    onGridReady: (params) => {
      params.api.sizeColumnsToFit();
    },
    onGridSizeChanged: (params) => {
      params.api.sizeColumnsToFit();
    },
  };

  const gridDiv = document.querySelector("#weeklyGrid");
  if (!gridDiv) {
    console.error("No se encontró el elemento #weeklyGrid");
    return;
  }

  const gridApi = agGrid.createGrid(gridDiv, gridOptions);

  function buildURL() {
    const base = "../../app/api/weekly_hours.php";
    const q = new URLSearchParams();

    if (inpNombre?.value) q.set("nombre", inpNombre.value.trim());
    if (inpSemana?.value) q.set("semana", inpSemana.value);

    const qs = q.toString();
    return qs ? `${base}?${qs}` : base;
  }

  function generateSignature(data) {
    if (!data || !Array.isArray(data)) return null;
    return JSON.stringify(
      data.map((row) => `${row.nombre}-${row.semana_iso}-${row.total_minutos}`)
    );
  }

  async function loadData(silent = false) {
    const url = buildURL();

    // Mostrar loader solo si NO es silent
    if (!silent) {
      if (window.LoaderManager) {
        LoaderManager.show("Filtrando horas semanales...");
      } else {
        try {
          gridApi.showLoadingOverlay();
        } catch (_) {
        } finally {
          if (!silent && window.LoaderManager) {
            LoaderManager.hide();
          }
        }
      }
    }

    try {
      const response = await fetch(url, { cache: "no-store" });

      if (!response.ok) {
        const errorText = await response.text();
        console.error("Error del servidor:", errorText);
        throw new Error(`Error ${response.status}: ${response.statusText}`);
      }

      const json = await response.json();

      if (json.error) {
        console.error("Error del servidor:", json.message);
        alert(
          `Error: ${json.message}\nArchivo: ${json.file}\nLínea: ${json.line}`
        );
        gridApi.setGridOption("rowData", []);
        return;
      }

      const rows = json.data || [];

      const currentSignature = generateSignature(rows);

      if (!silent || lastDataSignature !== currentSignature) {
        gridApi.setGridOption("rowData", rows);
        lastDataSignature = currentSignature;

        // Si no hay datos, mostrar overlay de "sin datos"
        if (rows.length === 0) {
          gridApi.showNoRowsOverlay();
        } else {
          gridApi.hideOverlay();
        }

        setTimeout(() => {
          gridApi.sizeColumnsToFit();
        }, 100);
      }
    } catch (error) {
      console.error("Error al cargar datos semanales:", error);
      if (!silent) {
        gridApi.setGridOption("rowData", []);
        gridApi.showNoRowsOverlay();
      }
    } finally {
      if (!silent) {
        LoaderManager.hide();
      }
    }
  }

  function startAutoRefresh() {
    if (autoRefreshInterval) {
      clearInterval(autoRefreshInterval);
    }

    autoRefreshInterval = setInterval(() => {
      loadData(true);
    }, 10000);
  }

  function stopAutoRefresh() {
    if (autoRefreshInterval) {
      clearInterval(autoRefreshInterval);
      autoRefreshInterval = null;
    }
  }

  if (btnFiltrar) {
    btnFiltrar.addEventListener("click", () => {
      loadData(false);
      stopAutoRefresh();
      startAutoRefresh();
    });
  }

  if (btnMostrar) {
    btnMostrar.addEventListener("click", () => {
      if (inpNombre) inpNombre.value = "";
      if (inpSemana) inpSemana.value = "";
      loadData(false);
      stopAutoRefresh();
      startAutoRefresh();
    });
  }

  if (btnExportar) {
    btnExportar.addEventListener("click", () => {
      gridApi.exportDataAsCsv({
        fileName: `horas_semanales_${
          new Date().toISOString().split("T")[0]
        }.csv`,
      });
    });
  }

  [inpNombre, inpSemana].forEach((input) => {
    if (input) {
      input.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
          loadData(false);
          stopAutoRefresh();
          startAutoRefresh();
        }
      });
    }
  });

  window.addEventListener("resize", () => {
    if (gridApi) {
      gridApi.sizeColumnsToFit();
    }
  });

  document.addEventListener("visibilitychange", () => {
    if (document.hidden) {
      stopAutoRefresh();
    } else {
      loadData(true);
      startAutoRefresh();
    }
  });

  loadData(false);
  startAutoRefresh();

  window.addEventListener("beforeunload", () => {
    stopAutoRefresh();
  });
})();
