(function () {
  var toggle = document.getElementById("navbarToggle");
  var menu = document.getElementById("navbarMenu");
  var root = document.getElementById("navbar");

  if (!toggle || !menu || !root) return;

  toggle.addEventListener("click", function () {
    var isOpen = root.classList.toggle("open");
    toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
  });

  // Cierra el menú al hacer click en un enlace (móvil)
  menu.addEventListener("click", function (e) {
    if (e.target.matches("a.navbar-link")) {
      root.classList.remove("open");
      toggle.setAttribute("aria-expanded", "false");
    }
  });
})();
