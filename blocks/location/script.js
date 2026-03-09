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

  function formatSelectedDetail(store) {
    if (!store) {
      return getEmptySelectedMarkup(true);
    }
    var phone = store.phone || store.tel || "";
    var gallery = Array.isArray(store.gallery) ? store.gallery : (store.image ? [store.image] : []);
    var galleryHtml = "";
    if (gallery.length) {
      galleryHtml =
        '<div class="nsl-v2-detail__gallery">' +
        gallery
          .slice(0, 5)
          .map(function (img, index) {
            return (
              '<button type="button" class="nsl-v2-detail__thumb' +
              (index === 0 ? " is-active" : "") +
              '" data-gallery-src="' +
              escHtml(img) +
              '">' +
              '<img src="' +
              escHtml(img) +
              '" alt="' +
              escHtml(store.title) +
              '" loading="lazy">' +
              "</button>"
            );
          })
          .join("") +
        "</div>";
    }

    var reviews = Array.isArray(store.reviews) ? store.reviews : [];
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
            var sourceLabel = review.source === "manual" ? "Admin" : "Customer";
            return (
              '<article class="nsl-v2-review-item">' +
              '<div class="nsl-v2-review-item__head">' +
              '<strong class="nsl-v2-review-item__name">' +
              reviewName +
              '</strong><span class="nsl-v2-review-item__stars">' +
              reviewStars +
              '</span></div><p class="nsl-v2-review-item__meta">' +
              escHtml(sourceLabel) +
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
      '<button type="button" class="nsl-v2-detail-tab" data-detail-tab="reviews">Reviews (' +
      escHtml(reviews.length) +
      ")</button>" +
      "</div>" +
      '<div class="nsl-v2-detail-pane is-active" data-detail-pane="overview">' +
      (store.image ? '<img class="nsl-v2-detail__image" src="' + escHtml(store.image) + '" alt="' + escHtml(store.title) + '" loading="lazy">' : "") +
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
      '<button type="button" class="nsl-v2-route-btn nsl-v2-get-route">Get Route From My Location</button>' +
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
    var searchInput = wrapper.querySelector(".nsl-v2-search-input");
    var suggestionsEl = wrapper.querySelector(".nsl-v2-suggestions");
    var selectedPanel = wrapper.querySelector(".nsl-v2-selected-panel");
    var parentFilterList = wrapper.querySelector(".nsl-v2-parent-filter-list");
    var childFilterList = wrapper.querySelector(".nsl-v2-child-filter-list");
    var extraFilterList = wrapper.querySelector(".nsl-v2-extra-filter-list");
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
      return store;
    });

    var query = "";
    var activeIsland = "Luzon";
    var activeRegion = "all";
    var activeQuickFilter = "all";
    var selectedStoreId = null;
    var currentPage = 1;
    var pageSize = 24;
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
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);

    var markers = {};
    var markerLayer = L.layerGroup().addTo(map);

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

    function getFullyFilteredStores() {
      var filtered = getSearchFilteredStores().filter(function (store) {
        if (activeIsland !== "all" && store.island_group !== activeIsland) return false;
        if (activeRegion !== "all" && store.region !== activeRegion) return false;
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
      var candidates = getSearchFilteredStores().slice(0, 10);
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
        var island = store.island_group || "Luzon";
        var region = store.region || "Uncategorized";
        if (!tree[island]) tree[island] = { count: 0, regions: {} };
        tree[island].count += 1;
        tree[island].regions[region] = (tree[island].regions[region] || 0) + 1;
      });
      return { bySearch: bySearch, tree: tree };
    }

    function renderFilters() {
      if (!parentFilterList || !childFilterList) return;
      var data = getFilterTreeBySearch();
      var tree = data.tree;
      var bySearch = data.bySearch;
      var parentOrder = ["Luzon", "Visayas", "Mindanao"];

      if (activeIsland !== "all" && !tree[activeIsland]) {
        activeIsland = parentOrder.find(function (name) {
          return !!tree[name];
        }) || "all";
        activeRegion = "all";
      }

      var parentHtml =
        '<button type="button" class="nsl-v2-filter-parent nsl-v2-filter-parent--all' +
        (activeIsland === "all" ? " is-active" : "") +
        '" data-island="all" data-region="all">All (' +
        bySearch.length +
        ")</button>";

      parentOrder.forEach(function (island) {
        var count = tree[island] ? tree[island].count : 0;
        parentHtml +=
          '<button type="button" class="nsl-v2-filter-parent' +
          (activeIsland === island ? " is-active" : "") +
          '" data-island="' +
          escHtml(island) +
          // '" data-region="all">(' +
          // count +
          // ") " +
          '">' +
          escHtml(island) +
          "</button>";
      });
      parentFilterList.innerHTML = parentHtml;

      if (activeIsland === "all") {
        childFilterList.innerHTML = '<div class="nsl-v2-filter-hint">Select Luzon, Visayas, or Mindanao to see child regions.</div>';
        return;
      }

      var islandTree = tree[activeIsland];
      if (!islandTree || !islandTree.regions || !Object.keys(islandTree.regions).length) {
        childFilterList.innerHTML = '<div class="nsl-v2-filter-hint">No regions found for this island group.</div>';
        return;
      }

      var childHtml =
        '<button type="button" class="nsl-v2-filter-child' +
        (activeRegion === "all" ? " is-active" : "") +
        '" data-island="' +
        escHtml(activeIsland) +
        '" data-region="all">All ' +
        escHtml(activeIsland) +
        // " (" +
        // islandTree.count +
        // ")</button>";
        "</button>";

      Object.keys(islandTree.regions)
        .sort()
        .forEach(function (region) {
          childHtml +=
            '<button type="button" class="nsl-v2-filter-child' +
            (activeRegion === region ? " is-active" : "") +
            '" data-island="' +
            escHtml(activeIsland) +
            '" data-region="' +
            escHtml(region) +
            '">(' +
            islandTree.regions[region] +
            ") " +
            escHtml(region) +
            "</button>";
        });
      childFilterList.innerHTML = childHtml;
    }

    function renderExtraFilters() {
      if (!extraFilterList) return;
      var options = [
        { key: "all", label: "All Stores" },
        { key: "open", label: "Open Now" },
        { key: "near", label: userLocation ? "Near Me (12km)" : "Near Me" },
        // { key: "top", label: "Top Rated (4.5+)" },
      ];
      extraFilterList.innerHTML = options
        .map(function (item) {
          return (
            '<button type="button" class="nsl-v2-filter-extra' +
            (activeQuickFilter === item.key ? " is-active" : "") +
            '" data-extra-filter="' +
            escHtml(item.key) +
            '">' +
            escHtml(item.label) +
            "</button>"
          );
        })
        .join("");
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
        selectedPanel.innerHTML = query ? getEmptySelectedMarkup(false) : getEmptySelectedMarkup(true);
        selectedPanel.classList.toggle("is-collapsed", !!query);
        setActiveMarker(null);
        clearRoute();
      } else {
        selectedPanel.classList.remove("is-collapsed");
        selectedPanel.innerHTML = formatSelectedDetail(selected);
        setActiveMarker(selected.id);
        syncAddressToggleVisibility();
      }
    }

    function selectStoreById(id, shouldFly) {
      var store = stores.find(function (s) {
        return String(s.id) === String(id);
      });
      if (!store) return;
      selectedStoreId = String(store.id);
      selectedPanel.innerHTML = formatSelectedDetail(store);
      setActiveMarker(store.id);
      syncAddressToggleVisibility();
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
      var addressEl = selectedPanel.querySelector(".nsl-v2-detail__address");
      var toggleEl = selectedPanel.querySelector(".nsl-v2-address-toggle");
      if (!addressEl || !toggleEl) return;
      var shouldShow = addressEl.scrollHeight > addressEl.clientHeight + 2;
      toggleEl.hidden = !shouldShow;
    }

    renderFilters();
    renderExtraFilters();
    renderStoresAndMap();

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
        activeIsland = filterBtn.getAttribute("data-island") || "all";
        activeRegion = filterBtn.getAttribute("data-region") || "all";
        currentPage = 1;
        renderFilters();
        renderExtraFilters();
        renderStoresAndMap();
        return;
      }

      var extraBtn = event.target.closest(".nsl-v2-filter-extra");
      if (extraBtn) {
        var nextQuickFilter = extraBtn.getAttribute("data-extra-filter") || "all";
        if (nextQuickFilter === "near" && !userLocation) {
          if (!navigator.geolocation) {
            alert("Geolocation is not supported by your browser.");
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
            },
            { enableHighAccuracy: true, timeout: 10000 }
          );
          return;
        }
        activeQuickFilter = nextQuickFilter;
        currentPage = 1;
        renderExtraFilters();
        renderStoresAndMap();
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

