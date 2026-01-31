(function () {
  function escHtml(str) {
    return String(str || "").replace(/[&<>"']/g, function (m) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[m];
    });
  }

  function getFavoritesSet(wrapper) {
    const raw = wrapper && wrapper.dataset ? wrapper.dataset.nslFavorites : "";
    if (!raw) return new Set();
    return new Set(
      raw
        .split(",")
        .map((id) => parseInt(id, 10))
        .filter((id) => Number.isFinite(id) && id > 0)
    );
  }

  function getStorePostId(item) {
    if (!item) return 0;
    const raw = item.getAttribute("data-store-post-id");
    const id = parseInt(raw, 10);
    if (Number.isFinite(id) && id > 0) return id;
    const fallback = item.getAttribute("data-store-id") || "";
    const fallbackId = parseInt(fallback.replace(/\D+/g, ""), 10);
    return Number.isFinite(fallbackId) ? fallbackId : 0;
  }

  function updateBookmarkUI(btn, isActive) {
    if (!btn) return;
    btn.classList.toggle("active", isActive);
    const icon = btn.querySelector("svg");
    const paths = btn.querySelectorAll("svg path");
    if (icon) {
      icon.style.fill = isActive ? "currentColor" : "none";
      icon.style.stroke = "currentColor";
    }
    if (paths.length) {
      paths.forEach((path) => {
        path.setAttribute("fill", isActive ? "currentColor" : "none");
        path.setAttribute("stroke", "currentColor");
      });
    }
  }

  function applyFavoritesFilter(wrapper, favorites, allowFavorites) {
    const activeTab = wrapper.querySelector(".nsl-tab.active");
    const showFavorites =
      allowFavorites &&
      activeTab &&
      activeTab.textContent &&
      activeTab.textContent.trim().toLowerCase() === "favorites";

    let anyVisible = false;
    wrapper.querySelectorAll(".nsl-store-item").forEach((item) => {
      if (!showFavorites) {
        item.style.display = "";
        anyVisible = true;
        return;
      }

      const id = getStorePostId(item);
      const visible = favorites.has(id);
      item.style.display = visible ? "" : "none";
      if (visible) anyVisible = true;
    });

    const emptyEl = wrapper.querySelector(".nsl-favorites-empty");
    if (showFavorites) {
      if (!emptyEl) {
        const list = wrapper.querySelector(".nsl-results-list");
        if (list) {
          const msg = document.createElement("div");
          msg.className = "nsl-favorites-empty";
          msg.textContent = "No favorites saved yet.";
          list.appendChild(msg);
        }
      } else {
        emptyEl.style.display = anyVisible ? "none" : "";
      }
    } else if (emptyEl) {
      emptyEl.style.display = "none";
    }
  }

  function ensureTooltip() {
    let tip = document.getElementById("nsl-product-tip");
    if (!tip) {
      tip = document.createElement("div");
      tip.id = "nsl-product-tip";
      tip.className = "nsl-product-tooltip";
      tip.style.display = "none";
      document.body.appendChild(tip);
    }
    return tip;
  }

  function bindTooltipEvents(wrapper) {
    const tip = ensureTooltip();

    function positionTip(x, y) {
      const rect = tip.getBoundingClientRect();
      const width = rect.width || tip.offsetWidth || 0;
      const height = rect.height || tip.offsetHeight || 0;
      const padding = 8;
      let left = x - width - 12;
      let top = y - height / 2;

      if (left < padding) {
        left = x + 12;
      }

      if (left + width + padding > window.innerWidth) {
        left = Math.max(padding, window.innerWidth - width - padding);
      }

      if (top < padding) {
        top = padding;
      }

      if (top + height + padding > window.innerHeight) {
        top = Math.max(padding, window.innerHeight - height - padding);
      }

      tip.style.left = left + "px";
      tip.style.top = top + "px";
    }

    function show(imgUrl, x, y) {
      if (!imgUrl) return;
      tip.innerHTML = `<img src="${imgUrl}" alt="" />`;
      tip.style.display = "block";
      positionTip(x, y);
    }

    function hide() {
      tip.style.display = "none";
    }

    // Use delegation on the wrapper (map or list)
    wrapper.addEventListener("mousemove", (e) => {
      // Move tooltip if visible
      if (tip.style.display === "block") {
        positionTip(e.clientX, e.clientY);
      }

      // Check for hover target
      const item = e.target.closest ? e.target.closest(".nsl-popup__product") : null;
      if (item) {
        const img = item.getAttribute("data-img");
        if (img) {
          // Only show if not already showing (optional optimization, but simple reset is fine)
          // If we just move, show() updates position too.
          show(img, e.clientX, e.clientY);
        } else {
          hide();
        }
      } else {
        // If we moved out of an item but are still in wrapper
        // However, `mouseout` handles the exit better usually.
        // But valid pointer-events: none on tooltip helps.
      }
    });

    wrapper.addEventListener("mouseout", (e) => {
      const item = e.target.closest ? e.target.closest(".nsl-popup__product") : null;
      if (item) {
        hide();
      }
    });

    // Safety: click hides it
    wrapper.addEventListener("click", hide);
  }

  function buildPopup(store) {
    let html = `<div class="nsl-popup">`;

    if (store.image) {
      html += `
          <div class="nsl-popup__store-image">
            <img src="${escHtml(store.image)}" alt="" />
          </div>
        `;
    }

    html += `<div class="nsl-popup__title"><b>${escHtml(store.title || "")}</b></div>`;
    if (store.address) html += `<div class="nsl-popup__line">${escHtml(store.address)}</div>`;
    if (store.phone) html += `<div class="nsl-popup__line">${escHtml(store.phone)}</div>`;
    if (store.hours) html += `<div class="nsl-popup__line">Hours: ${escHtml(store.hours)}</div>`;

    // store.content is HTML from WP (optional)
    if (store.content) {
      html += `<div class="nsl-popup__content">${store.content}</div>`;
    }

    if (store.products && store.products.length) {
      html += `<hr class="nsl-popup__hr">`;
      html += `<div class="nsl-popup__products"><strong>Available Products:</strong>`;
      html += `<ul class="nsl-popup__product-list">`;

      store.products.forEach((prod) => {
        const name = escHtml(prod.name || "");
        const qty = prod.qty !== undefined && prod.qty !== null ? escHtml(prod.qty) : "";
        const img = prod.image ? escHtml(prod.image) : "";

        html += `
            <li class="nsl-popup__product" ${img ? `data-img="${img}"` : ""}>
              <span class="nsl-prod-name">${name}</span>
              <span class="nsl-prod-qty">: ${qty}</span>
            </li>
          `;
      });

      html += `</ul></div>`;
    }

    if (store.updated_at) {
      html += `<div class="nsl-popup__updated">Last updated: ${escHtml(store.updated_at)}</div>`;
    }

    html += `</div>`;
    return html;
  }

  function ensureProductDrawer(wrapper) {
    if (!wrapper) return null;
    let overlay = wrapper.querySelector(".nsl-product-overlay");
    if (!overlay) {
      overlay = document.createElement("div");
      overlay.className = "nsl-product-overlay";
      wrapper.appendChild(overlay);
    }

    let drawer = wrapper.querySelector(".nsl-product-drawer");
    if (!drawer) {
      drawer = document.createElement("aside");
      drawer.className = "nsl-product-drawer";
      drawer.innerHTML = `
        <div class="nsl-product-drawer__header">
          <div class="nsl-product-drawer__title">Catalog</div>
          <button type="button" class="nsl-product-drawer__close" aria-label="Close catalog">
            <span class="nsl-product-drawer__close-icon" aria-hidden="true">&larr;</span>
            <span class="nsl-product-drawer__close-label">Back</span>
          </button>
        </div>
        <div class="nsl-product-drawer__body"></div>
      `;
      wrapper.appendChild(drawer);
    }

    return { overlay, drawer };
  }

  function renderProductDrawer(drawer, store) {
    if (!drawer || !store) return;
    const body = drawer.querySelector(".nsl-product-drawer__body");
    const title = drawer.querySelector(".nsl-product-drawer__title");
    if (!body) return;

    const products = Array.isArray(store.products) ? store.products : [];
    const count = products.length;
    if (title) {
      title.textContent = `Catalog (${count})`;
    }

    let html = '';

    if (count) {
      html += `<ul class="nsl-product-drawer__list">`;
      products.forEach((prod, index) => {
        const name = escHtml(prod.name || "");
        const category = escHtml(prod.category || "");
        const img = prod.image ? escHtml(prod.image) : "";
        const qtyRaw = prod.qty;
        const qtyNum = Number(qtyRaw);
        const hasQty =
          qtyRaw !== undefined && qtyRaw !== null && qtyRaw !== "" && Number.isFinite(qtyNum);
        let statusLabel = "In stock";
        let statusClass = "is-in-stock";
        if (hasQty && qtyNum <= 0) {
          statusLabel = "Out of stock";
          statusClass = "is-out";
        } else if (hasQty && qtyNum <= 3) {
          statusLabel = "Limited stock";
          statusClass = "is-low";
        }
        const qty = hasQty ? `${qtyNum}${qtyNum >= 5 ? "+" : ""} available` : "";

        html += `
          <li class="nsl-product-drawer__item" data-product-index="${index}">
            <span class="nsl-product-drawer__thumb${img ? "" : " nsl-product-drawer__thumb--empty"}">
              ${img ? `<img src="${img}" alt="${name}" loading="lazy">` : ""}
            </span>
            <span class="nsl-product-drawer__meta">
              ${category ? `<span class="nsl-product-drawer__category">${category}</span>` : ""}
              <span class="nsl-product-drawer__name">${name}</span>
              <span class="nsl-product-drawer__status ${statusClass}">${statusLabel}</span>
              ${qty ? `<span class="nsl-product-drawer__qty">${qty}</span>` : ""}
              <button type="button" class="nsl-product-drawer__view" data-product-index="${index}">
                View <span aria-hidden="true">&rarr;</span>
              </button>
            </span>
          </li>
        `;
      });
      html += `</ul>`;
    } else {
      html += `<div class="nsl-product-drawer__empty">No products listed for this store.</div>`;
    }

    body.innerHTML = `
      <div class="nsl-product-drawer__view nsl-product-drawer__view--list">${html}</div>
      <div class="nsl-product-drawer__view nsl-product-drawer__view--detail"></div>
    `;

    drawer.classList.remove("is-detail");
    drawer._nslStore = store;
  }

  function renderProductDetail(drawer, store, product) {
    if (!drawer || !store || !product) return;
    const detail = drawer.querySelector(".nsl-product-drawer__view--detail");
    const body = drawer.querySelector(".nsl-product-drawer__body");
    if (!detail) return;

    const name = escHtml(product.name || "");
    const category = escHtml(product.category || "");
    const img = product.image ? escHtml(product.image) : "";
    const desc = product.description ? escHtml(product.description) : "";
    const qtyRaw = product.qty;
    const qtyNum = Number(qtyRaw);
    const hasQty =
      qtyRaw !== undefined && qtyRaw !== null && qtyRaw !== "" && Number.isFinite(qtyNum);
    const unitLabel = qtyNum === 1 ? "unit" : "units";
    const stockText = hasQty ? `Stock available: ${qtyNum} ${unitLabel}` : "";

    const storeName = escHtml(store.title || "");
    const storeContent = store.content || "";
    const hoursText = store.hours ? escHtml(store.hours) : "";
    const statusLabel = store.status_label || (store.is_open ? "Open Now" : "Close Now");
    const statusText = statusLabel ? escHtml(statusLabel) : "";

    const lat = store.lat;
    const lng = store.lng;
    const directionsUrl =
      lat && lng
        ? `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(`${lat},${lng}`)}`
        : "";
    const phone = store.phone ? escHtml(store.phone) : "";

    const actions = [];
    if (phone) {
      actions.push(`<a class="nsl-product-detail__call" href="tel:${phone}">Call Store</a>`);
    }
    if (directionsUrl) {
      actions.push(
        `<a class="nsl-product-detail__directions" href="${directionsUrl}" target="_blank" rel="noopener">Get Directions</a>`
      );
    }

    const actionsMarkup = actions.length
      ? `<div class="nsl-product-detail__actions${actions.length === 1 ? " nsl-product-detail__actions--single" : ""}">
          ${actions.join("")}
        </div>`
      : "";

    detail.innerHTML = `
      <div class="nsl-product-detail">
        ${
          img
            ? `<div class="nsl-product-detail__hero">
                <img src="${img}" alt="${name}" loading="lazy">
                <div class="nsl-product-detail__dots" aria-hidden="true">
                  <span class="nsl-product-detail__dot is-active"></span>
                  <span class="nsl-product-detail__dot"></span>
                  <span class="nsl-product-detail__dot"></span>
                </div>
              </div>`
            : ""
        }
        <div class="nsl-product-detail__body">
          ${category ? `<div class="nsl-product-detail__category">${category}</div>` : ""}
          <h3 class="nsl-product-detail__name">${name}</h3>
          ${desc ? `<p class="nsl-product-detail__desc">${desc}</p>` : ""}
          ${stockText ? `<div class="nsl-product-detail__stock">${stockText}</div>` : ""}
          <div class="nsl-product-detail__section">
            <div class="nsl-product-detail__section-label">Available at</div>
            <div class="nsl-product-detail__section-title">${storeName}</div>
            ${storeContent ? `<div class="nsl-product-detail__section-text">${storeContent}</div>` : ""}
            ${hoursText ? `<div class="nsl-product-detail__section-meta">Open: ${hoursText}</div>` : ""}
            ${!hoursText && statusText ? `<div class="nsl-product-detail__section-meta">${statusText}</div>` : ""}
          </div>
          ${actionsMarkup}
        </div>
      </div>
    `;

    drawer.classList.add("is-detail");
    if (body) body.scrollTop = 0;
  }

  function showProductList(drawer) {
    if (!drawer) return;
    const detail = drawer.querySelector(".nsl-product-drawer__view--detail");
    if (detail) detail.innerHTML = "";
    drawer.classList.remove("is-detail");
  }

  function openProductDrawer(wrapper, store) {
    if (!wrapper || !store) return;
    const parts = ensureProductDrawer(wrapper);
    if (!parts) return;
    const { overlay, drawer } = parts;
    renderProductDrawer(drawer, store);
    drawer.classList.add("is-open");
    overlay.classList.add("is-open");
    wrapper.classList.add("nsl-drawer-open");
  }

  function closeProductDrawer(wrapper) {
    if (!wrapper) return;
    const overlay = wrapper.querySelector(".nsl-product-overlay");
    const drawer = wrapper.querySelector(".nsl-product-drawer");
    if (drawer) drawer.classList.remove("is-open");
    if (overlay) overlay.classList.remove("is-open");
    wrapper.classList.remove("nsl-drawer-open");
  }

  function setSelectedStoreItem(wrapper, item) {
    if (!wrapper || !item) return;
    wrapper.querySelectorAll(".nsl-store-item.is-selected").forEach((el) => {
      if (el !== item) el.classList.remove("is-selected");
    });
    item.classList.add("is-selected");
  }

  function bindMobileCatalogUI(wrapper, storeById) {
    if (!wrapper || !storeById) return;
    const media = window.matchMedia ? window.matchMedia("(max-width: 959px)") : null;
    const parts = ensureProductDrawer(wrapper);
    if (!parts) return;

    const { overlay, drawer } = parts;
    const close = () => closeProductDrawer(wrapper);

    if (overlay && overlay.dataset.nslBound !== "1") {
      overlay.addEventListener("click", close);
      overlay.dataset.nslBound = "1";
    }

    if (drawer) {
      const closeBtn = drawer.querySelector(".nsl-product-drawer__close");
      if (closeBtn && closeBtn.dataset.nslBound !== "1") {
        closeBtn.addEventListener("click", (event) => {
          event.preventDefault();
          event.stopPropagation();
          if (drawer.classList.contains("is-detail")) {
            showProductList(drawer);
            return;
          }
          close();
        });
        closeBtn.dataset.nslBound = "1";
      }
      if (drawer.dataset.nslDetailBound !== "1") {
        drawer.addEventListener("click", (event) => {
          const item = event.target.closest(".nsl-product-drawer__item");
          if (!item) return;
          const index = parseInt(item.getAttribute("data-product-index"), 10);
          if (!Number.isFinite(index)) return;
          const store = drawer._nslStore;
          const products = store && Array.isArray(store.products) ? store.products : [];
          const product = products[index];
          if (!product) return;
          renderProductDetail(drawer, store, product);
        });
        drawer.dataset.nslDetailBound = "1";
      }
    }

    wrapper.querySelectorAll(".nsl-store-item").forEach((item) => {
      item.addEventListener("click", (event) => {
        if (media && !media.matches) return;
        const isCta = event.target.closest(".nsl-store-cta");
        if (isCta) {
          event.preventDefault();
          event.stopPropagation();
        }

        setSelectedStoreItem(wrapper, item);

        if (isCta) {
          const storeId = item.getAttribute("data-store-id");
          const store = storeById[String(storeId)];
          if (store) {
            openProductDrawer(wrapper, store);
          }
        } else {
          closeProductDrawer(wrapper);
        }
      });
    });
  }

  function renderStoreList(stores, wrapper) {
    const container = wrapper.querySelector(".store-list") || wrapper.querySelector(".nsl-results-list");
    if (!container) return;
    const allowFavorites = wrapper && wrapper.dataset && wrapper.dataset.nslLoggedIn === "1";

    if (!stores.length) {
      container.innerHTML = '<div class="nsl-no-results">No stores found.</div>';
      return;
    }

    let html = '';
    stores.forEach(store => {
      // Directions URL: Google Maps link using destination lat/lng
      const directionsUrl = `https://www.google.com/maps/dir/?api=1&destination=${store.lat},${store.lng}`;

      const statusLabel = store.status_label || (store.is_open ? "Open Now" : "Close Now");
      const statusClass = store.status_class || (store.is_open ? "is-open" : "is-closed");
      const distanceText = store.distance_text || "0.5 km away";
      const ratingValue =
        store.rating !== undefined && store.rating !== null && store.rating !== "" ? store.rating : "4.5";
      const ratingText = typeof ratingValue === "number" ? ratingValue.toFixed(1) : String(ratingValue);
      const hoursText = store.hours ? String(store.hours) : "";

      const productCount = Array.isArray(store.products) ? store.products.length : 0;

      html += `<div class="nsl-store-item" data-store-id="${escHtml(store.id)}" data-store-post-id="${escHtml(store.post_id || "")}" data-lat="${escHtml(store.lat || "")}" data-lng="${escHtml(store.lng || "")}">`;

      if (store.image) {
        html += `<div class="nsl-store-image"><img src="${escHtml(store.image)}" alt="${escHtml(store.title || "")}" loading="lazy"></div>`;
      }

      html += `<div class="nsl-store-content">`;
      html += `<div class="nsl-store-status">`;
      html += `<span class="nsl-status-badge ${escHtml(statusClass)}">${escHtml(statusLabel)}</span>`;
      html += `<span class="nsl-meta-sep">&bull;</span>`;
      html += `<span class="nsl-meta-text">${escHtml(distanceText)}</span>`;
      if (allowFavorites) {
        html += `<button class="nsl-card-bookmark">
                   <svg width="14" height="18" viewBox="0 0 14 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                     <path d="M1 1H13V17L7 13L1 17V1Z" stroke="#333" stroke-width="1.5" fill="none"/>
                   </svg>
                 </button>`;
      }
      html += `</div>`;
      // Header: Title + Bookmark Icon
      html += `<div class="nsl-store-header">`;
      html += `<h3 class="nsl-store-name">${escHtml(store.title || "")}</h3>`;
      html += `</div>`;

      // Content/Address
      if (store.content) {
        html += `<div class="nsl-store-address">${store.content}</div>`;
      }

      html += `<div class="nsl-store-meta">`;
      html += `<div class="nsl-store-meta-row nsl-store-meta-row--details">`;
      html += `<span class="nsl-rating-badge"><i class="fa-solid fa-star"></i> ${escHtml(ratingText)}</span>`;
      if (hoursText) {
        html += `<span class="nsl-meta-sep">&bull;</span>`;
        html += `<span class="nsl-meta-text nsl-hours-text">${escHtml(hoursText)}</span>`;
      }
      html += `</div>`;
      html += `</div>`;

      /* 
         Products removed from sidebar list as per user request. 
         They remain in the Map Popup (buildPopup function).
      */


      // Footer: Get Directions
      html += `<a href="${directionsUrl}" target="_blank" class="nsl-get-directions">Get directions ></a>`;
      html += `</div>`;

      html += `
        <button type="button" class="nsl-store-cta" aria-label="View catalog for ${escHtml(store.title || "")}">
          <span class="nsl-store-cta__text">
            <span class="nsl-store-cta__label">View catalog</span>
            <span class="nsl-store-cta__name">${escHtml(store.title || "")}</span>
          </span>
          <span class="nsl-store-cta__actions">
            <span class="nsl-store-cta__count">${escHtml(productCount)}</span>
            <span class="nsl-store-cta__open" aria-hidden="true">
              <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 6h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                <path d="M6 2l4 4-4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
          </span>
        </button>
      `;

      html += `</div>`; // .nsl-store-item
    });

    container.innerHTML = html;
  }

  function initStoreLocator(wrapper) {
    // Tabs + bookmarks (scoped per block)
    const tabs = wrapper.querySelectorAll(".nsl-tab");
    const allowFavorites = wrapper && wrapper.dataset && wrapper.dataset.nslLoggedIn === "1";
    const favorites = getFavoritesSet(wrapper);
    const ajaxUrl = wrapper && wrapper.dataset ? wrapper.dataset.nslAjaxUrl : "";
    const ajaxNonce = wrapper && wrapper.dataset ? wrapper.dataset.nslAjaxNonce : "";
    let stores = [];

    tabs.forEach((tab) => {
      tab.addEventListener("click", function () {
        tabs.forEach((t) => t.classList.remove("active"));
        this.classList.add("active");
        applyFavoritesFilter(wrapper, favorites, allowFavorites);
      });
    });

    function bindFavoriteButtons() {
      if (!allowFavorites) return;

      wrapper.querySelectorAll(".nsl-card-bookmark").forEach((btn) => {
        if (btn.dataset && btn.dataset.nslBound === "1") return;
        if (btn.dataset) btn.dataset.nslBound = "1";

        const item = btn.closest(".nsl-store-item");
        const storeId = getStorePostId(item);
        updateBookmarkUI(btn, favorites.has(storeId));

        btn.addEventListener("click", function (e) {
          e.preventDefault();
          e.stopPropagation();

          if (!storeId || !ajaxUrl || !ajaxNonce) return;
          const isActive = favorites.has(storeId);
          const mode = isActive ? "remove" : "add";

          btn.disabled = true;

          const body = new URLSearchParams();
          body.append("action", "noyona_toggle_favorite");
          body.append("store_id", String(storeId));
          body.append("mode", mode);
          body.append("nonce", ajaxNonce);

          fetch(ajaxUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" },
            body: body.toString(),
            credentials: "same-origin",
          })
            .then((res) => res.json())
            .then((data) => {
              if (!data || !data.success) {
                updateBookmarkUI(btn, isActive);
                return;
              }

              if (mode === "add") {
                favorites.add(storeId);
              } else {
                favorites.delete(storeId);
              }

              if (wrapper && wrapper.dataset) {
                wrapper.dataset.nslFavorites = Array.from(favorites).join(",");
              }

              updateBookmarkUI(btn, favorites.has(storeId));
              applyFavoritesFilter(wrapper, favorites, allowFavorites);
            })
            .catch(() => {
              updateBookmarkUI(btn, isActive);
            })
            .finally(() => {
              btn.disabled = false;
            });
        });
      });
    }

    // Read JSON
    const jsonEl = wrapper.querySelector("#store-locator-data") || wrapper.querySelector('script[type="application/json"]');
    if (jsonEl) {
      try {
        stores = JSON.parse(jsonEl.textContent || "[]");
      } catch (e) {
        console.error("Error parsing stores JSON:", e);
      }
    }

    const storeById = {};
    stores.forEach((store) => {
      storeById[String(store.id)] = store;
    });

    // Render Sidebar List
    renderStoreList(stores, wrapper); // RENDER THE LIST
    bindFavoriteButtons();
    applyFavoritesFilter(wrapper, favorites, allowFavorites);
    bindMobileCatalogUI(wrapper, storeById);

    // Map
    const mapContainer = wrapper.querySelector("#store-map") || wrapper.querySelector(".nsl-map-placeholder");
    if (!mapContainer) return;

    function startMap() {
      if (wrapper.dataset && wrapper.dataset.nslMapReady === "1") return;
      if (typeof L === "undefined") return;
      if (wrapper.dataset) wrapper.dataset.nslMapReady = "1";

      // Default center
      let centerCoords = [14.5547, 121.0244];
      if (stores.length && stores[0].lat && stores[0].lng) {
        const lat0 = parseFloat(stores[0].lat);
        const lng0 = parseFloat(stores[0].lng);
        if (!isNaN(lat0) && !isNaN(lng0)) centerCoords = [lat0, lng0];
      }

      const map = L.map(mapContainer).setView(centerCoords, 12);

      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap contributors",
      }).addTo(map);

      let userLocation = null;
      let userMarker = null;
      let routeLine = null;
      const routeStyle = { color: "#ff6f9b", weight: 4, opacity: 0.85 };

      function clearRoute() {
        if (routeLine) {
          map.removeLayer(routeLine);
          routeLine = null;
        }
      }

      function setUserLocation(lat, lng) {
        userLocation = { lat, lng };
        if (userMarker) {
          userMarker.setLatLng([lat, lng]);
        } else {
          userMarker = L.circleMarker([lat, lng], {
            radius: 7,
            color: "#ff6f9b",
            weight: 2,
            fillColor: "#ff6f9b",
            fillOpacity: 0.85,
          }).addTo(map);
          userMarker.bindPopup("You are here");
        }
        clearRoute();
      }

      function drawRoute(start, end) {
        if (!start || !end) return;

        const url =
          "https://router.project-osrm.org/route/v1/driving/" +
          `${start.lng},${start.lat};${end.lng},${end.lat}` +
          "?overview=full&geometries=geojson";

        fetch(url)
          .then((res) => (res.ok ? res.json() : null))
          .then((data) => {
            if (
              data &&
              data.routes &&
              data.routes[0] &&
              data.routes[0].geometry &&
              Array.isArray(data.routes[0].geometry.coordinates)
            ) {
              const coords = data.routes[0].geometry.coordinates.map((pair) => [pair[1], pair[0]]);
              clearRoute();
              routeLine = L.polyline(coords, routeStyle).addTo(map);
              map.fitBounds(routeLine.getBounds().pad(0.15));
              return;
            }

            clearRoute();
            routeLine = L.polyline(
              [
                [start.lat, start.lng],
                [end.lat, end.lng],
              ],
              { ...routeStyle, dashArray: "6 8" }
            ).addTo(map);
            map.fitBounds(routeLine.getBounds().pad(0.2));
          })
          .catch(() => {
            clearRoute();
            routeLine = L.polyline(
              [
                [start.lat, start.lng],
                [end.lat, end.lng],
              ],
              { ...routeStyle, dashArray: "6 8" }
            ).addTo(map);
            map.fitBounds(routeLine.getBounds().pad(0.2));
          });
      }

      // Bind Tooltip to Map
      bindTooltipEvents(mapContainer);

      // Bind Tooltip to Sidebar List (New!)
      const listContainer = wrapper.querySelector(".store-list") || wrapper.querySelector(".nsl-results-list");
      if (listContainer) {
        bindTooltipEvents(listContainer);
      }

      const markers = [];
      const markerById = {};

      stores.forEach((store) => {
        if (!store.lat || !store.lng) return;
        const lat = parseFloat(store.lat);
        const lng = parseFloat(store.lng);
        if (isNaN(lat) || isNaN(lng)) return;

        const marker = L.marker([lat, lng]).addTo(map);
        marker.bindPopup(buildPopup(store), { maxWidth: 360 });

        markerById[String(store.id)] = marker;
        markers.push(marker);

        marker.on("click", () => {
          const item = wrapper.querySelector(`.nsl-store-item[data-store-id="${String(store.id)}"]`);
          if (item) {
            setSelectedStoreItem(wrapper, item);
          }
        });
      });

      if (markers.length) {
        const group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.1));
      } else {
        L.marker(centerCoords).addTo(map).bindPopup("No stores found.").openPopup();
      }

      // Click store item -> pan + open popup
      // Re-query .nsl-store-item because we just added them to DOM
      wrapper.querySelectorAll(".nsl-store-item").forEach((item) => {
        item.addEventListener("click", (event) => {
          if (event.target.closest(".nsl-store-cta") || event.target.closest(".nsl-card-bookmark")) {
            return;
          }
          const id = item.getAttribute("data-store-id");
          const marker = markerById[String(id)];
          if (!marker) return;

          const ll = marker.getLatLng();
          const currentZoom = map.getZoom();
          const targetZoom = Math.max(12, Math.min(currentZoom - 1, 13));
          map.flyTo([ll.lat, ll.lng], targetZoom, {
            animate: true,
            duration: 1.6,
            easeLinearity: 0.22,
          });
          const popup = marker.getPopup();
          if (popup) {
            const prevAutoPan = popup.options.autoPan;
            popup.options.autoPan = false;
            marker.openPopup();
            popup.options.autoPan = prevAutoPan;
          } else {
            marker.openPopup();
          }

          if (userLocation) {
            drawRoute(userLocation, { lat: ll.lat, lng: ll.lng });
          }
        });
      });

      // Use current location (if exists in markup)
      const locationBtn = wrapper.querySelector(".nsl-use-location");
      if (locationBtn) {
        locationBtn.addEventListener("click", function () {
          if (!navigator.geolocation) {
            alert("Geolocation is not supported by your browser.");
            return;
          }
          navigator.geolocation.getCurrentPosition(
            (pos) => {
              const userLat = pos.coords.latitude;
              const userLng = pos.coords.longitude;
              map.setView([userLat, userLng], 14, { animate: true });
              setUserLocation(userLat, userLng);
              if (userMarker) {
                userMarker.openPopup();
              }
            },
            () => alert("Could not get your location. Please allow location access."),
            { enableHighAccuracy: true, timeout: 8000 }
          );
        });
      }

      setTimeout(() => map.invalidateSize(), 100);
    }

    if (typeof L === "undefined") {
      let attempts = 0;
      const timer = setInterval(() => {
        if (typeof L !== "undefined") {
          clearInterval(timer);
          startMap();
        } else if (attempts >= 50) {
          clearInterval(timer);
        }
        attempts += 1;
      }, 100);
      return;
    }

    startMap();

  }

  function runStoreLocatorInit() {
    document.querySelectorAll(".noyona-store-locator-wrapper").forEach(initStoreLocator);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", runStoreLocatorInit);
  } else {
    runStoreLocatorInit();
  }
})();
