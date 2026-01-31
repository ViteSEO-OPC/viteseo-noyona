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

      html += `<div class="nsl-store-item" data-store-id="${escHtml(store.id)}" data-store-post-id="${escHtml(store.post_id || "")}">`;

      // Header: Title + Bookmark Icon
      html += `<div class="nsl-store-header">`;
      html += `<h3 class="nsl-store-name">${escHtml(store.title || "")}</h3>`;
      if (allowFavorites) {
        html += `<button class="nsl-card-bookmark">
                   <svg width="14" height="18" viewBox="0 0 14 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                     <path d="M1 1H13V17L7 13L1 17V1Z" stroke="#333" stroke-width="1.5" fill="none"/>
                   </svg>
                 </button>`;
      }
      html += `</div>`;

      // Content/Address
      if (store.content) {
        html += `<div class="nsl-store-address">${store.content}</div>`;
      }

      /* 
         Products removed from sidebar list as per user request. 
         They remain in the Map Popup (buildPopup function).
      */


      // Footer: Get Directions
      html += `<a href="${directionsUrl}" target="_blank" class="nsl-get-directions">Get directions ></a>`;

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

    // Render Sidebar List
    renderStoreList(stores, wrapper); // RENDER THE LIST
    bindFavoriteButtons();
    applyFavoritesFilter(wrapper, favorites, allowFavorites);

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
        item.addEventListener("click", () => {
          const id = item.getAttribute("data-store-id");
          const marker = markerById[String(id)];
          if (!marker) return;

          const ll = marker.getLatLng();
          map.setView([ll.lat, ll.lng], Math.max(map.getZoom(), 14), { animate: true });
          marker.openPopup();
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
              L.circleMarker([userLat, userLng], { radius: 7 }).addTo(map).bindPopup("You are here").openPopup();
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
