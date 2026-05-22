(function () {
  function getEmptySelectedMarkup(showMessage) {
    if (!showMessage) {
      return '<div class="nsl-v2-selected-empty nsl-v2-selected-empty--blank"></div>';
    }
    return '<div class="nsl-v2-selected-empty">Select a store from suggestions, map markers, or store cards.</div>';
  }

  function escHtml(value) {
    return String(value || "").replace(/[&<>"']/g, function (m) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[m];
    });
  }

  function buildDirectionsUrl(store) {
    return "https://www.google.com/maps/dir/?api=1&destination=" + encodeURIComponent(store.lat + "," + store.lng);
  }

  function renderStars(rating) {
    var r = Math.max(1, Math.min(5, parseInt(rating || 0, 10) || 0));
    var out = "";
    for (var i = 1; i <= 5; i += 1) {
      out += i <= r ? "★" : "☆";
    }
    return out;
  }

  function parseTimeToMinutes(value) {
    var raw = String(value || "").trim();
    var match = raw.match(/^([01]?\d|2[0-3]):([0-5]\d)$/);
    if (!match) return null;
    return parseInt(match[1], 10) * 60 + parseInt(match[2], 10);
  }

  function computeOpenState(store) {
    var open = parseTimeToMinutes(store.open_time);
    var close = parseTimeToMinutes(store.close_time);
    if (open === null || close === null) {
      return { isOpen: false, label: "Closed" };
    }
    var now = new Date();
    var nowMinutes = now.getHours() * 60 + now.getMinutes();
    var isOpen;
    if (open <= close) {
      isOpen = nowMinutes >= open && nowMinutes <= close;
    } else {
      isOpen = nowMinutes >= open || nowMinutes <= close;
    }
    return { isOpen: isOpen, label: isOpen ? "Open" : "Closed" };
  }

  function distanceKm(aLat, aLng, bLat, bLng) {
    var toRad = function (v) {
      return (v * Math.PI) / 180;
    };
    var R = 6371;
    var dLat = toRad(bLat - aLat);
    var dLng = toRad(bLng - aLng);
    var s1 = Math.sin(dLat / 2);
    var s2 = Math.sin(dLng / 2);
    var aa =
      s1 * s1 +
      Math.cos(toRad(aLat)) * Math.cos(toRad(bLat)) * s2 * s2;
    return R * (2 * Math.atan2(Math.sqrt(aa), Math.sqrt(1 - aa)));
  }

  function formatStoreCard(store) {
    return (
      '<article class="nsl-v2-store-card" data-store-id="' +
      escHtml(store.id) +
      '">' +
      // (store.image ? '<div class="nsl-v2-store-card__image"><img src="' + escHtml(store.image) + '" alt="' + escHtml(store.title) + '" loading="lazy"></div>' : "") +
      '<div class="nsl-v2-store-card__body">' +
      '<div class="nsl-v2-store-card__headline">' +
      '<h4 class="nsl-v2-store-card__title">' +
      escHtml(store.title) +
      "</h4>" +
      '<span class="nsl-v2-status ' +
      (store._isOpen ? "is-open" : "is-closed") +
      '">' +
      escHtml(store._statusLabel || "Closed") +
      "</span></div>" +
      // '<p class="nsl-v2-store-card__address">' +
      // escHtml(store.address || "") +
      // "</p>" +
      // '<p class="nsl-v2-store-card__region">' +
      // escHtml(store.island_group) +
      // " / " +
      // escHtml(store.region || "Uncategorized") +
      // "</p>" +
      // '<p class="nsl-v2-store-card__rating">Rating: ' +
      // escHtml(Number(store._rating || 0).toFixed(1)) +
      // "</p>" +
      (store.hours ? '<p class="nsl-v2-store-card__hours">' + escHtml(store.hours) + "</p>" : "") +
      "</div></article>"
    );
  }

  function normalizeFilterValue(value) {
    return String(value || "").trim().toLowerCase();
  }

  function normalizeLocationText(value) {
    return normalizeFilterValue(value)
      .replace(/[&\-_\/,.()]+/g, " ")
      .replace(/\s+/g, " ")
      .trim();
  }

  function normalizeIslandKey(value) {
    var key = normalizeLocationText(value);
    if (key === "luzon" || key === "visayas" || key === "mindanao") {
      return key;
    }
    if (key.indexOf("luzon") !== -1) return "luzon";
    if (key.indexOf("visayas") !== -1) return "visayas";
    if (key.indexOf("mindanao") !== -1) return "mindanao";
    return "";
  }

  function coordinatesInBounds(lat, lng, bounds) {
    return lat >= bounds[0] && lat <= bounds[1] && lng >= bounds[2] && lng <= bounds[3];
  }

  function coordinatesToIslandKey(lat, lng) {
    lat = parseFloat(lat);
    lng = parseFloat(lng);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return "";
    if (lat < 4.0 || lat > 22.5 || lng < 116.0 || lng > 128.5) return "";

    var samarBounds = [
      [10.7, 12.8, 124.0, 126.4],
    ];
    if (samarBounds.some(function (bounds) { return coordinatesInBounds(lat, lng, bounds); })) {
      return "visayas";
    }

    var luzonBounds = [
      [12.0, 21.8, 119.0, 126.8],
      [7.4, 13.3, 116.5, 121.8],
    ];
    if (luzonBounds.some(function (bounds) { return coordinatesInBounds(lat, lng, bounds); })) {
      return "luzon";
    }

    var visayasBounds = [
      [9.0, 12.4, 121.8, 125.35],
      [10.0, 12.8, 125.35, 126.35],
    ];
    if (visayasBounds.some(function (bounds) { return coordinatesInBounds(lat, lng, bounds); })) {
      return "visayas";
    }

    var mindanaoBounds = [
      [4.4, 9.99, 121.5, 127.6],
      [9.9, 10.6, 125.2, 126.7],
    ];
    if (mindanaoBounds.some(function (bounds) { return coordinatesInBounds(lat, lng, bounds); })) {
      return "mindanao";
    }

    return "";
  }

  function regionToIslandKey(value) {
    var region = normalizeLocationText(value);
    if (!region) return "";

    var aliases = {
      luzon: [
        "ncr",
        "national capital region",
        "metro manila",
        "car",
        "cordillera administrative region",
        "region i",
        "region 1",
        "ilocos",
        "ilocos region",
        "region ii",
        "region 2",
        "cagayan valley",
        "region iii",
        "region 3",
        "central luzon",
        "region iv a",
        "region iva",
        "region 4 a",
        "calabarzon",
        "region iv b",
        "region ivb",
        "region 4 b",
        "mimaropa",
        "region v",
        "region 5",
        "bicol",
        "bicol region",
        "luzon other",
      ],
      visayas: [
        "region vi",
        "region 6",
        "western visayas",
        "region vii",
        "region 7",
        "central visayas",
        "region viii",
        "region 8",
        "eastern visayas",
        "visayas other",
      ],
      mindanao: [
        "region ix",
        "region 9",
        "zamboanga peninsula",
        "region x",
        "region 10",
        "northern mindanao",
        "region xi",
        "region 11",
        "davao region",
        "region xii",
        "region 12",
        "soccsksargen",
        "region xiii",
        "region 13",
        "caraga",
        "barmm",
        "bangsamoro autonomous region in muslim mindanao",
        "mindanao other",
      ],
    };

    var haystack = " " + region + " ";
    return Object.keys(aliases).find(function (island) {
      return aliases[island].some(function (alias) {
        alias = normalizeLocationText(alias);
        return region === alias || haystack.indexOf(" " + alias + " ") !== -1;
      });
    }) || "";
  }

  function addressToIslandKey(value) {
    var address = normalizeLocationText(value);
    if (!address) return "";

    var tokens = {
      visayas: ["cebu", "iloilo", "bacolod", "bohol", "leyte", "samar", "eastern samar", "northern samar", "western samar", "calbayog", "catarman", "dolores eastern samar", "dumaguete", "roxas", "aklan", "antique", "capiz", "guimaras", "negros occidental", "negros oriental", "siquijor", "tacloban", "ormoc"],
      mindanao: ["davao", "cagayan de oro", "zamboanga", "butuan", "surigao", "cotabato", "general santos", "iligan", "dipolog", "pagadian", "misamis", "bukidnon", "camiguin", "lanao", "agusan", "sarangani", "south cotabato", "sultan kudarat"],
      luzon: ["manila", "quezon city", "makati", "pasig", "taguig", "pasay", "mandaluyong", "marikina", "caloocan", "muntinlupa", "paranaque", "las pinas", "san juan", "malabon", "navotas", "valenzuela", "pateros", "bulacan", "pampanga", "tarlac", "zambales", "nueva ecija", "aurora", "bataan", "laguna", "cavite", "batangas", "rizal", "quezon province", "ilocos", "la union", "pangasinan", "albay", "camarines", "catanduanes", "masbate", "sorsogon", "palawan", "mindoro", "marinduque", "romblon"],
    };

    return Object.keys(tokens).find(function (island) {
      return tokens[island].some(function (token) {
        return address.indexOf(normalizeLocationText(token)) !== -1;
      });
    }) || "";
  }

  function inferStoreIslandKey(store) {
    return (
      coordinatesToIslandKey(store.lat, store.lng) ||
      normalizeIslandKey(store.island || store.island_group || store.islandGroup) ||
      regionToIslandKey(store.region || store.regionName) ||
      addressToIslandKey(store.address)
    );
  }

  function islandLabel(key) {
    if (key === "luzon") return "Luzon";
    if (key === "visayas") return "Visayas";
    if (key === "mindanao") return "Mindanao";
    return "";
  }

  function normalizeIslandSelection(value) {
    var key = normalizeFilterValue(value);
    if (key === "all") return "all";
    return normalizeIslandKey(key) || "all";
  }

  function formatSelectedDetail(store) {
    if (!store) {
      return getEmptySelectedMarkup(true);
    }
    var phone = store.phone || store.tel || "";
    var galleryHtml = "";
    // Temporarily hidden: store detail gallery/images
    // var gallery = Array.isArray(store.gallery) ? store.gallery : (store.image ? [store.image] : []);
    // if (gallery.length) {
    //   galleryHtml =
    //     '<div class="nsl-v2-detail__gallery">' +
    //     gallery
    //       .slice(0, 5)
    //       .map(function (img, index) {
    //         return (
    //           '<button type="button" class="nsl-v2-detail__thumb' +
    //           (index === 0 ? " is-active" : "") +
    //           '" data-gallery-src="' +
    //           escHtml(img) +
    //           '">' +
    //           '<img src="' +
    //           escHtml(img) +
    //           '" alt="' +
    //           escHtml(store.title) +
    //           '" loading="lazy">' +
    //           "</button>"
    //         );
    //       })
    //       .join("") +
    //     "</div>";
    // }

    var reviews = Array.isArray(store.reviews) ? store.reviews : [];
    var reviewsTabLabel = reviews.length ? "Reviews (" + reviews.length + ")" : "Reviews";
    var totalRating = reviews.reduce(function (sum, review) {
      return sum + (parseInt(review.rating || 0, 10) || 0);
    }, 0);
    var averageRating = reviews.length ? (totalRating / reviews.length).toFixed(1) : "0.0";
    var reviewsHtml = reviews.length
      ? reviews
          .map(function (review) {
            var reviewName = escHtml(review.name || "Anonymous");
            var reviewDate = escHtml(review.date || "");
            var reviewText = escHtml(review.text || "");
            var reviewStars = escHtml(renderStars(review.rating));
            // var sourceLabel = review.source === "manual" ? "Admin" : "Customer";
            return (
              '<article class="nsl-v2-review-item">' +
              '<div class="nsl-v2-review-item__head">' +
              '<strong class="nsl-v2-review-item__name">' +
              reviewName +
              '</strong><span class="nsl-v2-review-item__stars">' +
              reviewStars +
              '</span></div><p class="nsl-v2-review-item__meta">' +
              reviewName +
              (reviewDate ? " - " + reviewDate : "") +
              "</p>" +
              (reviewText ? '<p class="nsl-v2-review-item__text">' + reviewText + "</p>" : "") +
              "</article>"
            );
          })
          .join("")
      : '<p class="nsl-v2-review-empty">No reviews yet.</p>';

    return (
      '<div class="nsl-v2-detail" data-store-id="' +
      escHtml(store.id) +
      '">' +
      '<div class="nsl-v2-detail-tabs">' +
      '<button type="button" class="nsl-v2-detail-tab is-active" data-detail-tab="overview">Overview</button>' +
      '<button type="button" class="nsl-v2-detail-tab" data-detail-tab="reviews">' +
      escHtml(reviewsTabLabel) +
      "</button>" +
      "</div>" +
      '<div class="nsl-v2-detail-pane is-active" data-detail-pane="overview">' +
      // Temporarily hidden: store detail gallery/images
      // (store.image ? '<img class="nsl-v2-detail__image" src="' + escHtml(store.image) + '" alt="' + escHtml(store.title) + '" loading="lazy">' : "") +
      galleryHtml +
      '<div class="nsl-v2-detail__headline">' +
      '<h3 class="nsl-v2-detail__title">' +
      escHtml(store.title) +
      "</h3>" +
      '<span class="nsl-v2-status ' +
      (store._isOpen ? "is-open" : "is-closed") +
      '">' +
      escHtml(store._statusLabel || "Closed") +
      "</span></div>" +
      '<p class="nsl-v2-detail__address is-clamped" data-address-expanded="0">' +
      escHtml(store.address || "") +
      "</p>" +
      '<button type="button" class="nsl-v2-address-toggle">See more</button>' +
      (store.description ? '<div class="nsl-v2-detail__desc">' + store.description + "</div>" : "") +
      '<ul class="nsl-v2-detail__list">' +
      (store.hours ? "<li><strong>Hours:</strong> " + escHtml(store.hours) + "</li>" : "") +
      (phone ? "<li><strong>Phone:</strong> " + escHtml(phone) + "</li>" : "") +
      (store.email ? "<li><strong>Email:</strong> " + escHtml(store.email) + "</li>" : "") +
      "<li><strong>Rating:</strong> " +
      escHtml(Number(store._rating || 0).toFixed(1)) +
      "</li>" +
      '<li><strong>Region:</strong> ' +
      escHtml(store.island_group) +
      " / " +
      escHtml(store.region || "Uncategorized") +
      "</li>" +
      "</ul>" +
      '<div class="nsl-v2-detail__actions">' +
      '<a class="nsl-v2-directions" href="' +
      escHtml(buildDirectionsUrl(store)) +
      '" target="_blank" rel="noopener noreferrer">Open in Google Maps</a>' +
      "</div>" +
      "</div>" +
      '<div class="nsl-v2-detail-pane" data-detail-pane="reviews">' +
      '<div class="nsl-v2-review-summary">' +
      '<div class="nsl-v2-review-summary__score">' +
      escHtml(averageRating) +
      "</div>" +
      '<div class="nsl-v2-review-summary__meta">' +
      '<div class="nsl-v2-review-summary__stars">' +
      escHtml(renderStars(Math.round(parseFloat(averageRating) || 0))) +
      "</div>" +
      '<div class="nsl-v2-review-summary__count">' +
      escHtml(reviews.length) +
      " review(s)</div>" +
      "</div>" +
      "</div>" +
      '<div class="nsl-v2-detail__reviews"><h4>All Reviews</h4>' +
      reviewsHtml +
      (store.allow_public_reviews
        ? '<button type="button" class="nsl-v2-open-review-modal" data-store-id="' +
          escHtml(store.id) +
          '">Add Review</button>'
        : "") +
      "</div>" +
      "</div>"
    );
  }

  function initStoreLocator(wrapper) {
    var jsonEl = wrapper.querySelector('script[type="application/json"]');
    var mapEl = wrapper.querySelector(".nsl-v2-map");
    var mapShell = wrapper.querySelector(".nsl-v2-map-shell");
    var searchInput = wrapper.querySelector(".nsl-v2-search-input");
    var suggestionsEl = wrapper.querySelector(".nsl-v2-suggestions");
    var selectedPanel = wrapper.querySelector(".nsl-v2-selected-panel");

    /**
     * Mobile-only mirror of the selected-store details. The overlay panel
     * floats inside the map on desktop/laptop, but on small viewports the
     * map is too narrow to share with a 390px panel — so we mirror the
     * same `formatSelectedDetail()` markup into a below-map container and
     * let CSS pick which one is visible at the active breakpoint.
     * Container is created once and lives between `.nsl-v2-top` and
     * `.nsl-v2-bottom`. Empty state is hidden via `:not(:empty)` in CSS.
     */
    var mobileSelectedPanel = wrapper.querySelector(".nsl-v2-mobile-selected");
    if (!mobileSelectedPanel) {
      var topSection = wrapper.querySelector(".nsl-v2-top");
      if (topSection && topSection.parentNode) {
        mobileSelectedPanel = document.createElement("div");
        mobileSelectedPanel.className = "nsl-v2-mobile-selected";
        topSection.parentNode.insertBefore(mobileSelectedPanel, topSection.nextSibling);
      }
    }

    var parentFilterList = wrapper.querySelector(".nsl-v2-parent-filter-list");
    var childFilterList = wrapper.querySelector(".nsl-v2-child-filter-list");
    var extraFilterList = wrapper.querySelector(".nsl-v2-extra-filter-list");
    var islandSelect = wrapper.querySelector(".nsl-v2-island-select");
    var regionSelect = wrapper.querySelector(".nsl-v2-region-select");
    var quickFilterSelect = wrapper.querySelector(".nsl-v2-quick-filter-select");
    var storeGrid = wrapper.querySelector(".nsl-v2-store-grid");
    var storeCount = wrapper.querySelector(".nsl-v2-store-count");
    var storePagination = wrapper.querySelector(".nsl-v2-store-pagination");
    var useLocationBtn = wrapper.querySelector(".nsl-v2-use-location");
    var reviewModal = document.getElementById("nsl-v2-review-modal");
    var reviewModalPostId = document.getElementById("nsl-v2-comment-post-id");
    if (!jsonEl || !mapEl || typeof L === "undefined") return;

    var stores = [];
    try {
      stores = JSON.parse(jsonEl.textContent || "[]");
    } catch (e) {
      stores = [];
    }

    stores = stores.map(function (store) {
      var computed = computeOpenState(store);
      store._isOpen = computed.isOpen;
      store._statusLabel = computed.label;
      var ratingNum = parseFloat(store.rating);
      store._rating = Number.isFinite(ratingNum) ? ratingNum : 4.5;
      store._islandKey = inferStoreIslandKey(store);
      store.island = store._islandKey;
      store.island_group = islandLabel(store._islandKey);
      store.region = String(store.region || store.regionName || "").trim() || "Uncategorized";
      store._regionKey = normalizeLocationText(store.region);
      return store;
    });

    var query = "";
    var activeIsland = "all";
    var activeRegion = "all";
    var activeQuickFilter = "all";
    var selectedStoreId = null;
    var currentPage = 1;

    /**
     * Derive the per-page store count from the active grid layout. We read the
     * computed `grid-template-columns` of `.nsl-v2-store-grid` directly so the
     * JS can never drift from the CSS breakpoints in `style.css`. Mapping:
     *   4 columns → 24 stores  (4 × 6 complete rows)
     *   3 columns → 21 stores  (3 × 7 complete rows)
     *   2 columns → 10 stores  (2 × 5 complete rows)
     *   1 column  → 5  stores  (1 × 5 complete rows)
     * Falls back to 24 if the grid hasn't been laid out yet or the value cannot
     * be parsed (e.g. element is `display: none`).
     */
    function computePageSize() {
      if (!storeGrid) {
        return 24;
      }
      var template = "";
      try {
        template = window.getComputedStyle(storeGrid).gridTemplateColumns || "";
      } catch (e) {
        template = "";
      }
      if (!template || template === "none") {
        return 24;
      }
      var columns = template.trim().split(/\s+/).length;
      if (columns >= 4) return 24;
      if (columns === 3) return 21;
      if (columns === 2) return 10;
      if (columns === 1) return 5;
      return 24;
    }

    var pageSize = computePageSize();
    var routeMode = "driving";
    var userLocation = null;
    var userMarker = null;
    var routeLine = null;
    var storeMarkerIcon = L.divIcon({
      className: "nsl-v2-marker-wrap",
      html: '<span class="nsl-v2-marker-pin"><span class="nsl-v2-marker-core"></span></span>',
      iconSize: [28, 40],
      iconAnchor: [14, 40],
      popupAnchor: [0, -36],
    });

    var map = L.map(mapEl, { zoomControl: false }).setView([14.5547, 121.0244], 11);
    L.control.zoom({ position: "bottomright" }).addTo(map);
    // tile.openstreetmap.org is OSM's dev/operations server and is rate-limited /
    // 403'd for production embeds under their tile usage policy. CARTO's Voyager
    // basemap is a free, no-API-key, production-friendly raster source built on
    // the same OSM data. Subdomains a–d + the {r} retina hint match Leaflet's
    // template syntax. Attribution must credit both OSM and CARTO.
    L.tileLayer("https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png", {
      maxZoom: 20,
      subdomains: "abcd",
      attribution:
        '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions" target="_blank" rel="noopener">CARTO</a>',
    }).addTo(map);

    var markers = {};
    var markerLayer = L.layerGroup().addTo(map);

    function invalidateMapSize(delay) {
      window.setTimeout(function () {
        map.invalidateSize({ pan: false });
      }, delay || 0);
    }

    function invalidateMapSizeSeries() {
      invalidateMapSize(0);
      invalidateMapSize(120);
      invalidateMapSize(320);
    }

    invalidateMapSizeSeries();
    window.addEventListener("load", invalidateMapSizeSeries);

    if (window.ResizeObserver && mapShell) {
      var mapResizeObserver = new ResizeObserver(invalidateMapSizeSeries);
      mapResizeObserver.observe(mapShell);
      mapResizeObserver.observe(mapEl);
    }

    function setActiveMarker(storeId) {
      Object.keys(markers).forEach(function (id) {
        var marker = markers[id];
        if (!marker) return;
        var el = marker.getElement ? marker.getElement() : null;
        if (!el) return;
        var pin = el.querySelector(".nsl-v2-marker-pin");
        if (!pin) return;
        pin.classList.toggle("is-active", String(id) === String(storeId));
      });
    }

    stores.forEach(function (store) {
      var marker = L.marker([store.lat, store.lng], { icon: storeMarkerIcon });
      marker.on("click", function () {
        selectStoreById(store.id, true);
      });
      markers[String(store.id)] = marker;
    });

    function getSearchFilteredStores() {
      if (!query) return stores.slice();
      var q = query.toLowerCase();
      return stores.filter(function (store) {
        var haystack = [store.title, store.address, store.region, store.island_group].join(" ").toLowerCase();
        return haystack.indexOf(q) !== -1;
      });
    }

    function getSearchIslandRegionFilteredStores() {
      return getSearchFilteredStores().filter(function (store) {
        if (activeIsland !== "all" && store._islandKey !== activeIsland) return false;
        if (activeRegion !== "all" && store._regionKey !== activeRegion) return false;
        return true;
      });
    }

    function getFullyFilteredStores() {
      var filtered = getSearchIslandRegionFilteredStores().filter(function (store) {
        if (activeQuickFilter === "open" && !store._isOpen) return false;
        if (activeQuickFilter === "top" && store._rating < 4.5) return false;
        if (activeQuickFilter === "near") {
          if (!userLocation) return false;
          if (distanceKm(userLocation.lat, userLocation.lng, store.lat, store.lng) > 12) return false;
        }
        return true;
      });
      if (activeQuickFilter === "top") {
        filtered.sort(function (a, b) {
          return b._rating - a._rating;
        });
      }
      return filtered;
    }

    function getSelectedStore() {
      if (!selectedStoreId) return null;
      return stores.find(function (s) {
        return String(s.id) === String(selectedStoreId);
      }) || null;
    }

    function clearRoute() {
      if (routeLine) {
        map.removeLayer(routeLine);
        routeLine = null;
      }
    }

    function setUserMarker(lat, lng) {
      userLocation = { lat: lat, lng: lng };
      if (userMarker) {
        userMarker.setLatLng([lat, lng]);
      } else {
        userMarker = L.circleMarker([lat, lng], {
          radius: 7,
          color: "#0a84ff",
          weight: 2,
          fillColor: "#0a84ff",
          fillOpacity: 0.85,
        }).addTo(map);
      }
    }

    function drawRouteToStore(store) {
      if (!store || !userLocation) return;
      clearRoute();

      var profile = routeMode === "walking" ? "foot" : "driving";
      var routeUrl =
        "https://router.project-osrm.org/route/v1/" +
        profile +
        "/" +
        encodeURIComponent(userLocation.lng + "," + userLocation.lat) +
        ";" +
        encodeURIComponent(store.lng + "," + store.lat) +
        "?overview=full&geometries=geojson";

      fetch(routeUrl)
        .then(function (res) {
          if (!res.ok) return null;
          return res.json();
        })
        .then(function (data) {
          if (
            data &&
            data.routes &&
            data.routes[0] &&
            data.routes[0].geometry &&
            Array.isArray(data.routes[0].geometry.coordinates)
          ) {
            var coords = data.routes[0].geometry.coordinates.map(function (pair) {
              return [pair[1], pair[0]];
            });
            routeLine = L.polyline(coords, {
              color: routeMode === "walking" ? "#22a06b" : "#0a84ff",
              weight: 5,
              opacity: 0.9,
            }).addTo(map);
            map.fitBounds(routeLine.getBounds().pad(0.15));
            return;
          }

          routeLine = L.polyline(
            [
              [userLocation.lat, userLocation.lng],
              [store.lat, store.lng],
            ],
            { color: "#0a84ff", weight: 4, opacity: 0.65, dashArray: "7 8" }
          ).addTo(map);
          map.fitBounds(routeLine.getBounds().pad(0.2));
        })
        .catch(function () {
          routeLine = L.polyline(
            [
              [userLocation.lat, userLocation.lng],
              [store.lat, store.lng],
            ],
            { color: "#0a84ff", weight: 4, opacity: 0.65, dashArray: "7 8" }
          ).addTo(map);
          map.fitBounds(routeLine.getBounds().pad(0.2));
        });
    }

    function requestUserLocationAndRoute(store) {
      if (!store) return;
      if (!navigator.geolocation) {
        alert("Geolocation is not supported by your browser.");
        return;
      }
      navigator.geolocation.getCurrentPosition(
        function (pos) {
          setUserMarker(pos.coords.latitude, pos.coords.longitude);
          drawRouteToStore(store);
        },
        function () {
          alert("Unable to access location. Please allow location permission in your browser.");
        },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    }

    function zoomToStore(store) {
      if (!store) return;
      map.stop();
      var target = L.latLng(store.lat, store.lng);
      var currentCenter = map.getCenter();
      var distance = map.distance(currentCenter, target);
      var currentZoom = map.getZoom();

      // Nearby stores: pan only to avoid zoom "vibration" effect.
      if (distance < 1500) {
        map.panTo(target, { animate: true, duration: 0.45 });
        if (currentZoom < 14) {
          map.setZoom(14, { animate: true });
        }
        return;
      }

      // Far stores: smooth fly animation.
      map.flyTo(target, Math.max(currentZoom, 14), { duration: 0.95, easeLinearity: 0.22 });
    }

    function renderSuggestions() {
      if (!query) {
        suggestionsEl.hidden = true;
        suggestionsEl.innerHTML = "";
        return;
      }
      var candidates = getSearchIslandRegionFilteredStores().slice(0, 10);
      if (!candidates.length) {
        suggestionsEl.hidden = false;
        suggestionsEl.innerHTML = '<div class="nsl-v2-suggestion-empty">No matching stores found.</div>';
        return;
      }
      suggestionsEl.hidden = false;
      suggestionsEl.innerHTML = candidates
        .map(function (store) {
          return (
            '<button type="button" class="nsl-v2-suggestion-item" data-store-id="' +
            escHtml(store.id) +
            '">' +
            '<span class="nsl-v2-suggestion-title">' +
            escHtml(store.title) +
            "</span>" +
            '<span class="nsl-v2-suggestion-meta">' +
            escHtml(store.address || "") +
            "</span>" +
            "</button>"
          );
        })
        .join("");
    }

    function getFilterTreeBySearch() {
      var bySearch = getSearchFilteredStores();
      var tree = {};
      bySearch.forEach(function (store) {
        var island = store._islandKey;
        if (!island) return;
        var regionKey = store._regionKey || "uncategorized";
        var regionLabel = store.region || "Uncategorized";
        if (!tree[island]) tree[island] = { count: 0, label: islandLabel(island), regions: {} };
        if (!tree[island].regions[regionKey]) {
          tree[island].regions[regionKey] = { count: 0, label: regionLabel };
        }
        tree[island].count += 1;
        tree[island].regions[regionKey].count += 1;
      });
      return { bySearch: bySearch, tree: tree };
    }

    function renderFilters() {
      if (!parentFilterList || !childFilterList) return;
      var data = getFilterTreeBySearch();
      var tree = data.tree;
      var bySearch = data.bySearch;
      var parentOrder = [
        { key: "luzon", label: "Luzon" },
        { key: "visayas", label: "Visayas" },
        { key: "mindanao", label: "Mindanao" },
      ];
      function optionHtml(value, label, selected) {
        return '<option value="' + escHtml(value) + '"' + (selected ? " selected" : "") + ">" + escHtml(label) + "</option>";
      }

      var parentHtml =
        '<button type="button" class="nsl-v2-filter-parent nsl-v2-filter-parent--all' +
        (activeIsland === "all" ? " is-active" : "") +
        '" data-island="all" data-region="all" aria-pressed="' +
        (activeIsland === "all" ? "true" : "false") +
        '">All (' +
        bySearch.length +
        ")</button>";

      parentOrder.forEach(function (island) {
        var count = tree[island.key] ? tree[island.key].count : 0;
        parentHtml +=
          '<button type="button" class="nsl-v2-filter-parent' +
          (activeIsland === island.key ? " is-active" : "") +
          '" data-island="' +
          escHtml(island.key) +
          '" aria-pressed="' +
          (activeIsland === island.key ? "true" : "false") +
          '">' +
          escHtml(island.label) +
          "</button>";
      });
      parentFilterList.innerHTML = parentHtml;

      if (islandSelect) {
        islandSelect.innerHTML =
          optionHtml("all", "All islands (" + bySearch.length + ")", activeIsland === "all") +
          parentOrder
            .map(function (island) {
              var count = tree[island.key] ? tree[island.key].count : 0;
              return optionHtml(island.key, island.label + " (" + count + ")", activeIsland === island.key);
            })
            .join("");
      }

      if (activeIsland === "all") {
        childFilterList.innerHTML = '<div class="nsl-v2-filter-hint">Select Luzon, Visayas, or Mindanao to see child regions.</div>';
        if (regionSelect) {
          regionSelect.innerHTML = optionHtml("all", "All regions", true);
          regionSelect.disabled = true;
        }
        return;
      }

      var islandTree = tree[activeIsland];
      if (!islandTree || !islandTree.regions || !Object.keys(islandTree.regions).length) {
        childFilterList.innerHTML = '<div class="nsl-v2-filter-hint">No regions found for this island group.</div>';
        if (regionSelect) {
          regionSelect.innerHTML = optionHtml("all", "No regions found", true);
          regionSelect.disabled = true;
        }
        return;
      }

      var childHtml =
        '<button type="button" class="nsl-v2-filter-child' +
        (activeRegion === "all" ? " is-active" : "") +
        '" data-island="' +
        escHtml(activeIsland) +
        '" data-region="all" aria-pressed="' +
        (activeRegion === "all" ? "true" : "false") +
        '">All ' +
        escHtml(islandTree.label || islandLabel(activeIsland)) +
        "</button>";

      Object.keys(islandTree.regions)
        .sort()
        .forEach(function (regionKey) {
          var region = islandTree.regions[regionKey];
          childHtml +=
            '<button type="button" class="nsl-v2-filter-child' +
            (activeRegion === regionKey ? " is-active" : "") +
            '" data-island="' +
            escHtml(activeIsland) +
            '" data-region="' +
            escHtml(regionKey) +
            '" aria-pressed="' +
            (activeRegion === regionKey ? "true" : "false") +
            '">(' +
            region.count +
            ") " +
            escHtml(region.label) +
            "</button>";
        });
      childFilterList.innerHTML = childHtml;

      if (regionSelect) {
        regionSelect.disabled = false;
        regionSelect.innerHTML =
          optionHtml("all", "All " + (islandTree.label || islandLabel(activeIsland)) + " regions", activeRegion === "all") +
          Object.keys(islandTree.regions)
            .sort()
            .map(function (regionKey) {
              var region = islandTree.regions[regionKey];
              return optionHtml(regionKey, region.label + " (" + region.count + ")", activeRegion === regionKey);
            })
            .join("");
      }
    }

    function renderExtraFilters() {
      if (!extraFilterList) return;
      var options = [
        { key: "all", label: "All Stores" },
        { key: "open", label: "Open Now" },
        // { key: "near", label: userLocation ? "Near Me (12km)" : "Near Me" },
        // { key: "top", label: "Top Rated (4.5+)" },
      ];
      extraFilterList.innerHTML = options
        .map(function (item) {
          return (
            '<button type="button" class="nsl-v2-filter-extra' +
            (activeQuickFilter === item.key ? " is-active" : "") +
            '" data-extra-filter="' +
            escHtml(item.key) +
            '" aria-pressed="' +
            (activeQuickFilter === item.key ? "true" : "false") +
            '">' +
            escHtml(item.label) +
            "</button>"
          );
        })
        .join("");

      if (quickFilterSelect) {
        quickFilterSelect.innerHTML = options
          .map(function (item) {
            return (
              '<option value="' +
              escHtml(item.key) +
              '"' +
              (activeQuickFilter === item.key ? " selected" : "") +
              ">" +
              escHtml(item.label) +
              "</option>"
            );
          })
          .join("");
      }
    }

    function renderPagination(totalItems, totalPages) {
      if (!storePagination) return;
      if (totalItems <= pageSize || totalPages <= 1) {
        storePagination.innerHTML = "";
        return;
      }

      var html = "";
      html +=
        '<button type="button" class="nsl-v2-store-pagination__btn" data-page-nav="prev"' +
        (currentPage <= 1 ? " disabled" : "") +
        '>Prev</button>';

      var windowStart = Math.max(1, currentPage - 2);
      var windowEnd = Math.min(totalPages, windowStart + 4);
      windowStart = Math.max(1, windowEnd - 4);
      for (var pageNo = windowStart; pageNo <= windowEnd; pageNo += 1) {
        html +=
          '<button type="button" class="nsl-v2-store-pagination__btn' +
          (pageNo === currentPage ? " is-active" : "") +
          '" data-page="' +
          pageNo +
          '"' +
          (pageNo === currentPage ? ' aria-current="page"' : "") +
          '">' +
          pageNo +
          "</button>";
      }

      html +=
        '<button type="button" class="nsl-v2-store-pagination__btn" data-page-nav="next"' +
        (currentPage >= totalPages ? " disabled" : "") +
        '>Next</button>';

      storePagination.innerHTML = html;
    }

    function renderStoresAndMap() {
      map.invalidateSize({ pan: false });
      var filtered = getFullyFilteredStores();
      var totalItems = filtered.length;
      var totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
      if (currentPage > totalPages) {
        currentPage = totalPages;
      }
      if (currentPage < 1) {
        currentPage = 1;
      }
      var startIndex = (currentPage - 1) * pageSize;
      var endIndex = startIndex + pageSize;
      var paged = filtered.slice(startIndex, endIndex);

      storeCount.textContent = totalItems + " store(s) found";
      if (totalItems > 0) {
        storeCount.textContent += " - Page " + currentPage + " of " + totalPages;
      }

      storeGrid.innerHTML = paged.length ? paged.map(formatStoreCard).join("") : '<div class="nsl-v2-empty">No stores match your filters.</div>';
      renderPagination(totalItems, totalPages);

      markerLayer.clearLayers();
      filtered.forEach(function (store) {
        var marker = markers[String(store.id)];
        if (marker) markerLayer.addLayer(marker);
      });

      if (filtered.length) {
        var group = L.featureGroup(
          filtered
            .map(function (store) {
              return markers[String(store.id)];
            })
            .filter(Boolean)
        );
        if (group.getLayers().length) {
          map.fitBounds(group.getBounds().pad(0.15));
        }
      }

      var selected = getSelectedStore();
      if (!selected || filtered.every(function (s) { return String(s.id) !== String(selected.id); })) {
        selectedStoreId = null;
        setSelectedPanelHtml(query ? getEmptySelectedMarkup(false) : getEmptySelectedMarkup(true));
        selectedPanel.classList.toggle("is-collapsed", !!query);
        setActiveMarker(null);
        clearRoute();
      } else {
        selectedPanel.classList.remove("is-collapsed");
        setSelectedPanelHtml(formatSelectedDetail(selected));
        setActiveMarker(selected.id);
        syncAddressToggleVisibility();
      }
      invalidateMapSizeSeries();
    }

    /**
     * Update both the in-map overlay panel and the below-map mobile mirror
     * with the same selected-store HTML. The mobile container is left empty
     * when the markup is the placeholder ("Select a store...") so the
     * `:not(:empty)` CSS rule hides it until a real store is chosen.
     */
    function setSelectedPanelHtml(html) {
      selectedPanel.innerHTML = html;
      if (!mobileSelectedPanel) {
        return;
      }
      if (typeof html === "string" && html.indexOf("nsl-v2-detail") !== -1) {
        mobileSelectedPanel.innerHTML = html;
      } else {
        mobileSelectedPanel.innerHTML = "";
      }
    }

    function selectStoreById(id, shouldFly) {
      var store = stores.find(function (s) {
        return String(s.id) === String(id);
      });
      if (!store) return;
      selectedStoreId = String(store.id);
      setSelectedPanelHtml(formatSelectedDetail(store));
      setActiveMarker(store.id);
      syncAddressToggleVisibility();
      invalidateMapSizeSeries();
      if (shouldFly && !userLocation) {
        zoomToStore(store);
      }
      if (userLocation) {
        drawRouteToStore(store);
      }
    }

    function closeReviewModal() {
      if (!reviewModal) return;
      reviewModal.hidden = true;
      document.body.classList.remove("nsl-v2-review-modal-open");
    }

    function syncAddressToggleVisibility() {
      // Run independently against each panel; the hidden one (display:none)
      // reports zero scroll/clientHeight, so its toggle is hidden, which is
      // harmless. Only the visible panel produces meaningful measurements.
      [selectedPanel, mobileSelectedPanel].forEach(function (panel) {
        if (!panel) return;
        var addressEl = panel.querySelector(".nsl-v2-detail__address");
        var toggleEl = panel.querySelector(".nsl-v2-address-toggle");
        if (!addressEl || !toggleEl) return;
        var shouldShow = addressEl.scrollHeight > addressEl.clientHeight + 2;
        toggleEl.hidden = !shouldShow;
      });
    }

    renderFilters();
    renderExtraFilters();
    renderStoresAndMap();

    /**
     * Recompute the per-page count when the viewport crosses a breakpoint and
     * the active grid column count changes. Debounced so a window-drag does
     * not thrash the layout. Only re-renders when pageSize actually changes;
     * resize events within the same breakpoint short-circuit. On a real
     * breakpoint change we reset to page 1 — pagination state is page-index
     * only (no URL/state persistence), so attempting to preserve the first
     * visible store would be misleading.
     */
    var resizeDebounceTimer = null;
    function handleViewportResize() {
      var nextPageSize = computePageSize();
      if (nextPageSize === pageSize) {
        invalidateMapSizeSeries();
        syncAddressToggleVisibility();
        return;
      }
      pageSize = nextPageSize;
      currentPage = 1;
      renderStoresAndMap();
    }
    window.addEventListener("resize", function () {
      if (resizeDebounceTimer) {
        clearTimeout(resizeDebounceTimer);
      }
      resizeDebounceTimer = window.setTimeout(handleViewportResize, 120);
    });

    wrapper.addEventListener("input", function (event) {
      if (!event.target.classList.contains("nsl-v2-search-input")) return;
      query = event.target.value.trim();
      if (!query) {
        // Clearing search also clears selected store detail state.
        selectedStoreId = null;
        clearRoute();
      }
      currentPage = 1;
      renderSuggestions();
      renderFilters();
      renderExtraFilters();
      renderStoresAndMap();
    });

    function applyQuickFilter(nextQuickFilter) {
      if (nextQuickFilter === "near" && !userLocation) {
        if (!navigator.geolocation) {
          alert("Geolocation is not supported by your browser.");
          renderExtraFilters();
          return;
        }
        navigator.geolocation.getCurrentPosition(
          function (pos) {
            setUserMarker(pos.coords.latitude, pos.coords.longitude);
            map.flyTo([pos.coords.latitude, pos.coords.longitude], 14, { duration: 1 });
            activeQuickFilter = "near";
            currentPage = 1;
            renderExtraFilters();
            renderStoresAndMap();
          },
          function () {
            alert("Unable to access location. Please allow location permission in your browser.");
            renderExtraFilters();
          },
          { enableHighAccuracy: true, timeout: 10000 }
        );
        return;
      }
      activeQuickFilter = nextQuickFilter;
      currentPage = 1;
      renderExtraFilters();
      renderStoresAndMap();
    }

    wrapper.addEventListener("change", function (event) {
      if (event.target.classList.contains("nsl-v2-island-select")) {
        activeIsland = normalizeIslandSelection(event.target.value);
        activeRegion = "all";
        currentPage = 1;
        renderFilters();
        renderExtraFilters();
        renderStoresAndMap();
        return;
      }

      if (event.target.classList.contains("nsl-v2-region-select")) {
        activeRegion = normalizeLocationText(event.target.value) || "all";
        currentPage = 1;
        renderFilters();
        renderExtraFilters();
        renderStoresAndMap();
        return;
      }

      if (event.target.classList.contains("nsl-v2-quick-filter-select")) {
        applyQuickFilter(event.target.value || "all");
      }
    });

    wrapper.addEventListener("click", function (event) {
      var suggestion = event.target.closest(".nsl-v2-suggestion-item");
      if (suggestion) {
        var sid = suggestion.getAttribute("data-store-id");
        selectStoreById(sid, true);
        var found = stores.find(function (s) {
          return String(s.id) === String(sid);
        });
        if (found) {
          searchInput.value = found.title;
          query = found.title;
          renderFilters();
          renderExtraFilters();
          renderStoresAndMap();
          renderSuggestions();
          suggestionsEl.hidden = true;
        }
        return;
      }

      var filterBtn = event.target.closest(".nsl-v2-filter-parent, .nsl-v2-filter-child");
      if (filterBtn) {
        activeIsland = normalizeIslandSelection(filterBtn.getAttribute("data-island"));
        activeRegion = normalizeLocationText(filterBtn.getAttribute("data-region")) || "all";
        currentPage = 1;
        renderFilters();
        renderExtraFilters();
        renderStoresAndMap();
        return;
      }

      var extraBtn = event.target.closest(".nsl-v2-filter-extra");
      if (extraBtn) {
        var nextQuickFilter = extraBtn.getAttribute("data-extra-filter") || "all";
        applyQuickFilter(nextQuickFilter);
        return;
      }

      var pageBtn = event.target.closest(".nsl-v2-store-pagination__btn[data-page]");
      if (pageBtn) {
        var targetPage = parseInt(pageBtn.getAttribute("data-page") || "1", 10);
        if (targetPage > 0 && targetPage !== currentPage) {
          currentPage = targetPage;
          renderStoresAndMap();
          storeGrid.scrollIntoView({ behavior: "smooth", block: "start" });
        }
        return;
      }

      var pageNavBtn = event.target.closest(".nsl-v2-store-pagination__btn[data-page-nav]");
      if (pageNavBtn && !pageNavBtn.disabled) {
        var direction = pageNavBtn.getAttribute("data-page-nav");
        currentPage += direction === "next" ? 1 : -1;
        if (currentPage < 1) {
          currentPage = 1;
        }
        renderStoresAndMap();
        storeGrid.scrollIntoView({ behavior: "smooth", block: "start" });
        return;
      }

      var card = event.target.closest(".nsl-v2-store-card");
      if (card) {
        selectStoreById(card.getAttribute("data-store-id"), true);
        var mapShell = wrapper.querySelector(".nsl-v2-map-shell");
        if (mapShell) {
          mapShell.scrollIntoView({ behavior: "smooth", block: "start" });
        }
        return;
      }

      if (event.target.closest(".nsl-v2-route-mode-btn")) {
        var modeBtn = event.target.closest(".nsl-v2-route-mode-btn");
        routeMode = modeBtn.getAttribute("data-route-mode") || "driving";
        wrapper.querySelectorAll(".nsl-v2-route-mode-btn").forEach(function (btn) {
          btn.classList.toggle("is-active", btn === modeBtn);
        });
        var current = getSelectedStore();
        if (userLocation && current) {
          drawRouteToStore(current);
        }
        return;
      }

      if (event.target.closest(".nsl-v2-get-route")) {
        var selected = getSelectedStore();
        if (selected) {
          requestUserLocationAndRoute(selected);
        }
        return;
      }

      var thumb = event.target.closest(".nsl-v2-detail__thumb");
      if (thumb) {
        var detail = thumb.closest(".nsl-v2-detail");
        if (!detail) return;
        var hero = detail.querySelector(".nsl-v2-detail__image");
        var src = thumb.getAttribute("data-gallery-src");
        if (!hero || !src) return;
        hero.src = src;
        detail.querySelectorAll(".nsl-v2-detail__thumb").forEach(function (el) {
          el.classList.toggle("is-active", el === thumb);
        });
        return;
      }

      var addressToggle = event.target.closest(".nsl-v2-address-toggle");
      if (addressToggle) {
        var detailWrap = addressToggle.closest(".nsl-v2-detail");
        if (!detailWrap) return;
        var addressEl = detailWrap.querySelector(".nsl-v2-detail__address");
        if (!addressEl) return;
        var expanded = addressEl.getAttribute("data-address-expanded") === "1";
        if (expanded) {
          addressEl.classList.add("is-clamped");
          addressEl.setAttribute("data-address-expanded", "0");
          addressToggle.textContent = "See more";
        } else {
          addressEl.classList.remove("is-clamped");
          addressEl.setAttribute("data-address-expanded", "1");
          addressToggle.textContent = "See less";
        }
        return;
      }

      var openReviewModalBtn = event.target.closest(".nsl-v2-open-review-modal");
      if (openReviewModalBtn && reviewModal) {
        var storeIdForReview = openReviewModalBtn.getAttribute("data-store-id") || "";
        if (reviewModalPostId) {
          reviewModalPostId.value = storeIdForReview;
        }
        reviewModal.hidden = false;
        document.body.classList.add("nsl-v2-review-modal-open");
        return;
      }

      var detailTabBtn = event.target.closest(".nsl-v2-detail-tab");
      if (detailTabBtn) {
        var detailWrap = detailTabBtn.closest(".nsl-v2-detail");
        if (!detailWrap) return;
        var targetTab = detailTabBtn.getAttribute("data-detail-tab");
        if (!targetTab) return;

        detailWrap.querySelectorAll(".nsl-v2-detail-tab").forEach(function (tab) {
          tab.classList.toggle("is-active", tab === detailTabBtn);
        });
        detailWrap.querySelectorAll(".nsl-v2-detail-pane").forEach(function (pane) {
          pane.classList.toggle("is-active", pane.getAttribute("data-detail-pane") === targetTab);
        });

        if (targetTab === "overview") {
          syncAddressToggleVisibility();
        }
        return;
      }

      if (event.target.closest(".nsl-v2-review-modal__close")) {
        closeReviewModal();
        return;
      }

      if (reviewModal && !reviewModal.hidden && event.target === reviewModal) {
        closeReviewModal();
        return;
      }

      if (!event.target.closest(".nsl-v2-search-wrap")) {
        suggestionsEl.hidden = true;
      }
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeReviewModal();
      }
    });

    if (useLocationBtn) {
      useLocationBtn.addEventListener("click", function () {
        if (!navigator.geolocation) {
          alert("Geolocation is not supported by your browser.");
          return;
        }
        navigator.geolocation.getCurrentPosition(
          function (pos) {
            setUserMarker(pos.coords.latitude, pos.coords.longitude);
            map.flyTo([pos.coords.latitude, pos.coords.longitude], 14, { duration: 1 });
            var current = getSelectedStore();
            if (current) {
              drawRouteToStore(current);
            }
            renderExtraFilters();
            renderStoresAndMap();
          },
          function () {
            alert("Unable to access location. Please allow location permission in your browser.");
          },
          { enableHighAccuracy: true, timeout: 10000 }
        );
      });
    }
  }

  function runInit() {
    document.querySelectorAll(".noyona-store-locator-wrapper").forEach(initStoreLocator);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", runInit);
  } else {
    runInit();
  }
})();

