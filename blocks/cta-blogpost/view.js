(function () {
  function initCtaBlogpost(block) {
    if (!block) {
      return;
    }

    var button = block.querySelector(".cta-blogpost__button");
    if (!button) {
      return;
    }

    button.addEventListener("mousedown", function () {
      button.style.transform = "translateY(0)";
    });

    button.addEventListener("mouseup", function () {
      button.style.transform = "";
    });
  }

  function initAll() {
    var blocks = document.querySelectorAll(".wp-block-noyona-cta-blogpost");
    if (!blocks.length) {
      return;
    }

    blocks.forEach(initCtaBlogpost);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAll);
    return;
  }

  initAll();
})();
