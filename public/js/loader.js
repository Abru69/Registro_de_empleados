const LoaderManager = (function () {
  let overlay = null;
  let loadingText = null;

  function init() {
    // Verificar si ya existe el overlay
    overlay = document.getElementById("loadingOverlay");
    if (!overlay) {
      console.warn("LoadingOverlay no encontrado en el DOM");
      return false;
    }
    loadingText = overlay.querySelector(".loading-text");
    return true;
  }

  function show(message = "Cargando datos...") {
    if (!overlay && !init()) return;

    if (loadingText) {
      loadingText.textContent = message;
    }

    overlay.classList.remove("fade-out");
    overlay.classList.add("active");
  }

  function hide() {
    if (!overlay) return;

    overlay.classList.add("fade-out");
    setTimeout(() => {
      overlay.classList.remove("active", "fade-out");
    }, 300);
  }

  function isActive() {
    return overlay && overlay.classList.contains("active");
  }

  // Auto-inicializar cuando el DOM esté listo
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }

  return {
    show,
    hide,
    isActive,
  };
})();
