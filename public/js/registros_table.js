(function () {
  // Referencias a elementos del DOM
  const inpDesde = document.getElementById("filterDesde");
  const inpHasta = document.getElementById("filterHasta");
  const inpNombre = document.getElementById("filterNombre");
  const btnFiltrar = document.getElementById("btnFiltrar");
  const btnMostrar = document.getElementById("btnMostrarTodo");
  const btnExportar = document.getElementById("btnExportar");

  // Variable para controlar el auto-refresh
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

  // Configuración de columnas de AG-Grid (SIN floating filters)
  const columnDefs = [
    {
      field: "nombre",
      headerName: "Nombre del Empleado",
      filter: "agTextColumnFilter",
      floatingFilter: false,
      flex: 2,
      minWidth: 180,
    },
    {
      field: "fecha",
      headerName: "Fecha",
      filter: "agDateColumnFilter",
      floatingFilter: false,
      flex: 1,
      minWidth: 130,
      filterParams: {
        comparator: (filterDate, cellValue) => {
          if (!cellValue) return -1;
          const cellDate = new Date(cellValue);
          if (cellDate < filterDate) return -1;
          if (cellDate > filterDate) return 1;
          return 0;
        },
      },
    },
    {
      field: "hora",
      headerName: "Hora Entrada",
      flex: 1,
      minWidth: 120,
    },
    {
      field: "hora_salida",
      headerName: "Hora Salida",
      flex: 1,
      minWidth: 120,
      valueFormatter: (params) => params.value || "Sin salida",
    },
    {
      field: "total_hhmm",
      headerName: "Total de Horas",
      flex: 1,
      minWidth: 130,
    },
  ];

  // Configuración general del grid
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
    // Configuración del loading overlay personalizado
    loadingOverlayComponentParams: {
      loadingMessage: "Filtrando registros...",
    },
    // Configuración del mensaje sin datos
    noRowsOverlayComponent: CustomNoRowsOverlay,
    noRowsOverlayComponentParams: {
      noRowsMessageFunc: function () {
        return "No hay registros para mostrar";
      },
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
      noRowsToShow: "No hay registros para mostrar",
      filterOoo: "Filtrar...",
      searchOoo: "Buscar...",
      selectAll: "Seleccionar todo",
      equals: "Igual",
      notEqual: "Diferente",
      lessThan: "Menor que",
      greaterThan: "Mayor que",
      contains: "Contiene",
      notContains: "No contiene",
      startsWith: "Comienza con",
      endsWith: "Termina con",
    },
    onGridReady: (params) => {
      params.api.sizeColumnsToFit();
    },
    onGridSizeChanged: (params) => {
      params.api.sizeColumnsToFit();
    },
  };

  // Inicializar AG-Grid
  const gridDiv = document.querySelector("#myGrid");
  const gridApi = agGrid.createGrid(gridDiv, gridOptions);

  // Función para construir URL con parámetros de filtro
  function buildURL() {
    const base = "../api/attendance_list.php";
    const q = new URLSearchParams();

    if (inpDesde?.value) q.set("desde", inpDesde.value);
    if (inpHasta?.value) q.set("hasta", inpHasta.value);
    if (inpNombre?.value) q.set("nombre", inpNombre.value.trim());

    const qs = q.toString();
    return qs ? `${base}?${qs}` : base;
  }

  // Función para generar firma de datos (para detectar cambios)
  function generateSignature(data) {
    if (!data || !Array.isArray(data)) return null;
    return JSON.stringify(
      data.map(
        (row) =>
          `${row.id}-${row.nombre}-${row.fecha}-${row.hora}-${row.hora_salida}-${row.total_hhmm}`
      )
    );
  }

  // Función para cargar datos en el grid con AJAX
  async function loadData(silent = false) {
    const url = buildURL();

    if (!silent) {
      try {
        gridApi.showLoadingOverlay();
      } catch (_) {}
      if (window.LoaderManager) LoaderManager.show("Porfavor espere...");
    }

    try {
      const response = await fetch(url, { cache: "no-store" });

      if (!response.ok) {
        throw new Error("Error en la respuesta del servidor");
      }

      const json = await response.json();

      if (json.error) {
        console.error("Error del servidor:", json.message);
        gridApi.setGridOption("rowData", []);
        return;
      }

      const rows = json.data || [];

      // Generar firma de los datos actuales
      const currentSignature = generateSignature(rows);

      // Solo actualizar si los datos cambiaron (evita parpadeos innecesarios)
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
      } else {
        if (rows.length > 0) {
          gridApi.hideOverlay();
        }
      }
    } catch (error) {
      console.error("Error al cargar datos:", error);
      if (!silent) {
        gridApi.setGridOption("rowData", []);
      }
    } finally {
      if (!silent && window.LoaderManager) {
        LoaderManager.hide();
      }
    }
  }

  // Función para iniciar el auto-refresh (cada 5 segundos)
  function startAutoRefresh() {
    if (autoRefreshInterval) {
      clearInterval(autoRefreshInterval);
    }

    // Actualizar cada 5 segundos (modo silencioso para no interrumpir al usuario)
    autoRefreshInterval = setInterval(() => {
      loadData(true);
    }, 5000);
  }

  // Función para detener el auto-refresh
  function stopAutoRefresh() {
    if (autoRefreshInterval) {
      clearInterval(autoRefreshInterval);
      autoRefreshInterval = null;
    }
  }

  // Event listeners para filtros
  if (btnFiltrar) {
    btnFiltrar.addEventListener("click", () => {
      if (!validateDateFilters()) return;
      loadData(false);
      stopAutoRefresh();
      startAutoRefresh();
    });
  }

  // Función para resetear límites de los datepickers
  function resetDatePickerLimits() {
    const today = new Date();
    const pad = (n) => String(n).padStart(2, "0");
    const todayISO = `${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(
      today.getDate()
    )}`;

    // Eventos de validación
    if (inpDesde) {
      inpDesde.addEventListener("change", validateDateFilters);

      // Detectar cuando el usuario limpia el campo manualmente
      inpDesde.addEventListener("input", (e) => {
        if (!e.target.value) {
          resetDatePickerLimits();
          clearDateErrors();
        }
      });
    }

    if (inpHasta) {
      inpHasta.addEventListener("change", validateDateFilters);
      // Detectar cuando el usuario limpia el campo manualmente
      inpHasta.addEventListener("input", (e) => {
        if (!e.target.value) {
          resetDatePickerLimits();
          clearDateErrors();
        }
      });
    }
  }

  if (btnMostrar) {
    btnMostrar.addEventListener("click", () => {
      if (inpNombre) inpNombre.value = "";

      resetDatePickerLimits();
      clearDateErrors();

      loadData(false);
      stopAutoRefresh();
      startAutoRefresh();
    });
  }

  if (inpDesde) {
    inpDesde.addEventListener("input", (e) => {
      // Si el campo queda vacío (usuario borró la fecha)
      if (!e.target.value) {
        resetDatePickerLimits();
        clearDateErrors();
      }
    });
  }

  // Exportar a CSV
  if (btnExportar) {
    btnExportar.addEventListener("click", () => {
      gridApi.exportDataAsCsv({
        fileName: `asistencia_empleados_${
          new Date().toISOString().split("T")[0]
        }.csv`,
      });
    });
  }

  // Permitir filtrar con Enter
  [inpDesde, inpHasta, inpNombre].forEach((input) => {
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

  // Limitar calendarios: no permitir futuro
  (function setTodayMax() {
    const today = new Date();
    const pad = (n) => String(n).padStart(2, "0");
    const iso = `${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(
      today.getDate()
    )}`;
    if (inpDesde) inpDesde.max = iso;
    if (inpHasta) inpHasta.max = iso;
  })();

  function clearDateErrors() {
    [inpDesde, inpHasta].forEach((el) => {
      el.classList.remove("is-invalid");
      el.title = "";
      el.setCustomValidity("");
    });
  }

  function markInvalid(el, msg) {
    el.classList.add("is-invalid");
    el.title = msg;
    el.setCustomValidity(msg);
    el.reportValidity();
  }

  // Valida permitiendo igualdad (Desde <= Hasta) y bloqueando futuro
  function validateDateFilters() {
    clearDateErrors();
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const desdeValue = inpDesde.value ? new Date(inpDesde.value) : null;
    const hastaValue = inpHasta.value ? new Date(inpHasta.value) : null;

    // Si falta alguno, no validamos todavía
    if (!desdeValue || !hastaValue) return true;

    // Solo marcamos error si Desde > Hasta
    if (desdeValue.getTime() > hastaValue.getTime()) {
      markInvalid(
        inpDesde,
        "La fecha 'Desde' no puede ser posterior a la fecha 'Hasta'."
      );
      return false;
    }

    // No permitir fechas futuras
    if (desdeValue > hoy) {
      markInvalid(
        inpDesde,
        "La fecha 'Desde' no puede ser posterior a la fecha actual."
      );
      return false;
    }

    if (hastaValue > hoy) {
      markInvalid(
        inpHasta,
        "La fecha 'Hasta' no puede ser posterior a la fecha actual."
      );
      return false;
    }

    // Ajustar límites dinámicos SOLO cuando ambas fechas son válidas
    inpHasta.min = inpDesde.value;
    inpDesde.max = inpHasta.value;

    return true;
  }

  // Eventos
  inpDesde.addEventListener("change", validateDateFilters);
  inpHasta.addEventListener("change", validateDateFilters);

  // Si tienes botón Buscar
  const btnBuscar = document.getElementById("btnBuscar");
  if (btnBuscar) {
    btnBuscar.addEventListener("click", (e) => {
      if (!validateDateFilters()) {
        e.preventDefault();
        return;
      }
      loadData(false); // tu función existente
    });
  }

  // Reajustar columnas cuando cambia el tamaño de la ventana
  window.addEventListener("resize", () => {
    if (gridApi) {
      gridApi.sizeColumnsToFit();
    }
  });

  // Detener auto-refresh cuando el usuario cambia de pestaña (optimización)
  document.addEventListener("visibilitychange", () => {
    if (document.hidden) {
      stopAutoRefresh();
    } else {
      loadData(true);
      startAutoRefresh();
    }
  });

  // Cargar datos iniciales e iniciar auto-refresh
  loadData(false);
  startAutoRefresh();

  // Limpiar intervalo cuando se cierra la página
  window.addEventListener("beforeunload", () => {
    stopAutoRefresh();
  });
})();
