(function () {
  const CART_STORE_KEYS = ["wc/store/cart", "wc/store/cart-data", "wc/store"];

  function getCartCount(cart) {
    if (!cart || typeof cart !== "object") return 0;
    if (Number.isFinite(Number(cart.items_count))) {
      return Math.max(0, Number(cart.items_count) || 0);
    }
    if (Array.isArray(cart.items)) {
      return cart.items.reduce((total, item) => {
        const qty = item && Number.isFinite(Number(item.quantity))
          ? Number(item.quantity)
          : 0;
        return total + Math.max(0, qty);
      }, 0);
    }
    return 0;
  }

  function syncHeaderCartBadge(count) {
    const safeCount = Math.max(0, parseInt(count, 10) || 0);
    document
      .querySelectorAll(
        ".wc-block-mini-cart__badge, .wc-block-mini-cart__button-badge"
      )
      .forEach((badge) => {
        badge.textContent = String(safeCount);
        badge.classList.toggle("is-hidden", safeCount < 1);
        badge.setAttribute("aria-hidden", safeCount < 1 ? "true" : "false");
      });
  }

  function fetchStoreCartData() {
    const endpoint = "/wp-json/wc/store/cart?_t=" + Date.now();
    return fetch(endpoint, {
      credentials: "same-origin",
      headers: { Accept: "application/json" },
      cache: "no-store",
    })
      .then((response) => (response.ok ? response.json() : null))
      .catch(() => {});
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
          // continue with invalidation
        }
      }
      if (typeof dispatch.receiveCartData === "function") {
        try {
          dispatch.receiveCartData(cart);
        } catch (e) {
          // continue with invalidation
        }
      }
      if (typeof dispatch.receiveCartContents === "function") {
        try {
          dispatch.receiveCartContents(cart);
        } catch (e) {
          // continue with invalidation
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
        try {
          dispatch.invalidateResolution("getCartTotals", []);
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

  function refreshCartCount() {
    return fetchStoreCartData().then((cart) => {
      if (!cart) return null;
      const count = getCartCount(cart);
      syncHeaderCartBadge(count);
      syncWooBlocksCartStore(cart);
      return cart;
    });
  }

  function refreshCartCountSeries() {
    refreshCartCount();
    setTimeout(() => refreshCartCount(), 350);
    setTimeout(() => refreshCartCount(), 900);
    setTimeout(() => refreshCartCount(), 1700);
    setTimeout(() => refreshCartCount(), 2600);
  }

  function broadcastCartAdded() {
    const evt = new CustomEvent("wc-blocks_added_to_cart", {
      bubbles: true,
      detail: { preserveCartData: false },
    });

    document.body.dispatchEvent(evt);
    document.dispatchEvent(
      new CustomEvent("wc-blocks_added_to_cart", {
        bubbles: true,
        detail: { preserveCartData: false },
      })
    );
    window.dispatchEvent(
      new CustomEvent("wc-blocks_added_to_cart", {
        detail: { preserveCartData: false },
      })
    );
  }

  function getStoreApiContext() {
    return fetch("/wp-json/wc/store/v1/cart?_t=" + Date.now(), {
      method: "GET",
      credentials: "same-origin",
      headers: { Accept: "application/json" },
      cache: "no-store",
    })
      .then((response) => {
        if (!response.ok) return null;
        const nonce = response.headers.get("Nonce") || "";
        const cartToken = response.headers.get("Cart-Token") || "";
        return response
          .json()
          .then((cart) => ({
            nonce: String(nonce || ""),
            cartToken: String(cartToken || ""),
            cart: cart && typeof cart === "object" ? cart : null,
          }))
          .catch(() => ({
            nonce: String(nonce || ""),
            cartToken: String(cartToken || ""),
            cart: null,
          }));
      })
      .catch(() => null);
  }

  function addToCartViaStoreApi(productId, quantity) {
    return getStoreApiContext()
      .then((ctx) => {
        if (!ctx) return null;

        const headers = {
          Accept: "application/json",
          "Content-Type": "application/json",
        };
        if (ctx.nonce) {
          headers.Nonce = ctx.nonce;
        }
        if (ctx.cartToken) {
          headers["Cart-Token"] = ctx.cartToken;
        }

        return fetch("/wp-json/wc/store/v1/cart/add-item", {
          method: "POST",
          credentials: "same-origin",
          headers: headers,
          cache: "no-store",
          body: JSON.stringify({
            id: Number(productId),
            quantity: Number(quantity),
          }),
        }).then((response) => {
          if (!response.ok) return null;
          return response
            .json()
            .then((cart) => (cart && typeof cart === "object" ? cart : null))
            .catch(() => null);
        });
      })
      .then((cart) => cart || null)
      .catch(() => null);
  }

  function addToCartViaWooStoreDispatch(productId, quantity) {
    if (!window.wp || !window.wp.data || typeof window.wp.data.dispatch !== "function") {
      return Promise.resolve(null);
    }

    const stores = ["wc/store/cart", "wc/store/cart-data", "wc/store"];
    for (let i = 0; i < stores.length; i += 1) {
      let dispatch = null;
      try {
        dispatch = window.wp.data.dispatch(stores[i]);
      } catch (e) {
        dispatch = null;
      }
      if (!dispatch) continue;

      const candidates = ["addItemToCart", "addItem", "addToCart"];
      for (let j = 0; j < candidates.length; j += 1) {
        const method = candidates[j];
        if (typeof dispatch[method] !== "function") continue;

        try {
          const result = dispatch[method]({
            id: Number(productId),
            quantity: Number(quantity),
          });
          return Promise.resolve(result)
            .then((cart) => (cart && typeof cart === "object" ? cart : null))
            .catch(() => null);
        } catch (e) {
          // Try next available method/store.
        }
      }
    }

    return Promise.resolve(null);
  }

  function getAddToCartEndpoint() {
    if (window.wc_add_to_cart_params && window.wc_add_to_cart_params.wc_ajax_url) {
      return String(window.wc_add_to_cart_params.wc_ajax_url).replace(
        "%%endpoint%%",
        "add_to_cart"
      );
    }
    return "/?wc-ajax=add_to_cart";
  }

  function handleAjaxAddToCart(button) {
    const endpoint = getAddToCartEndpoint();
    const cartUrl =
      button.getAttribute("data-cart-url") || button.getAttribute("href") || "";
    const productId = parseInt(button.getAttribute("data-product_id"), 10) || 0;
    const quantity = parseInt(button.getAttribute("data-quantity"), 10) || 1;

    if (!endpoint || !productId) {
      return;
    }

    if (button.classList.contains("loading")) return;
    button.classList.add("loading");

    const payload = new URLSearchParams({
      product_id: String(productId),
      quantity: String(quantity),
    });

    try {
      const hrefUrl = new URL(cartUrl, window.location.origin);
      hrefUrl.searchParams.forEach((value, key) => {
        if (key === "add-to-cart") return;
        if (!payload.has(key)) payload.append(key, value);
      });
    } catch (e) {
      // Keep base payload when URL parsing fails.
    }

    addToCartViaWooStoreDispatch(productId, quantity)
      .then((storeCart) => {
        if (storeCart) {
          return storeCart;
        }
        return addToCartViaStoreApi(productId, quantity);
      })
      .then((storeCart) => {
        if (storeCart) {
          button.classList.add("added");
          syncWooBlocksCartStore(storeCart);
          syncHeaderCartBadge(getCartCount(storeCart));
          invalidateWooBlocksCartStore();
          broadcastCartAdded();
          refreshCartCountSeries();
          return { done: true };
        }

        return fetch(endpoint, {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
          },
          body: payload.toString(),
        })
          .then((response) => response.text())
          .then((raw) => {
            let data = null;
            if (raw) {
              try {
                data = JSON.parse(raw);
              } catch (e) {
                data = null;
              }
            }

            if (data && data.error && data.product_url) {
              window.location.href = data.product_url;
              return { done: true };
            }

            button.classList.add("added");

            if (window.jQuery) {
              window.jQuery(document.body).trigger("added_to_cart", [
                data && data.fragments ? data.fragments : null,
                data && data.cart_hash ? data.cart_hash : "",
                button,
              ]);
              window.jQuery(document.body).trigger("wc_fragment_refresh");
            }

            broadcastCartAdded();
            invalidateWooBlocksCartStore();
            refreshCartCountSeries();
            return { done: true };
          });
      })
      .catch(() => null)
      .finally(() => {
        button.classList.remove("loading");
      });

    // Prevent Woo's injected "View cart" link from staying in the card.
    const card = button.closest(".noyona-pdp-related__card");
    if (card) {
      card
        .querySelectorAll(".added_to_cart.wc-forward")
        .forEach((link) => link.remove());
    }
  }

  function onReady() {
    document.addEventListener("click", (event) => {
      const button = event.target.closest(
        ".noyona-pdp-related__cart-btn--ajax[data-product_id]"
      );
      if (!button) return;

      event.preventDefault();
      event.stopImmediatePropagation();
      handleAjaxAddToCart(button);
    }, true);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", onReady);
  } else {
    onReady();
  }
})();
