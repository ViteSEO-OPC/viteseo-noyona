/**
 * Carousel logic for Product Slide block.
 */
(function () {
  function debounce(fn, wait) {
    let t;
    return function () {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, arguments), wait);
    };
  }

  function perView(maxCards) {
    const w = window.innerWidth;
    // Prefer 4 cards on desktop/large laptops, fallback to 3 on standard laptops.
    if (w >= 1600) return Math.min(4, maxCards);
    if (w >= 1260) return Math.min(3, maxCards);
    if (w >= 770) return Math.min(2, maxCards);
    return 1;
  }

  function getAddToCartEndpoint() {
    if (window.wc_add_to_cart_params && window.wc_add_to_cart_params.wc_ajax_url) {
      return String(window.wc_add_to_cart_params.wc_ajax_url).replace(
        "%%endpoint%%",
        "add_to_cart"
      );
    }
    // Fallback endpoint when Woo params are unavailable on custom pages.
    return "/?wc-ajax=add_to_cart";
  }

  function initCarousel(root) {
    const track = root.querySelector(".product-slide__track");
    const cards = Array.from(root.querySelectorAll(".product-slide__card"));
    const prevBtn = root.querySelector(".ps-prev");
    const nextBtn = root.querySelector(".ps-next");
    const dotsContainer = root.querySelector(".product-slide__dots");

    if (!track || cards.length === 0 || !prevBtn || !nextBtn || !dotsContainer)
      return;

    const maxCards = parseInt(root.dataset.cardsToShow, 10) || 3;
    let currentIndex = 0;
    let view = perView(maxCards);
    let maxIndex = Math.max(0, cards.length - view);
    let totalPositions = Math.max(1, maxIndex + 1);

    function buildDots() {
      dotsContainer.innerHTML = "";
      for (let i = 0; i < totalPositions; i++) {
        const dot = document.createElement("button");
        dot.className = "ps-dot" + (i === currentIndex ? " active" : "");
        dot.setAttribute("aria-label", "Go to slide " + (i + 1));
        dot.addEventListener("click", () => goTo(i));
        dotsContainer.appendChild(dot);
      }
    }

    function updateWidths() {
      view = perView(maxCards);
      maxIndex = Math.max(0, cards.length - view);
      totalPositions = Math.max(1, maxIndex + 1);
      track.style.setProperty("--cards-visible", view);
      cards.forEach((card) => {
        card.style.flexBasis = 100 / view + "%";
        card.style.maxWidth = 100 / view + "%";
      });
      if (currentIndex > maxIndex) currentIndex = maxIndex;
      if (currentIndex < 0) currentIndex = 0;
      buildDots();
      update();
    }

    function update() {
      const offset = -(currentIndex * (100 / view));
      track.style.transform = `translateX(${offset}%)`;
      prevBtn.disabled = currentIndex === 0;
      nextBtn.disabled = currentIndex >= maxIndex;
      Array.from(dotsContainer.children).forEach((dot, idx) => {
        dot.classList.toggle("active", idx === currentIndex);
      });

      const hideNav = maxIndex <= 0;
      prevBtn.style.display = hideNav ? "none" : "";
      nextBtn.style.display = hideNav ? "none" : "";
      dotsContainer.style.display = hideNav ? "none" : "";
    }

    function goTo(index) {
      if (index < 0) index = 0;
      if (index > maxIndex) index = maxIndex;
      currentIndex = index;
      update();
    }

    prevBtn.addEventListener("click", () => goTo(currentIndex - 1));
    nextBtn.addEventListener("click", () => goTo(currentIndex + 1));

    root.addEventListener("click", function (event) {
      const cartBtn = event.target.closest(
        ".ps-btn-cart.ajax_add_to_cart[data-product_id]"
      );
      if (!cartBtn || !root.contains(cartBtn)) return;

      event.preventDefault();

      if (cartBtn.classList.contains("loading")) return;

      const endpoint = getAddToCartEndpoint();
      const href =
        cartBtn.getAttribute("href") ||
        cartBtn.getAttribute("data-cart-url") ||
        "";
      const productId = parseInt(cartBtn.getAttribute("data-product_id"), 10) || 0;
      const quantity = parseInt(cartBtn.getAttribute("data-quantity"), 10) || 1;

      if (!endpoint || !productId) {
        if (href) window.location.href = href;
        return;
      }

      cartBtn.classList.add("loading");

      fetch(endpoint, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: new URLSearchParams({
          product_id: String(productId),
          quantity: String(quantity),
        }).toString(),
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          if (data && data.error && data.product_url) {
            window.location.href = data.product_url;
            return;
          }

          cartBtn.classList.remove("loading");
          cartBtn.classList.add("added");

          // Keep Woo fragments/cart counters in sync with header mini-cart.
          if (window.jQuery) {
            window.jQuery(document.body).trigger("added_to_cart", [
              data && data.fragments ? data.fragments : null,
              data && data.cart_hash ? data.cart_hash : "",
              cartBtn,
            ]);
          }
        })
        .catch(function () {
          if (href) window.location.href = href;
        })
        .finally(function () {
          cartBtn.classList.remove("loading");
        });
    });

    const onResize = debounce(updateWidths, 150);
    window.addEventListener("resize", onResize);

    updateWidths();
  }

  function onReady() {
    document
      .querySelectorAll(".wp-block-noyona-product-slide")
      .forEach(initCarousel);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", onReady);
  } else {
    onReady();
  }
})();
