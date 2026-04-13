(function () {
  function togglePassword(button) {
    if (!button) return false;
    var selector = button.getAttribute("data-toggle-password");
    if (!selector) return false;

    var input = document.querySelector(selector);
    if (!input) return false;

    var currentlyPassword = input.type === "password";
    try {
      input.type = currentlyPassword ? "text" : "password";
    } catch (err) {
      return false;
    }

    // Fallback in case third-party styles mask text visibility.
    input.style.webkitTextSecurity = currentlyPassword ? "none" : "";

    button.classList.toggle("is-visible", currentlyPassword);
    var icon = button.querySelector("i");
    if (icon) {
      icon.classList.remove("fa-eye", "fa-eye-slash");
      icon.classList.add(currentlyPassword ? "fa-eye-slash" : "fa-eye");
    }
    return false;
  }

  function cleanModalArtifacts() {
    var dialogs = document.querySelectorAll(".noyona-account-modal-dialog");
    if (!dialogs.length) return;

    dialogs.forEach(function (dialog) {
      dialog.querySelectorAll("p").forEach(function (p) {
        var txt = (p.textContent || "").replace(/\u00a0/g, " ").trim();
        if (txt === "" && p.children.length === 0) {
          p.remove();
        }
      });

      dialog
        .querySelectorAll("br, [data-lastpass-icon-root], [data-lastpass-root]")
        .forEach(function (node) {
          node.remove();
        });
    });
  }

  function bindToggles() {
    document
      .querySelectorAll(".noyona-account-modal-password-toggle[data-toggle-password]")
      .forEach(function (button) {
        button.addEventListener("click", function (event) {
          event.preventDefault();
          event.stopPropagation();
          togglePassword(button);
        });
      });
  }

  document.addEventListener("click", function (event) {
    var toggle = event.target.closest(
      ".noyona-account-modal-password-toggle[data-toggle-password]"
    );
    if (!toggle) return;
    event.preventDefault();
    event.stopPropagation();
    togglePassword(toggle);
  });

  function init() {
    cleanModalArtifacts();
    bindToggles();

    var observer = new MutationObserver(function () {
      cleanModalArtifacts();
    });
    observer.observe(document.body, { childList: true, subtree: true });

    window.noyonaToggleAccountPassword = function (toggle) {
      return togglePassword(toggle);
    };
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
