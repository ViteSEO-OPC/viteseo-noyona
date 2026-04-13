/**
 * Carousel logic for Product Slide block.
 */
(function () {
  const CART_STORE_KEYS = ["wc/store/cart", "wc/store/cart-data", "wc/store"];

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

  function syncWooBlocksCartStore(cart) {
    if (!cart || !window.wp || !window.wp.data) return;

    CART_STORE_KEYS.forEach((storeKey) => {
      let dispatch = null;
      try {
        dispatch = window.wp.data.dispatch(storeKey);
      } catch (e) {
        dispatch = null;
      }
      if (!dispatch) return;

      if (typeof dispatch.receiveCart === "function") {
        try {
          dispatch.receiveCart(cart);
        } catch (e) {
          // Fall through to invalidation.
        }
      }
      if (typeof dispatch.receiveCartData === "function") {
        try {
          dispatch.receiveCartData(cart);
        } catch (e) {
          // Fall through to invalidation.
        }
      }
      if (typeof dispatch.receiveCartContents === "function") {
        try {
          dispatch.receiveCartContents(cart);
        } catch (e) {
          // Fall through to invalidation.
        }
      }
      if (typeof dispatch.invalidateResolutionForStore === "function") {
        try {
          dispatch.invalidateResolutionForStore();
        } catch (e) {
          // noop
        }
      }
      if (typeof dispatch.invalidateResolution === "function") {
        try {
          dispatch.invalidateResolution("getCartData", []);
        } catch (e) {
          // noop
        }
        try {
          dispatch.invalidateResolution("getCart", []);
        } catch (e) {
          // noop
        }
      }
    });
  }

  function invalidateWooBlocksCartStore() {
    if (!window.wp || !window.wp.data) return;

    CART_STORE_KEYS.forEach((storeKey) => {
      let dispatch = null;
      try {
        dispatch = window.wp.data.dispatch(storeKey);
      } catch (e) {
        dispatch = null;
      }
      if (!dispatch) return;

      if (typeof dispatch.invalidateResolutionForStore === "function") {
        try {
          dispatch.invalidateResolutionForStore();
        } catch (e) {
          // noop
        }
      }
      if (typeof dispatch.invalidateResolution === "function") {
        try {
          dispatch.invalidateResolution("getCartData", []);
        } catch (e) {
          // noop
        }
        try {
          dispatch.invalidateResolution("getCart", []);
        } catch (e) {
          // noop
        }
      }
    });
  }

  function broadcastCartAdded(detail) {
    const payload = Object.assign(
      {
        preserveCartData: false,
      },
      detail || {}
    );

    document.body.dispatchEvent(
      new CustomEvent("wc-blocks_added_to_cart", {
        bubbles: true,
        detail: payload,
      })
    );
    document.dispatchEvent(
      new CustomEvent("wc-blocks_added_to_cart", {
        bubbles: true,
        detail: payload,
      })
    );
    window.dispatchEvent(
      new CustomEvent("wc-blocks_added_to_cart", {
        detail: payload,
      })
    );
  }

  function notifyCartUpdated(data, sourceButton) {
    if (window.jQuery) {
      window.jQuery(document.body).trigger("added_to_cart", [
        data && data.fragments ? data.fragments : null,
        data && data.cart_hash ? data.cart_hash : "",
        sourceButton || null,
      ]);
      window.jQuery(document.body).trigger("wc_fragment_refresh");
    }

    if (data && data.cart && typeof data.cart === "object") {
      syncWooBlocksCartStore(data.cart);
    } else {
      invalidateWooBlocksCartStore();
    }

    broadcastCartAdded({
      source: "product-slide",
    });
    document.body.dispatchEvent(
      new CustomEvent("noyona_cart_added", {
        bubbles: true,
        detail: {
          source: "product-slide",
          productId: sourceButton
            ? parseInt(sourceButton.getAttribute("data-product_id"), 10) || 0
            : 0,
        },
      })
    );
  }

  function setQueryParam(url, key, value) {
    if (!url || url === "#" || !key) return url;

    try {
      const u = new URL(url, window.location.origin);
      if (value) {
        u.searchParams.set(key, value);
      } else {
        u.searchParams.delete(key);
      }

      if (u.origin === window.location.origin) {
        return u.pathname + u.search + u.hash;
      }
      return u.toString();
    } catch (e) {
      const escapedKey = key.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
      let next = String(url)
        .replace(new RegExp("([?&])" + escapedKey + "=[^&]*"), "$1")
        .replace(/[?&]$/, "");
      if (value) {
        next += (next.indexOf("?") === -1 ? "?" : "&") +
          encodeURIComponent(key) +
          "=" +
          encodeURIComponent(value);
      }
      return next;
    }
  }

  function parseVariationMap(button) {
    if (!button) return null;
    if (Object.prototype.hasOwnProperty.call(button, "_noyonaVariationMap")) {
      return button._noyonaVariationMap;
    }

    const raw = button.getAttribute("data-variation-map") || "";
    if (!raw) {
      button._noyonaVariationMap = null;
      return null;
    }

    try {
      const parsed = JSON.parse(raw);
      if (parsed && typeof parsed === "object") {
        button._noyonaVariationMap = parsed;
        return parsed;
      }
    } catch (e) {
      // Ignore malformed JSON and fall back to null.
    }

    button._noyonaVariationMap = null;
    return null;
  }

  function parseVariationChoiceMap(button) {
    if (!button) return null;
    if (Object.prototype.hasOwnProperty.call(button, "_noyonaVariationChoiceMap")) {
      return button._noyonaVariationChoiceMap;
    }

    const raw = button.getAttribute("data-variation-choice-map") || "";
    if (!raw) {
      button._noyonaVariationChoiceMap = null;
      return null;
    }

    try {
      const parsed = JSON.parse(raw);
      if (parsed && typeof parsed === "object") {
        button._noyonaVariationChoiceMap = parsed;
        return parsed;
      }
    } catch (e) {
      // Ignore malformed JSON and fall back to null.
    }

    button._noyonaVariationChoiceMap = null;
    return null;
  }

  function syncCartButtonSelection(card, attrParam, selectedValue, explicitSelection) {
    const selected = String(selectedValue || "");
    const selectedLower = selected.toLowerCase();

    card.querySelectorAll(".ps-btn-cart[data-product_id]").forEach((button) => {
      const productType = (button.getAttribute("data-product-type") || "").toLowerCase();
      const isVariable = productType === "variable";
      let selectedParam = String(attrParam || "");
      let selectedPayloadValue = selected;

      if (!isVariable) {
        if (selectedParam) {
          button.setAttribute("data-selected-attribute-param", selectedParam);
        }
        button.setAttribute("data-selected-attribute-value", selectedPayloadValue);
        button.removeAttribute("data-variation_id");
        button.classList.remove("is-disabled");
        button.setAttribute("aria-disabled", "false");
        return;
      }

      if (
        explicitSelection &&
        typeof explicitSelection === "object" &&
        parseInt(explicitSelection.variationId, 10) > 0
      ) {
        const nextParam = String(explicitSelection.attributeParam || selectedParam || "");
        const nextValue = String(explicitSelection.attributeValue || selectedPayloadValue || "");
        const nextVariationId = parseInt(explicitSelection.variationId, 10) || 0;

        if (nextParam) {
          button.setAttribute("data-selected-attribute-param", nextParam);
        }
        button.setAttribute("data-selected-attribute-value", nextValue);
        button.setAttribute("data-variation_id", String(nextVariationId));
        button.classList.remove("is-disabled");
        button.setAttribute("aria-disabled", "false");
        return;
      }

      const choiceMap = parseVariationChoiceMap(button);
      let chosenChoice = null;
      if (choiceMap && typeof choiceMap === "object") {
        if (selected && Object.prototype.hasOwnProperty.call(choiceMap, selected)) {
          chosenChoice = choiceMap[selected];
        } else if (selectedLower) {
          Object.keys(choiceMap).some((key) => {
            if (String(key).toLowerCase() !== selectedLower) {
              return false;
            }
            chosenChoice = choiceMap[key];
            return true;
          });
        }
      }

      let variationId = 0;
      if (chosenChoice && typeof chosenChoice === "object") {
        if (chosenChoice.attributeParam) {
          selectedParam = String(chosenChoice.attributeParam);
        }
        if (chosenChoice.attributeValue) {
          selectedPayloadValue = String(chosenChoice.attributeValue);
        }
        variationId = parseInt(chosenChoice.variationId, 10) || 0;
      } else {
        const variationMap = parseVariationMap(button);
        if (variationMap && typeof variationMap === "object") {
          if (selected && Object.prototype.hasOwnProperty.call(variationMap, selected)) {
            variationId = parseInt(variationMap[selected], 10) || 0;
          } else if (selectedLower) {
            Object.keys(variationMap).some((key) => {
              if (String(key).toLowerCase() !== selectedLower) {
                return false;
              }
              variationId = parseInt(variationMap[key], 10) || 0;
              return true;
            });
          }
        }
      }

      if (selectedParam) {
        button.setAttribute("data-selected-attribute-param", selectedParam);
      }
      button.setAttribute("data-selected-attribute-value", selectedPayloadValue);

      if (variationId > 0) {
        button.setAttribute("data-variation_id", String(variationId));
        button.classList.remove("is-disabled");
        button.setAttribute("aria-disabled", "false");
      } else {
        button.removeAttribute("data-variation_id");
        button.classList.add("is-disabled");
        button.setAttribute("aria-disabled", "true");
      }
    });
  }

  function updateCardActionUrls(card, selectedValue, explicitSelection) {
    const attrParam = card.getAttribute("data-attribute-param") || "";
    syncCartButtonSelection(card, attrParam, selectedValue, explicitSelection);
    if (!attrParam) return;

    card.querySelectorAll(".ps-btn-primary, .ps-btn-cart").forEach((el) => {
      const ownAttrParam = el.getAttribute("data-attribute-param") || attrParam;
      if (!ownAttrParam) return;

      const baseUrl =
        el.getAttribute("data-base-url") ||
        el.getAttribute("data-base-cart-url") ||
        el.getAttribute("href") ||
        el.getAttribute("data-cart-url") ||
        "";

      if (!baseUrl || baseUrl === "#") return;

      const nextUrl = setQueryParam(baseUrl, ownAttrParam, selectedValue || "");
      if (el.tagName === "A") {
        el.setAttribute("href", nextUrl);
      } else if (el.classList.contains("ps-btn-cart")) {
        el.setAttribute("data-cart-url", nextUrl);
      }
    });
  }

  function initShadeSwatches(root) {
    root.querySelectorAll(".product-slide__card").forEach((card) => {
      const swatches = Array.from(
        card.querySelectorAll(".ps-swatch--option[data-swatch-value]")
      );
      if (!swatches.length) return;

      function selectSwatch(target) {
        const selectedValue = target.getAttribute("data-swatch-value") || "";
        const selectedMeta = {
          attributeParam:
            target.getAttribute("data-cart-attribute-param") ||
            target.getAttribute("data-attribute-param") ||
            "",
          attributeValue:
            target.getAttribute("data-cart-attribute-value") || selectedValue,
          variationId:
            parseInt(target.getAttribute("data-variation-id"), 10) || 0,
        };
        swatches.forEach((swatch) => {
          const on = swatch === target;
          swatch.classList.toggle("is-selected", on);
          swatch.setAttribute("aria-selected", on ? "true" : "false");
        });
        updateCardActionUrls(card, selectedValue, selectedMeta);
      }

      swatches.forEach((swatch) => {
        swatch.addEventListener("click", () => selectSwatch(swatch));
      });

      const initial =
        swatches.find((swatch) => swatch.classList.contains("is-selected")) ||
        swatches[0];
      if (initial) {
        selectSwatch(initial);
      }
    });
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
    initShadeSwatches(root);

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
      const productType = (cartBtn.getAttribute("data-product-type") || "").toLowerCase();
      const selectedAttrParam =
        cartBtn.getAttribute("data-selected-attribute-param") ||
        cartBtn.getAttribute("data-attribute-param") ||
        "";
      const selectedAttrValue =
        cartBtn.getAttribute("data-selected-attribute-value") || "";
      const variationId = parseInt(cartBtn.getAttribute("data-variation_id"), 10) || 0;
      const requestProductId =
        productType === "variable" && variationId > 0 ? variationId : productId;

      if (!endpoint || !requestProductId) {
        if (href) window.location.href = href;
        return;
      }
      if (productType === "variable" && (!selectedAttrParam || !selectedAttrValue)) {
        cartBtn.classList.add("is-error");
        return;
      }
      if (productType === "variable" && cartBtn.getAttribute("aria-disabled") === "true") {
        cartBtn.classList.add("is-error");
        return;
      }
      if (productType === "variable" && variationId < 1) {
        cartBtn.classList.add("is-error");
        return;
      }

      cartBtn.classList.remove("is-error");
      cartBtn.classList.add("loading");

      const payload = new URLSearchParams({
        product_id: String(requestProductId),
        quantity: String(quantity),
      });

      try {
        const hrefUrl = new URL(href, window.location.origin);
        hrefUrl.searchParams.forEach((v, k) => {
          if (k === "add-to-cart") return;
          if (!payload.has(k)) {
            payload.append(k, v);
          }
        });
      } catch (e) {
        // Keep base payload only.
      }

      if (selectedAttrParam && selectedAttrValue && !payload.has(selectedAttrParam)) {
        payload.append(selectedAttrParam, selectedAttrValue);
      }
      if (variationId > 0 && !payload.has("variation_id")) {
        payload.append("variation_id", String(variationId));
      }
      fetch(endpoint, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: payload.toString(),
      })
        .then(function (response) {
          return response.text();
        })
        .then(function (raw) {
          let data = null;
          if (raw) {
            try {
              data = JSON.parse(raw);
            } catch (e) {
              data = null;
            }
          }

          if (data && data.error && data.product_url) {
            cartBtn.classList.remove("loading");
            cartBtn.classList.add("is-error");
            return;
          }

          cartBtn.classList.remove("loading");
          cartBtn.classList.add("added");

          // Keep classic fragments + Woo Blocks mini-cart stores in sync.
          notifyCartUpdated(data || {}, cartBtn);
        })
        .catch(function () {
          cartBtn.classList.remove("loading");
          cartBtn.classList.add("is-error");
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
