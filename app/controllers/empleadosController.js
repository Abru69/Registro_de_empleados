// Registro de Entrada/Salida - Main JavaScript
document.addEventListener("DOMContentLoaded", () => {
  const btnEntrada = document.getElementById("btnEntrada");
  const btnSalida = document.getElementById("btnSalida");
  const inputNombre = document.getElementById("nombre");
  const mensaje = document.getElementById("mensaje");
  const horaActual = document.getElementById("hora-actual");

  // Actualizar hora actual cada segundo
  function actualizarHora() {
    const ahora = new Date();
    const opciones = {
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
      hour12: false,
    };
    horaActual.textContent = `Hora actual: ${ahora.toLocaleTimeString(
      "es-MX",
      opciones
    )}`;
  }

  actualizarHora();
  setInterval(actualizarHora, 1000);

  // --- Función reutilizable para registrar movimientos ---
  function registrarMovimiento(accion) {
    const nombre = inputNombre.value.trim();

    if (nombre === "") {
      mostrarMensaje("Por favor, ingrese su nombre", "error");
      return;
    }

    // Determinar qué botón deshabilitar
    const botonPresionado = accion === "entrada" ? btnEntrada : btnSalida;
    botonPresionado.disabled = true;
    botonPresionado.textContent = "Registrando...";

    const formData = new FormData();
    formData.append("nombre", nombre);
    formData.append("accion", accion); // Enviamos la acción (entrada o salida)

    fetch("app/models/registrar.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.status === "ok") {
          mostrarMensaje(data.mensaje, "success");
          inputNombre.value = "";
          inputNombre.focus();
        } else {
          mostrarMensaje(data.mensaje, "error");
        }
      })
      .catch((error) => {
        mostrarMensaje("Error al registrar", "error");
        console.error("Error:", error);
      })
      .finally(() => {
        // Habilitar ambos botones al finalizar
        btnEntrada.disabled = false;
        btnEntrada.textContent = "Registrar Entrada";
        btnSalida.disabled = false;
        btnSalida.textContent = "Registrar Salida";
      });
  }

  // --- Event Listeners para ambos botones ---
  btnEntrada.addEventListener("click", () => {
    registrarMovimiento("entrada");
  });

  btnSalida.addEventListener("click", () => {
    registrarMovimiento("salida");
  });

  // Permitir registro de ENTRADA con Enter
  inputNombre.addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
      btnEntrada.click();
    }
  });

  // Función para mostrar mensajes
  function mostrarMensaje(texto, tipo) {
    mensaje.textContent = texto;
    mensaje.className = tipo;
    mensaje.style.display = "block";

    setTimeout(() => {
      mensaje.style.display = "none";
    }, 4000);
  }
});
