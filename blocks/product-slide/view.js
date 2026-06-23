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
    // Tablets / iPad portrait (down to ~600px) show 2 cards instead of one
    // oversized card; phones (<600px) keep a single card.
    if (w >= 1600) return Math.min(4, maxCards);
    if (w >= 1260) return Math.min(3, maxCards);
    if (w >= 600) return Math.min(2, maxCards);
    return 1;
  }

  // Phones (<=780px) navigate via native horizontal scroll + snap instead of
  // the JS transform engine; card sizing stays identical (set by updateWidths).
  // This breakpoint matches the scroll-snap rules in style.css. Above 780px
  // (tablet/desktop) the transform carousel is unchanged.
  function isMobileScroll() {
    return window.matchMedia
      ? window.matchMedia("(max-width: 780px)").matches
      : window.innerWidth <= 780;
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

  function parseVariationCombinations(button) {
    if (!button) return [];
    if (Object.prototype.hasOwnProperty.call(button, "_noyonaVariationCombinations")) {
      return button._noyonaVariationCombinations;
    }

    const raw = button.getAttribute("data-variation-combinations") || "";
    if (!raw) {
      button._noyonaVariationCombinations = [];
      return [];
    }

    try {
      const parsed = JSON.parse(raw);
      button._noyonaVariationCombinations = Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      button._noyonaVariationCombinations = [];
    }

    return button._noyonaVariationCombinations;
  }

  function getCardSelectedAttributes(card) {
    const attrs = {};

    card
      .querySelectorAll(".ps-swatch--option.is-selected, .ps-swatch--option[aria-checked='true']")
      .forEach((button) => {
        const param =
          button.getAttribute("data-cart-attribute-param") ||
          button.getAttribute("data-attribute-param") ||
          "";
        const value =
          button.getAttribute("data-cart-attribute-value") ||
          button.getAttribute("data-swatch-value") ||
          "";
        if (param && value) attrs[param] = value;
      });

    card
      .querySelectorAll(".ps-choice--option.is-selected, .ps-choice--option[aria-checked='true']")
      .forEach((button) => {
        const param = button.getAttribute("data-cart-attribute-param") || "";
        const value =
          button.getAttribute("data-cart-attribute-value") ||
          button.getAttribute("data-choice-value") ||
          "";
        if (param && value) attrs[param] = value;
      });

    return attrs;
  }

  function findMatchingVariationFromAttributes(button, selectedAttributes) {
    const combinations = parseVariationCombinations(button);
    const selectedKeys = Object.keys(selectedAttributes || {});
    if (!combinations.length || !selectedKeys.length) return 0;

    for (let i = 0; i < combinations.length; i++) {
      const combination = combinations[i] || {};
      const attrs = combination.attributes || {};
      const attrKeys = Object.keys(attrs);
      if (!attrKeys.length) continue;

      const matches = attrKeys.every((param) => {
        const expected = String(attrs[param] || "");
        const actual = String(selectedAttributes[param] || "");
        return expected && actual && expected === actual;
      });

      if (matches && parseInt(combination.variationId, 10) > 0) {
        return parseInt(combination.variationId, 10) || 0;
      }
    }

    return 0;
  }

  function syncCartButtonSelection(card, attrParam, selectedValue, explicitSelection) {
    const selected = String(selectedValue || "");
    const selectedLower = selected.toLowerCase();
    const selectedAttributes = getCardSelectedAttributes(card);

    card.querySelectorAll(".ps-btn-cart[data-product_id]").forEach((button) => {
      const productType = (button.getAttribute("data-product-type") || "").toLowerCase();
      const isVariable = productType === "variable";
      let selectedParam = String(attrParam || "");
      let selectedPayloadValue = selected;

      if (Object.keys(selectedAttributes).length) {
        button.setAttribute("data-selected-attributes", JSON.stringify(selectedAttributes));
      } else {
        button.removeAttribute("data-selected-attributes");
      }

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

      const combinationVariationId = findMatchingVariationFromAttributes(button, selectedAttributes);
      if (combinationVariationId > 0) {
        const firstParam = Object.keys(selectedAttributes)[0] || selectedParam;
        button.setAttribute("data-selected-attribute-param", firstParam);
        button.setAttribute("data-selected-attribute-value", selectedAttributes[firstParam] || selectedPayloadValue);
        button.setAttribute("data-variation_id", String(combinationVariationId));
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
    const selectedAttributes = getCardSelectedAttributes(card);

    card.querySelectorAll(".ps-btn-primary, .ps-btn-cart").forEach((el) => {
      const baseUrl =
        el.getAttribute("data-base-url") ||
        el.getAttribute("data-base-cart-url") ||
        el.getAttribute("href") ||
        el.getAttribute("data-cart-url") ||
        "";

      if (!baseUrl || baseUrl === "#") return;

      let nextUrl = baseUrl;
      if (Object.keys(selectedAttributes).length) {
        Object.keys(selectedAttributes).forEach((param) => {
          nextUrl = setQueryParam(nextUrl, param, selectedAttributes[param] || "");
        });
        el.setAttribute("data-selected-attributes", JSON.stringify(selectedAttributes));
      } else {
        const ownAttrParam = el.getAttribute("data-attribute-param") || attrParam;
        if (!ownAttrParam) return;
        nextUrl = setQueryParam(nextUrl, ownAttrParam, selectedValue || "");
      }

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
          swatch.setAttribute("aria-checked", on ? "true" : "false");
          swatch.removeAttribute("aria-selected");
        });
        card.dataset.selectedSwatchValue = selectedValue;
        updateCardActionUrls(card, selectedValue, selectedMeta);
      }

      swatches.forEach((swatch) => {
        swatch.addEventListener("click", () => selectSwatch(swatch));
        swatch.addEventListener("keydown", (event) => {
          const currentIndex = swatches.indexOf(swatch);
          let nextIndex = currentIndex;

          if (event.key === "ArrowRight" || event.key === "ArrowDown") {
            nextIndex = (currentIndex + 1) % swatches.length;
          } else if (event.key === "ArrowLeft" || event.key === "ArrowUp") {
            nextIndex = (currentIndex - 1 + swatches.length) % swatches.length;
          } else if (event.key === "Home") {
            nextIndex = 0;
          } else if (event.key === "End") {
            nextIndex = swatches.length - 1;
          } else {
            return;
          }

          event.preventDefault();
          swatches[nextIndex].focus();
          selectSwatch(swatches[nextIndex]);
        });
      });

      const initial =
        swatches.find(
          (swatch) =>
            swatch.classList.contains("is-selected") ||
            swatch.getAttribute("aria-checked") === "true"
        ) ||
        swatches[0];
      if (initial) {
        selectSwatch(initial);
      }
    });
  }

  function initChoicePills(root) {
    root.querySelectorAll(".product-slide__card").forEach((card) => {
      const groups = Array.from(card.querySelectorAll(".ps-choice-group"));
      if (!groups.length) return;

      groups.forEach((group) => {
        const choices = Array.from(group.querySelectorAll(".ps-choice--option[data-choice-value]"));
        if (!choices.length) return;

        function selectChoice(target) {
          choices.forEach((choice) => {
            const on = choice === target;
            choice.classList.toggle("is-selected", on);
            choice.setAttribute("aria-checked", on ? "true" : "false");
          });
          updateCardActionUrls(card, card.dataset.selectedSwatchValue || "");
        }

        choices.forEach((choice) => {
          choice.addEventListener("click", () => selectChoice(choice));
          choice.addEventListener("keydown", (event) => {
            const currentIndex = choices.indexOf(choice);
            let nextIndex = currentIndex;

            if (event.key === "ArrowRight" || event.key === "ArrowDown") {
              nextIndex = (currentIndex + 1) % choices.length;
            } else if (event.key === "ArrowLeft" || event.key === "ArrowUp") {
              nextIndex = (currentIndex - 1 + choices.length) % choices.length;
            } else if (event.key === "Home") {
              nextIndex = 0;
            } else if (event.key === "End") {
              nextIndex = choices.length - 1;
            } else {
              return;
            }

            event.preventDefault();
            choices[nextIndex].focus();
            selectChoice(choices[nextIndex]);
          });
        });

        const initial =
          choices.find(
            (choice) =>
              choice.classList.contains("is-selected") ||
              choice.getAttribute("aria-checked") === "true"
          ) ||
          choices[0];
        if (initial) {
          selectChoice(initial);
        }
      });
    });
  }

  function initCarousel(root) {
    const track = root.querySelector(".product-slide__track");
    const cards = Array.from(root.querySelectorAll(".product-slide__card"));
    const prevBtn = root.querySelector(".ps-prev");
    const nextBtn = root.querySelector(".ps-next");
    const dotsContainer = root.querySelector(".product-slide__dots");
    // Native scroll viewport on phones; the mobile pager reads its scroll
    // position and dot clicks scroll it. (Desktop: overflow:hidden, never scrolls.)
    const scroller = root.querySelector(".product-slide__track-wrap");

    if (!track || cards.length === 0 || !prevBtn || !nextBtn || !dotsContainer)
      return;

    const maxCards = parseInt(root.dataset.cardsToShow, 10) || 3;
    let currentIndex = 0;
    let view = perView(maxCards);
    let maxIndex = Math.max(0, cards.length - view);
    let totalPositions = Math.max(1, maxIndex + 1);
    // Slide pitch as a % of the track. On touch layouts (<=1023px) this is set
    // smaller than 100/view so a partial next/prev card peeks (swipe affordance).
    let slidePct = 100 / view;

    function buildDots() {
      dotsContainer.innerHTML = "";
      for (let i = 0; i < totalPositions; i++) {
        const dot = document.createElement("button");
        dot.className = "ps-dot" + (i === currentIndex ? " active" : "");
        dot.setAttribute("aria-label", "Go to slide " + (i + 1));
        dot.addEventListener("click", () => {
          // Mobile (<=780px): dots are indicators only — users navigate by
          // swipe / native scroll, and the active dot is kept in sync by the
          // scroll listener. Desktop/tablet keep click-to-navigate.
          if (isMobileScroll()) return;
          goTo(i);
        });
        dotsContainer.appendChild(dot);
      }
    }

    function updateWidths() {
      // Runs identically on every width: it computes the original per-breakpoint
      // slot widths (slidePct) and writes them to the cards, so mobile card
      // dimensions are exactly the original. The only mobile difference is in
      // update() below, which skips the transform so native scroll positions the
      // track instead.
      view = perView(maxCards);
      maxIndex = Math.max(0, cards.length - view);
      totalPositions = Math.max(1, maxIndex + 1);
      // Touch layouts (<=1023px) with more cards than fit reveal a partial card
      // peek so it reads as a swipe carousel; desktop keeps full-width slides.
      const peek =
        maxIndex > 0 &&
        window.matchMedia &&
        window.matchMedia("(max-width: 1023px)").matches
          ? 0.2
          : 0;
      slidePct = 100 / (view + peek);
      root
        .querySelector(".product-slide__carousel")
        ?.classList.toggle("product-slide__carousel--has-nav-space", maxIndex > 0);
      track.style.setProperty("--cards-visible", view);
      track.style.justifyContent = maxIndex > 0 ? "flex-start" : "";
      cards.forEach((card) => {
        card.style.flexBasis = slidePct + "%";
        card.style.maxWidth = slidePct + "%";
      });
      if (currentIndex > maxIndex) currentIndex = maxIndex;
      if (currentIndex < 0) currentIndex = 0;
      buildDots();
      update();
    }

    function update() {
      if (isMobileScroll()) {
        // Phones scroll natively; never apply a transform offset here. Keep the
        // pager (built by buildDots with the desktop totalPositions count) in
        // sync with the native scroll position instead.
        track.style.transform = "";
        syncMobileDots();
        return;
      }
      // Center the active slide group in the viewport. On touch layouts the
      // slide is narrower than the viewport (peek), so this inset reveals a
      // partial PREVIOUS card on the left and a partial NEXT card on the right
      // for middle slides, and cleanly centers the first/last card (the outer
      // side simply has no neighbor to peek). On desktop peek = 0, so
      // view * slidePct = 100 and inset = 0 — i.e. this is a no-op there.
      const inset = (100 - view * slidePct) / 2;
      const offset = -(currentIndex * slidePct) + inset;
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

    // ---- Mobile (<=780px) pager ---------------------------------------------
    // Reuses the dots built by buildDots() (count = totalPositions, the desktop
    // pagination logic). These helpers only run on phones; desktop dots keep
    // using goTo()/update() unchanged.
    let mobileDotsRaf = null;

    function setActiveDot(index) {
      Array.from(dotsContainer.children).forEach((dot, dotIndex) => {
        dot.classList.toggle("active", dotIndex === index);
      });
    }

    // The active position = the leading card sitting at the scroll viewport's
    // left edge, clamped to maxIndex (the final slide group). rect math is used
    // so it's correct regardless of the card's offsetParent.
    function mobileActivePosition() {
      if (!scroller) return 0;
      const reference = scroller.getBoundingClientRect().left;
      let bestIndex = 0;
      let bestDistance = Infinity;
      cards.forEach((card, index) => {
        const distance = Math.abs(card.getBoundingClientRect().left - reference);
        if (distance < bestDistance) {
          bestDistance = distance;
          bestIndex = index;
        }
      });
      return Math.min(bestIndex, maxIndex);
    }

    function syncMobileDots() {
      if (!isMobileScroll()) return;
      // Show the pager on phones (CSS hides it <=1023px); hide it when there is
      // only a single position. Inline style keeps this out of the stylesheet.
      dotsContainer.style.display = maxIndex > 0 ? "flex" : "none";
      setActiveDot(mobileActivePosition());
    }

    function onScrollerScroll() {
      if (mobileDotsRaf) return;
      mobileDotsRaf = requestAnimationFrame(() => {
        mobileDotsRaf = null;
        if (isMobileScroll()) setActiveDot(mobileActivePosition());
      });
    }

    prevBtn.addEventListener("click", () => goTo(currentIndex - 1));
    nextBtn.addEventListener("click", () => goTo(currentIndex + 1));

    // Touch swipe: on tablet/mobile the arrows are hidden (CSS <=1023px), so let
    // users swipe the track between slides. Threshold-based and passive, so a tap
    // still fires card/CTA clicks and vertical page scroll is never blocked.
    let swipeStartX = 0;
    let swipeStartY = 0;
    let swipeActive = false;
    track.addEventListener(
      "touchstart",
      function (event) {
        // <=780px: native scroll-snap handles swiping; don't engage the
        // transform-based swipe (it would fight the browser's own scroll).
        if (isMobileScroll()) return;
        if (!event.touches || event.touches.length !== 1) return;
        swipeActive = true;
        swipeStartX = event.touches[0].clientX;
        swipeStartY = event.touches[0].clientY;
      },
      { passive: true }
    );
    track.addEventListener(
      "touchend",
      function (event) {
        if (isMobileScroll()) return;
        if (!swipeActive) return;
        swipeActive = false;
        const touch = event.changedTouches && event.changedTouches[0];
        if (!touch) return;
        const dx = touch.clientX - swipeStartX;
        const dy = touch.clientY - swipeStartY;
        // Only react to a deliberate, mostly-horizontal swipe.
        if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) {
          goTo(currentIndex + (dx < 0 ? 1 : -1));
        }
      },
      { passive: true }
    );

    initShadeSwatches(root);
    initChoicePills(root);

    root.addEventListener("click", function (event) {
      const cartBtn = event.target.closest(
        ".ps-btn-cart.ajax_add_to_cart[data-product_id]"
      );
      if (!cartBtn || !root.contains(cartBtn)) return;

      event.preventDefault();
      event.stopImmediatePropagation();

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
      let selectedAttributes = {};
      const selectedAttributesRaw = cartBtn.getAttribute("data-selected-attributes") || "";
      if (selectedAttributesRaw) {
        try {
          const parsed = JSON.parse(selectedAttributesRaw);
          if (parsed && typeof parsed === "object") {
            selectedAttributes = parsed;
          }
        } catch (e) {
          selectedAttributes = {};
        }
      }
      const variationId = parseInt(cartBtn.getAttribute("data-variation_id"), 10) || 0;
      const requestProductId =
        productType === "variable" && variationId > 0 ? variationId : productId;

      if (!endpoint || !requestProductId) {
        cartBtn.classList.add("is-error");
        return;
      }
      if (
        productType === "variable" &&
        (!Object.keys(selectedAttributes).length && (!selectedAttrParam || !selectedAttrValue))
      ) {
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
      Object.keys(selectedAttributes).forEach((param) => {
        if (param && selectedAttributes[param] && !payload.has(param)) {
          payload.append(param, selectedAttributes[param]);
        }
      });
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
    }, true);

    const onResize = debounce(updateWidths, 150);
    window.addEventListener("resize", onResize);

    // Mobile pager: track the native scroll position to highlight the active
    // dot. On desktop the track-wrap never scrolls, so this never fires.
    if (scroller) {
      scroller.addEventListener("scroll", onScrollerScroll, { passive: true });
    }

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
