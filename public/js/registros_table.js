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
    paginationPageSize: 25,
    paginationPageSizeSelector: [10, 25, 50, 100],
    domLayout: "normal",
    rowData: [],
    suppressColumnVirtualisation: true,
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

        setTimeout(() => {
          gridApi.sizeColumnsToFit();
        }, 100);
      }
    } catch (error) {
      console.error("Error al cargar datos:", error);
      if (!silent) {
        gridApi.setGridOption("rowData", []);
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
      loadData(false);
      stopAutoRefresh();
      startAutoRefresh();
    });
  }

  if (btnMostrar) {
    btnMostrar.addEventListener("click", () => {
      if (inpDesde) inpDesde.value = "";
      if (inpHasta) inpHasta.value = "";
      if (inpNombre) inpNombre.value = "";
      loadData(false);
      stopAutoRefresh();
      startAutoRefresh();
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
