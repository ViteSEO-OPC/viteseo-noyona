(function () {
  function initFaqBlogpost(block) {
    if (!block) {
      return;
    }

    var items = Array.prototype.slice.call(
      block.querySelectorAll(".faq-blogpost__item")
    );

    if (!items.length) {
      return;
    }

    items.forEach(function (item) {
      item.addEventListener("toggle", function () {
        if (!item.open) {
          return;
        }

        items.forEach(function (other) {
          if (other !== item) {
            other.open = false;
          }
        });
      });
    });
  }

  function initAll() {
    var blocks = document.querySelectorAll(".wp-block-noyona-faq-blogpost");
    if (!blocks.length) {
      return;
    }

    blocks.forEach(initFaqBlogpost);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAll);
    return;
  }

  initAll();
})();
