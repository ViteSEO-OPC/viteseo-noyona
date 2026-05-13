/**
 * PDP: color swatches (hex term slugs), Buy now, content tabs.
 */
(function () {
  'use strict';

  function hexToCssColor(slug) {
    if (!slug || typeof slug !== 'string') {
      return '';
    }

    var s = slug.trim().replace(/^#/, '');

    if (/^[0-9a-fA-F]{6}$/.test(s)) {
      return '#' + s.toLowerCase();
    }

    if (/^[0-9a-fA-F]{3}$/.test(s)) {
      return (
        '#' +
        s[0].toLowerCase() +
        s[0].toLowerCase() +
        s[1].toLowerCase() +
        s[1].toLowerCase() +
        s[2].toLowerCase() +
        s[2].toLowerCase()
      );
    }

    var embedded = s.match(/(?:^|[-_])([0-9a-fA-F]{6})(?:$|[-_])/);
    if (embedded) {
      return '#' + embedded[1].toLowerCase();
    }

    return '';
  }

  function initTabs(root) {
    var wrap = root || document;
    var tabsets = wrap.querySelectorAll('[data-noyona-pdp-tabs]');

    tabsets.forEach(function (set) {
      var tabs = set.querySelectorAll('.noyona-pdp-tabs__tab');
      var panels = set.querySelectorAll('.noyona-pdp-tabs__panel');

      tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
          var id = tab.getAttribute('data-noyona-tab');
          if (!id) {
            return;
          }

          tabs.forEach(function (t) {
            var on = t.getAttribute('data-noyona-tab') === id;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
          });

          panels.forEach(function (p) {
            var on = p.getAttribute('data-noyona-panel') === id;
            p.classList.toggle('is-active', on);
            if (on) {
              p.removeAttribute('hidden');
            } else {
              p.setAttribute('hidden', '');
            }
          });
        });
      });
    });
  }

  function isColorAttributeSelect(select) {
    var name = (select.getAttribute('name') || '').toLowerCase();

    if (name === 'attribute_color' || name === 'attribute_colour') {
      return true;
    }

    if (name.indexOf('attribute_pa_color') === 0 || name.indexOf('attribute_pa_colour') === 0) {
      return true;
    }

    return false;
  }

  function normalizeAttributeKey(name) {
    var key = (name || '').toLowerCase();
    key = key.replace(/^attribute_/, '');
    key = key.replace(/^pa_/, '');
    key = key.replace(/[_\s]+/g, '-');
    key = key.replace(/-+/g, '-');
    key = key.replace(/^-+|-+$/g, '');
    return key;
  }

  function slugifyForCompare(value) {
    return String(value || '')
      .toLowerCase()
      .trim()
      .replace(/&/g, ' and ')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  function valuesEqualLoose(a, b) {
    var left = String(a || '').trim();
    var right = String(b || '').trim();
    if (!left && !right) {
      return true;
    }
    if (left === right) {
      return true;
    }
    if (left.toLowerCase() === right.toLowerCase()) {
      return true;
    }
    return slugifyForCompare(left) === slugifyForCompare(right);
  }

  function logVariationDebug(label, payload) {
    if (typeof console === 'undefined' || typeof console.log !== 'function') {
      return;
    }
    console.log('[Noyona PDP]', label, payload || {});
  }

  function debugVariationFormState(form, context) {
    if (!form) {
      return;
    }
    var selects = Array.prototype.slice.call(
      form.querySelectorAll('select[name^="attribute_"]'),
      0
    ).map(function (sel) {
      var selected = sel.options[sel.selectedIndex] || null;
      return {
        name: sel.name || '',
        value: sel.value || '',
        selectedText: selected ? String(selected.text || '').trim() : '',
      };
    });

    var variationIdInput = form.querySelector('input[name="variation_id"]');
    var rawVariations = form.getAttribute('data-product_variations');
    var parsedCount = 0;
    var variationAttrKeys = [];
    if (rawVariations) {
      try {
        var parsed = JSON.parse(rawVariations);
        if (Array.isArray(parsed)) {
          parsedCount = parsed.length;
          var keyMap = {};
          parsed.slice(0, 20).forEach(function (variation) {
            var attrs = variation && variation.attributes ? variation.attributes : {};
            Object.keys(attrs).forEach(function (key) {
              keyMap[key] = true;
            });
          });
          variationAttrKeys = Object.keys(keyMap);
        }
      } catch (e) {
        variationAttrKeys = ['<invalid-json>'];
      }
    }

    logVariationDebug('variation-form-state:' + String(context || 'unknown'), {
      hasVariationsFormClass: !!(form.classList && form.classList.contains('variations_form')),
      hasDataProductVariations: !!rawVariations,
      productVariationsCount: parsedCount,
      productVariationAttributeKeys: variationAttrKeys,
      attributes: selects,
      variation_id: variationIdInput ? variationIdInput.value : '',
    });
  }

  function getAttributeLabel(select) {
    var row = select.closest('tr');
    var labelNode = row ? row.querySelector('th.label label') : null;
    var labelText = labelNode ? String(labelNode.textContent || '').trim() : '';
    if (labelText) {
      return labelText;
    }

    var key = normalizeAttributeKey(select.getAttribute('name') || '');
    if (!key) {
      return 'Option';
    }

    return key
      .replace(/-/g, ' ')
      .replace(/\b\w/g, function (char) {
        return char.toUpperCase();
      });
  }

  function isSizePackAttributeSelect(select) {
    var key = normalizeAttributeKey(select.getAttribute('name') || '');
    var label = getAttributeLabel(select).toLowerCase();

    if (/^(size|sizes|pack-size|packsize|pack)$/.test(key)) {
      return true;
    }

    if (/^pack-size-/.test(key)) {
      return true;
    }

    if (/pack[\s_-]*size/.test(label)) {
      return true;
    }

    return label === 'size';
  }

  function shouldUsePillUi(select) {
    if (shouldUseSwatchUi(select)) {
      return false;
    }

    return isSizePackAttributeSelect(select);
  }

  function shouldUseSwatchUi(select) {
    if (isColorAttributeSelect(select)) {
      return true;
    }

    var name = (select.getAttribute('name') || '').toLowerCase();

    if (/^attribute_(pa_)?(shade|swatch|tone|tint)$/.test(name)) {
      return true;
    }

    var opts = Array.prototype.filter.call(select.options, function (o) {
      return o.value;
    });

    if (opts.length < 2) {
      return false;
    }

    return opts.every(function (o) {
      return hexToCssColor(o.value) !== '';
    });
  }

  function getVariationFormForSelect(select) {
    if (!select) {
      return null;
    }
    return select.closest('form.variations_form');
  }

  function findMatchingAttributeSelect(form, attributeToken, fallbackSelect) {
    if (!form) {
      return fallbackSelect || null;
    }

    var selects = Array.prototype.slice.call(
      form.querySelectorAll('select[name^="attribute_"]'),
      0
    );
    if (!selects.length) {
      return fallbackSelect || null;
    }

    var normalizedTarget = normalizeAttributeKey(attributeToken || '');
    if (normalizedTarget) {
      var direct = selects.find(function (candidate) {
        return (
          normalizeAttributeKey(candidate.getAttribute('name') || '') ===
          normalizedTarget
        );
      });
      if (direct) {
        return direct;
      }
    }

    if (
      fallbackSelect &&
      fallbackSelect.form === form &&
      fallbackSelect.getAttribute('name')
    ) {
      return fallbackSelect;
    }

    return selects.length === 1 ? selects[0] : (fallbackSelect || null);
  }

  function resolveOptionForSelect(select, requestedValue, requestedLabel, requestedIndex) {
    if (!select || !select.options) {
      return null;
    }

    var options = Array.prototype.slice.call(select.options, 0);
    var valueRaw = String(requestedValue || '').trim();
    var labelRaw = String(requestedLabel || '').trim();
    var valueLc = valueRaw.toLowerCase();
    var labelLc = labelRaw.toLowerCase();
    var valueSlug = slugifyForCompare(valueRaw);
    var labelSlug = slugifyForCompare(labelRaw);

    var match = null;

    if (valueRaw) {
      match =
        options.find(function (opt) {
          return String(opt.value) === valueRaw;
        }) ||
        options.find(function (opt) {
          return String(opt.value || '').toLowerCase() === valueLc;
        }) ||
        options.find(function (opt) {
          return slugifyForCompare(opt.value || '') === valueSlug;
        }) ||
        null;
    }

    if (!match && labelRaw) {
      match =
        options.find(function (opt) {
          return String(opt.text || '').trim() === labelRaw;
        }) ||
        options.find(function (opt) {
          return String(opt.text || '').trim().toLowerCase() === labelLc;
        }) ||
        options.find(function (opt) {
          return slugifyForCompare(opt.text || '') === labelSlug;
        }) ||
        options.find(function (opt) {
          return slugifyForCompare(opt.value || '') === labelSlug;
        }) ||
        null;
    }

    if (
      !match &&
      typeof requestedIndex === 'number' &&
      requestedIndex > -1 &&
      options[requestedIndex] &&
      options[requestedIndex].value
    ) {
      match = options[requestedIndex];
    }

    if (!match || !match.value) {
      return null;
    }

    return match;
  }

  function triggerWooVariationEvents(select) {
    if (!select) {
      return;
    }

    var form = getVariationFormForSelect(select);

    // Native change for non-jQuery listeners.
    select.dispatchEvent(new Event('change', { bubbles: true }));

    // WooCommerce variation form listeners are jQuery-based.
    if (typeof window.jQuery !== 'undefined') {
      var $select = window.jQuery(select);
      $select.trigger('change');

      if (form) {
        var $form = window.jQuery(form);
        $form.trigger('woocommerce_variation_select_change');
        $form.trigger('check_variations');
        $form.trigger('change');
      }
    }
  }

  function setSelectValueFromCustomUi(
    select,
    requestedValue,
    requestedIndex,
    requestedLabel,
    attributeToken
  ) {
    if (!select || !select.options) {
      return false;
    }

    var form = getVariationFormForSelect(select);
    var targetSelect = findMatchingAttributeSelect(
      form,
      attributeToken || select.getAttribute('name') || '',
      select
    );
    var targetOption = resolveOptionForSelect(
      targetSelect,
      requestedValue,
      requestedLabel,
      requestedIndex
    );

    if (!targetSelect || !targetOption || !targetOption.value) {
      logVariationDebug('variation-select-match-failed', {
        clickedCustomValue: requestedValue || '',
        clickedCustomLabel: requestedLabel || '',
        attributeToken: attributeToken || '',
        sourceSelectName: select.getAttribute('name') || '',
      });
      return false;
    }

    // Always sync by the real option value (slug), never by label text.
    targetSelect.value = String(targetOption.value);
    targetSelect.selectedIndex = targetOption.index;

    if (targetSelect !== select) {
      select.value = String(targetOption.value);
      select.selectedIndex = targetOption.index;
    }

    logVariationDebug('variation-option-click-sync', {
      clickedCustomValue: requestedValue || '',
      clickedCustomLabel: requestedLabel || '',
      matchedSelectName: targetSelect.getAttribute('name') || '',
      matchedOptionValue: targetOption.value,
      matchedOptionLabel: String(targetOption.text || '').trim(),
    });

    triggerWooVariationEvents(targetSelect);
    return true;
  }

  function buildSwatchRow(select) {
    if (select.closest('.noyona-pdp-variation__shade-box')) {
      if (typeof select._noyonaUiSync === 'function') {
        select._noyonaUiSync();
      }
      return;
    }

    if (!shouldUseSwatchUi(select)) {
      return;
    }

    var wrap = document.createElement('div');
    wrap.className = 'noyona-pdp-variation__shade-box';

    var header = document.createElement('div');
    header.className = 'noyona-pdp-variation__shade-head';

    var label = document.createElement('span');
    label.className = 'noyona-pdp-variation__shade-label';
    label.textContent =
      typeof window.noyonaPdp !== 'undefined' &&
      window.noyonaPdp.i18n &&
      window.noyonaPdp.i18n.selectShade
        ? window.noyonaPdp.i18n.selectShade
        : 'Select shade';

    var current = document.createElement('span');
    current.className = 'noyona-pdp-variation__shade-current';
    current.setAttribute('data-noyona-shade-name', '');

    header.appendChild(label);
    header.appendChild(current);

    var row = document.createElement('div');
    row.className = 'noyona-pdp-variation__swatches';
    row.setAttribute('role', 'list');

    function updateCurrentLabel() {
      var opt = select.options[select.selectedIndex];
      current.textContent = opt && opt.text ? opt.text.trim() : '';
    }

    var options = Array.prototype.slice.call(select.options, 0);

    options.forEach(function (opt, index) {
      if (!opt.value) {
        return;
      }

      var hex = hexToCssColor(opt.value);
      var btn = document.createElement('button');

      btn.type = 'button';
      btn.className = 'noyona-pdp-swatch';
      btn.setAttribute('role', 'listitem');
      btn.setAttribute('aria-label', opt.text || opt.value);
      btn.setAttribute('data-value', opt.value);
      btn.setAttribute('data-index', String(index));

      if (hex) {
        btn.style.setProperty('--noyona-swatch', hex);
      } else {
        btn.classList.add('noyona-pdp-swatch--fallback');
      }

      btn.addEventListener('click', function () {
        setSelectValueFromCustomUi(
          select,
          opt.value,
          index,
          opt.text || opt.value,
          select.getAttribute('name') || ''
        );
      });

      row.appendChild(btn);
    });

    select.classList.add('noyona-pdp-variation__select-hidden');
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(header);
    wrap.appendChild(row);
    wrap.appendChild(select);

    function syncFromSelect() {
      updateCurrentLabel();
      var selectedValue = '';
      if (select.selectedIndex > -1 && select.options[select.selectedIndex]) {
        selectedValue = select.options[select.selectedIndex].value || '';
      }

      row.querySelectorAll('.noyona-pdp-swatch').forEach(function (button) {
        var value = button.getAttribute('data-value') || '';
        var idx = parseInt(button.getAttribute('data-index') || '-1', 10);
        var disabled = false;
        if (idx > -1 && select.options[idx]) {
          disabled = !!select.options[idx].disabled;
        }
        button.classList.toggle('is-selected', selectedValue !== '' && value === selectedValue);
        button.disabled = disabled;
        button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
      });
    }

    select._noyonaUiSync = syncFromSelect;
    select.addEventListener('change', syncFromSelect);
    syncFromSelect();
  }

  function buildPillRow(select) {
    if (select.closest('.noyona-pdp-variation__choice-box')) {
      if (typeof select._noyonaUiSync === 'function') {
        select._noyonaUiSync();
      }
      return;
    }

    if (select.closest('.noyona-pdp-variation__shade-box') || select.closest('.noyona-pdp-variation__dropdown-box')) {
      return;
    }

    if (!shouldUsePillUi(select)) {
      return;
    }

    var wrap = document.createElement('div');
    wrap.className = 'noyona-pdp-variation__choice-box';

    var header = document.createElement('div');
    header.className = 'noyona-pdp-variation__field-head';

    var label = document.createElement('span');
    label.className = 'noyona-pdp-variation__field-label';

    var attributeLabel = getAttributeLabel(select);
    label.textContent = 'Select ' + attributeLabel.toLowerCase();

    var current = document.createElement('span');
    current.className = 'noyona-pdp-variation__field-current';

    header.appendChild(label);
    header.appendChild(current);

    var row = document.createElement('div');
    row.className = 'noyona-pdp-variation__choices';
    row.setAttribute('role', 'list');

    var options = Array.prototype.slice.call(select.options, 0);
    options.forEach(function (opt, index) {
      if (!opt.value) {
        return;
      }

      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'noyona-pdp-choice';
      btn.setAttribute('role', 'listitem');
      btn.setAttribute('data-value', opt.value);
      btn.setAttribute('data-index', String(index));
      btn.textContent = opt.text || opt.value;

      btn.addEventListener('click', function () {
        if (btn.disabled) {
          return;
        }
        setSelectValueFromCustomUi(
          select,
          opt.value,
          index,
          opt.text || opt.value,
          select.getAttribute('name') || ''
        );
      });

      row.appendChild(btn);
    });

    select.classList.add('noyona-pdp-variation__select-hidden');
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(header);
    wrap.appendChild(row);
    wrap.appendChild(select);

    function syncFromSelect() {
      var selectedOption = select.options[select.selectedIndex] || null;
      current.textContent =
        selectedOption && selectedOption.value
          ? String(selectedOption.text || '').trim()
          : '';

      row.querySelectorAll('.noyona-pdp-choice').forEach(function (button) {
        var value = button.getAttribute('data-value') || '';
        var idx = parseInt(button.getAttribute('data-index') || '-1', 10);
        var disabled = false;
        if (idx > -1 && select.options[idx]) {
          disabled = !!select.options[idx].disabled;
        }
        var isSelected = selectedOption && selectedOption.value && value === selectedOption.value;
        button.classList.toggle('is-selected', !!isSelected);
        button.classList.toggle('is-disabled', !!disabled);
        button.disabled = !!disabled;
        button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
        button.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
      });
    }

    select._noyonaUiSync = syncFromSelect;
    select.addEventListener('change', syncFromSelect);
    syncFromSelect();
  }

  function buildDropdownRow(select) {
    if (select.closest('.noyona-pdp-variation__dropdown-box')) {
      if (typeof select._noyonaUiSync === 'function') {
        select._noyonaUiSync();
      }
      return;
    }

    if (select.closest('.noyona-pdp-variation__shade-box') || select.closest('.noyona-pdp-variation__choice-box')) {
      return;
    }

    var wrap = document.createElement('div');
    wrap.className = 'noyona-pdp-variation__dropdown-box';

    var header = document.createElement('div');
    header.className = 'noyona-pdp-variation__field-head';

    var label = document.createElement('span');
    label.className = 'noyona-pdp-variation__field-label';
    label.textContent = 'Select ' + getAttributeLabel(select).toLowerCase();
    header.appendChild(label);

    var selectWrap = document.createElement('div');
    selectWrap.className = 'noyona-pdp-variation__dropdown';

    select.classList.remove('noyona-pdp-variation__select-hidden');
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(header);
    wrap.appendChild(selectWrap);
    selectWrap.appendChild(select);

    function syncFromSelect() {
      wrap.classList.toggle('has-value', !!select.value);
    }

    select._noyonaUiSync = syncFromSelect;
    select.addEventListener('change', syncFromSelect);
    syncFromSelect();
  }

  function buildVariationControl(select) {
    if (shouldUseSwatchUi(select)) {
      buildSwatchRow(select);
      return;
    }

    if (shouldUsePillUi(select)) {
      buildPillRow(select);
      return;
    }

    buildDropdownRow(select);
  }

  /* ------------------------------------------------------------------ */
  /* Variation-id resolution fallback.                                  */
  /*                                                                    */
  /* On hosts where wc-add-to-cart-variation.js fails to run, the swatch */
  /* click never produces a `variation_id` and our add-to-cart aborts   */
  /* with the "Please select all options" alert. Resolve the variation  */
  /* ourselves from the form's data-product_variations JSON (which WC   */
  /* inlines on every PDP for products with ≤30 variations).            */
  /* ------------------------------------------------------------------ */

  function getInlineVariations(form) {
    if (!form) return null;
    if ('_noyonaVariations' in form) return form._noyonaVariations;

    var raw = form.getAttribute('data-product_variations');
    if (!raw) {
      // For >30 variations WC uses AJAX and leaves this attr empty. Bail —
      // user gets the original alert path; not worse than before.
      form._noyonaVariations = null;
      logVariationDebug('data-product_variations-missing', {
        formClass: form.className || '',
      });
      return null;
    }
    try {
      var parsed = JSON.parse(raw);
      form._noyonaVariations = Array.isArray(parsed) ? parsed : null;
      if (Array.isArray(parsed)) {
        var keyMap = {};
        parsed.slice(0, 20).forEach(function (variation) {
          var attrs = variation && variation.attributes ? variation.attributes : {};
          Object.keys(attrs).forEach(function (key) {
            keyMap[key] = true;
          });
        });
        logVariationDebug('data-product_variations-loaded', {
          count: parsed.length,
          attributeKeys: Object.keys(keyMap),
        });
      }
    } catch (e) {
      form._noyonaVariations = null;
      logVariationDebug('data-product_variations-invalid-json', {
        error: e && e.message ? e.message : String(e),
      });
    }
    return form._noyonaVariations;
  }

  function findMatchingVariation(form) {
    var variations = getInlineVariations(form);
    if (!variations || variations.length === 0) return null;

    var selects = form.querySelectorAll('select[name^="attribute_"]');
    if (selects.length === 0) return null;

    var picked = [];
    var allSelected = true;
    selects.forEach(function (sel) {
      var name = String(sel.getAttribute('name') || '');
      var normalizedName = normalizeAttributeKey(name);
      var val = sel.value || '';
      picked.push({
        name: name,
        normalizedName: normalizedName,
        value: val,
      });
      if (!val) allSelected = false;
    });

    if (!allSelected) {
      logVariationDebug('matching-variation-incomplete-selection', {
        picked: picked,
      });
      return null;
    }

    // Each variation has `.attributes` keyed by `attribute_<slug>`. An empty
    // value on a variation attribute means "any value matches" (WC default
    // for "Any" attribute). Check every select's value matches.
    for (var i = 0; i < variations.length; i++) {
      var v = variations[i];
      var attrs = (v && v.attributes) || {};
      var normalizedAttrMap = {};
      Object.keys(attrs).forEach(function (attrKey) {
        normalizedAttrMap[normalizeAttributeKey(attrKey)] = attrs[attrKey];
      });
      var ok = true;
      for (var p = 0; p < picked.length; p++) {
        var pickedAttr = picked[p];
        var vAttr = attrs[pickedAttr.name];
        if (typeof vAttr === 'undefined') {
          vAttr = normalizedAttrMap[pickedAttr.normalizedName];
        }
        if (vAttr === undefined) continue; // attribute not on variation matrix
        if (vAttr === '' || vAttr === null) continue; // "any" — matches anything
        if (!valuesEqualLoose(vAttr, pickedAttr.value)) {
          ok = false;
          break;
        }
      }
      if (ok) {
        logVariationDebug('matching-variation-found', {
          variation_id: v && v.variation_id ? v.variation_id : '',
          picked: picked,
          variationAttributes: attrs,
        });
        return v;
      }
    }
    logVariationDebug('matching-variation-not-found', {
      picked: picked,
      variationCount: variations.length,
    });
    return null;
  }

  function ensureVariationIdInput(form) {
    var input = form.querySelector('input[name="variation_id"]');
    if (!input) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'variation_id';
      input.value = '0';
      form.appendChild(input);
    }
    return input;
  }

  function syncVariationId(form) {
    if (!form || !form.classList.contains('variations_form')) return;
    var match = findMatchingVariation(form);
    var input = ensureVariationIdInput(form);
    var newId = match && match.variation_id ? String(match.variation_id) : '0';
    if (input.value !== newId) {
      input.value = newId;
    }
    // Mirror Woo's events so anything else on the page (price sync, gallery
    // fallback variation-image swap) reacts correctly. jQuery is available
    // because WC core enqueues it; bindWooCommercePdpHooks already guards.
    if (match && window.jQuery) {
      window.jQuery(form).trigger('found_variation', [match]);
      window.jQuery(form).trigger('show_variation', [match]);
    } else if (!match && window.jQuery) {
      window.jQuery(form).trigger('reset_data');
    }
    logVariationDebug('sync-variation-id', {
      variation_id: input.value,
      hasMatch: !!match,
      formClass: form.className || '',
    });
  }

  function applyUrlVariationParams(form) {
    if (!window.URLSearchParams) return false;
    var params;
    try {
      params = new URLSearchParams(window.location.search);
    } catch (e) {
      return false;
    }
    var changed = false;
    form.querySelectorAll('select[name^="attribute_"]').forEach(function (sel) {
      var name = sel.getAttribute('name') || '';
      if (!params.has(name)) return;
      var val = params.get(name);
      if (val == null || val === sel.value) return;
      var has = Array.prototype.some.call(sel.options, function (o) {
        return o.value === val;
      });
      if (has) {
        setSelectValueFromCustomUi(
          sel,
          val,
          null,
          val,
          sel.getAttribute('name') || ''
        );
        changed = true;
      }
    });
    return changed;
  }

  function bindVariationIdSync(form) {
    if (!form || !form.classList.contains('variations_form')) return;
    if (form._noyonaVariationSyncBound) return;
    form._noyonaVariationSyncBound = true;

    var selects = form.querySelectorAll('select[name^="attribute_"]');
    selects.forEach(function (sel) {
      sel.addEventListener('change', function () {
        // Defer one tick so any concurrent WC handlers run first; whichever
        // wins, we end with the correct variation_id (idempotent set).
        setTimeout(function () { syncVariationId(form); }, 0);
      });
    });

    // Honour ?attribute_*=… in the URL (WC's script does this when present).
    applyUrlVariationParams(form);

    // Initial pass — covers the URL-preselect case and any server-rendered
    // selected-option markup.
    syncVariationId(form);
  }

  function initSwatches(form) {
    if (!form) {
      return;
    }

    if (!form._noyonaInitialVariationDebug) {
      form._noyonaInitialVariationDebug = true;
      debugVariationFormState(form, 'init-swatches');
    }

    var selects = form.querySelectorAll('select[name^="attribute_"]');
    selects.forEach(buildVariationControl);

    bindVariationIdSync(form);
  }

  function bindWooCommercePdpHooks() {
    if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.on) {
      return;
    }

    var $ = window.jQuery;

    $(document.body).on('wc_variation_form', 'form.variations_form', function () {
      initSwatches(this);
    });

    $(document.body).on(
      'woocommerce_update_variation_values woocommerce_variation_has_changed reset_data found_variation',
      'form.variations_form',
      function () {
        initSwatches(this);
      }
    );
  }

  function bindVariationPriceSync(form) {
    if (!form || !form.classList.contains('variations_form')) {
      return;
    }
    if (typeof window.jQuery === 'undefined') {
      return;
    }

    var mainPrice = document.querySelector(
      '.single-product .wp-block-woocommerce-product-price .wc-block-components-product-price'
    );
    if (!mainPrice) {
      return;
    }

    if (!mainPrice.getAttribute('data-original-price-html')) {
      mainPrice.setAttribute('data-original-price-html', mainPrice.innerHTML);
    }

    var $form = window.jQuery(form);
    if ($form.data('noyonaPriceSyncBound')) {
      return;
    }
    $form.data('noyonaPriceSyncBound', true);

    $form.on('found_variation show_variation', function (event, variation) {
      if (variation && variation.price_html) {
        mainPrice.innerHTML = variation.price_html;
      }
    });

    $form.on('reset_data hide_variation', function () {
      var original = mainPrice.getAttribute('data-original-price-html');
      if (original !== null) {
        mainPrice.innerHTML = original;
      }
    });
  }

  function getAddToCartEndpoint() {
    if (
      typeof window.wc_add_to_cart_params !== 'undefined' &&
      window.wc_add_to_cart_params &&
      window.wc_add_to_cart_params.wc_ajax_url
    ) {
      return String(window.wc_add_to_cart_params.wc_ajax_url).replace('%%endpoint%%', 'add_to_cart');
    }
    return '/?wc-ajax=add_to_cart';
  }

  function maybeOpenMiniCartDrawer() {
    var miniCartBtn = document.querySelector('.wc-block-mini-cart__button');
    if (miniCartBtn) {
      miniCartBtn.click();
    }
  }

  function getGlobalCheckoutLoginModal() {
    return document.querySelector('[data-mini-cart-login-modal-global]');
  }

  function closeGlobalCheckoutLoginModal() {
    var modal = getGlobalCheckoutLoginModal();
    if (!modal) {
      return;
    }
    modal.hidden = true;
    document.documentElement.classList.remove('noyona-mini-cart-login-open');
  }

  function setGlobalCheckoutLoginRedirect(redirectUrl) {
    var modal = getGlobalCheckoutLoginModal();
    if (!modal || !redirectUrl) {
      return;
    }

    var hiddenRedirect = modal.querySelector('[data-mini-cart-login-redirect]');
    if (hiddenRedirect) {
      hiddenRedirect.setAttribute('value', String(redirectUrl));
    }

    var loginLink = modal.querySelector('[data-mini-cart-login-action]');
    if (loginLink) {
      var href = loginLink.getAttribute('href') || '/wp-login.php';
      try {
        var url = new URL(href, window.location.origin);
        url.searchParams.set('redirect_to', String(redirectUrl));
        loginLink.setAttribute('href', url.toString());
      } catch (e) {
        // Keep existing URL when parsing fails.
      }
    }
  }

  function bindGlobalCheckoutLoginModal() {
    var modal = getGlobalCheckoutLoginModal();
    if (!modal || modal.dataset.noyonaBound === '1') {
      return;
    }
    modal.dataset.noyonaBound = '1';

    var closeButtons = modal.querySelectorAll('[data-mini-cart-login-close]');
    closeButtons.forEach(function (button) {
      button.addEventListener('click', closeGlobalCheckoutLoginModal);
    });

    if (document.documentElement.getAttribute('data-noyona-global-login-esc-bound') !== '1') {
      document.documentElement.setAttribute('data-noyona-global-login-esc-bound', '1');
      document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
          return;
        }
        closeGlobalCheckoutLoginModal();
      });
    }
  }

  function openGlobalCheckoutLoginModal(redirectUrl, modalCopy) {
    var modal = getGlobalCheckoutLoginModal();
    if (!modal) {
      window.location.href = '/my-account/';
      return;
    }

    bindGlobalCheckoutLoginModal();
    setGlobalCheckoutLoginRedirect(redirectUrl || window.location.href);

    if (modalCopy && typeof modalCopy === 'object') {
      var title = modal.querySelector('.noyona-mini-cart-login-title');
      var copy = modal.querySelector('.noyona-mini-cart-login-copy');
      if (title && modalCopy.title) {
        title.textContent = String(modalCopy.title);
      }
      if (copy && modalCopy.copy) {
        copy.textContent = String(modalCopy.copy);
      }
    }

    modal.hidden = false;
    document.documentElement.classList.add('noyona-mini-cart-login-open');
  }

  function invalidateWooBlocksCartStore() {
    if (!window.wp || !window.wp.data || typeof window.wp.data.dispatch !== 'function') {
      return;
    }

    var stores = ['wc/store/cart', 'wc/store/cart-data', 'wc/store'];
    stores.forEach(function (storeKey) {
      var dispatch = null;
      try {
        dispatch = window.wp.data.dispatch(storeKey);
      } catch (e) {
        dispatch = null;
      }
      if (!dispatch) {
        return;
      }

      if (typeof dispatch.invalidateResolutionForStore === 'function') {
        try {
          dispatch.invalidateResolutionForStore();
        } catch (e) {
          // noop
        }
      }
      if (typeof dispatch.invalidateResolution === 'function') {
        try {
          dispatch.invalidateResolution('getCartData', []);
        } catch (e) {
          // noop
        }
        try {
          dispatch.invalidateResolution('getCart', []);
        } catch (e) {
          // noop
        }
        try {
          dispatch.invalidateResolution('getCartTotals', []);
        } catch (e) {
          // noop
        }
      }
    });
  }

  function broadcastCartAdded(detail) {
    var payload = Object.assign(
      {
        preserveCartData: false,
      },
      detail || {}
    );

    document.body.dispatchEvent(
      new CustomEvent('wc-blocks_added_to_cart', {
        bubbles: true,
        detail: payload,
      })
    );
    document.dispatchEvent(
      new CustomEvent('wc-blocks_added_to_cart', {
        bubbles: true,
        detail: payload,
      })
    );
    window.dispatchEvent(
      new CustomEvent('wc-blocks_added_to_cart', {
        detail: payload,
      })
    );
    document.body.dispatchEvent(
      new CustomEvent('noyona_cart_added', {
        bubbles: true,
        detail: {
          source: 'single-product',
        },
      })
    );
  }

  function clearLegacyAddToCartState() {
    try {
      var url = new URL(window.location.href);
      if (url.searchParams.has('add-to-cart')) {
        url.searchParams.delete('add-to-cart');
        var next =
          url.pathname +
          (url.searchParams.toString() ? '?' + url.searchParams.toString() : '') +
          (url.hash || '');
        window.history.replaceState({}, '', next);
      }
    } catch (e) {
      // Ignore URL parsing errors.
    }

    document
      .querySelectorAll(
        '.wp-block-woocommerce-store-notices .wc-block-components-notice-banner.is-success,' +
          '.woocommerce-notices-wrapper .woocommerce-message,' +
          '.wp-block-woocommerce-store-notices .wc-block-components-notice-banner.is-error,' +
          '.woocommerce-notices-wrapper .woocommerce-error'
      )
      .forEach(function (notice) {
        notice.remove();
      });
  }

  function bindAjaxAddToCart(form) {
    if (!form || form.getAttribute('data-noyona-ajax-cart-bound') === '1') {
      return;
    }

    form.setAttribute('data-noyona-ajax-cart-bound', '1');
    var addBtn = form.querySelector('.single_add_to_cart_button');
    var buyNowField = form.querySelector('input[name="noyona_buy_now"]');
    var inFlight = false;
    var lastRequestAt = 0;

    function runAjaxAddToCart() {
      addBtn = form.querySelector('.single_add_to_cart_button');
      if (!addBtn || addBtn.disabled || addBtn.classList.contains('disabled')) {
        return;
      }
      if (inFlight || addBtn.classList.contains('loading')) {
        return;
      }

      var now = Date.now();
      if (now - lastRequestAt < 350) {
        return;
      }
      lastRequestAt = now;

      var selectedVariationId = 0;
      // Keep variation UX consistent with Woo validation before request.
      if (form.classList.contains('variations_form')) {
        debugVariationFormState(form, 'before-add-to-cart');
        var variationId = form.querySelector('input[name="variation_id"]');
        if (!variationId || !variationId.value || variationId.value === '0') {
          var msg =
            typeof window.noyonaPdp !== 'undefined' &&
            window.noyonaPdp.i18n &&
            window.noyonaPdp.i18n.selectOptions
              ? window.noyonaPdp.i18n.selectOptions
              : 'Please select all options.';
          window.alert(msg);
          return;
        }
        selectedVariationId = parseInt(variationId.value, 10) || 0;
      }

      var endpoint = getAddToCartEndpoint();
      if (!endpoint) {
        return;
      }

      inFlight = true;
      addBtn.classList.add('loading');

      var formData = new FormData(form);
      var payload = new URLSearchParams();

      formData.forEach(function (value, key) {
        if (value === null || typeof value === 'undefined') {
          return;
        }
        var stringValue = String(value);
        if (key !== 'quantity' && stringValue === '') {
          return;
        }
        payload.append(key, stringValue);
      });

      if (!payload.has('product_id')) {
        var explicitProductId =
          form.querySelector('input[name="add-to-cart"]') ||
          form.querySelector('button[name="add-to-cart"]');
        if (explicitProductId && explicitProductId.value) {
          payload.append('product_id', String(explicitProductId.value));
        }
      }

      if (!payload.has('quantity')) {
        payload.append('quantity', '1');
      }

      // Prevent duplicate additions on variable products:
      // this store doubles quantity when both add-to-cart (parent) and product_id (variation) are sent.
      if (payload.has('add-to-cart')) {
        payload.delete('add-to-cart');
      }

      // For variable products, this store accepts AJAX add-to-cart when product_id is the variation ID.
      if (form.classList.contains('variations_form') && selectedVariationId > 0) {
        payload.set('product_id', String(selectedVariationId));
        payload.set('variation_id', String(selectedVariationId));
      }

      fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        },
        body: payload.toString(),
      })
        .then(function (response) {
          return response.text();
        })
        .then(function (raw) {
          var data = null;
          if (raw) {
            try {
              data = JSON.parse(raw);
            } catch (e) {
              data = null;
            }
          }

          if (data && data.error) {
            addBtn.classList.add('is-error');
            return;
          }

          addBtn.classList.remove('is-error');
          addBtn.classList.remove('loading');
          addBtn.classList.add('added');

          if (typeof window.jQuery !== 'undefined') {
            window.jQuery(document.body).trigger('added_to_cart', [
              data && data.fragments ? data.fragments : null,
              data && data.cart_hash ? data.cart_hash : '',
              addBtn,
            ]);
            window.jQuery(document.body).trigger('wc_fragment_refresh');
          }

          invalidateWooBlocksCartStore();
          broadcastCartAdded({
            source: 'single-product',
          });
          clearLegacyAddToCartState();

          // Open the mini-cart drawer so user sees updated cart, not cart page link.
          setTimeout(maybeOpenMiniCartDrawer, 120);
        })
        .catch(function () {
          // Do not fallback-submit the form: this causes reload + duplicate cart lines.
        })
        .finally(function () {
          inFlight = false;
          addBtn.classList.remove('loading');
        });
    }
    form.__noyonaRunAjaxAddToCart = runAjaxAddToCart;

    if (addBtn && !addBtn.getAttribute('data-noyona-reset-buy-now-bound')) {
      addBtn.setAttribute('data-noyona-reset-buy-now-bound', '1');
      addBtn.addEventListener('click', function () {
        if (buyNowField) {
          buyNowField.value = '';
        }
      });
    }

    form.addEventListener('submit', function (event) {
      buyNowField = form.querySelector('input[name="noyona_buy_now"]');
      if (buyNowField && buyNowField.value === '1') {
        return;
      }

      event.preventDefault();
      event.stopImmediatePropagation();
      runAjaxAddToCart();
    }, true);
  }

  function bindGlobalAjaxCartGuards() {
    if (document.documentElement.getAttribute('data-noyona-pdp-cart-guard') === '1') {
      return;
    }
    document.documentElement.setAttribute('data-noyona-pdp-cart-guard', '1');

    document.addEventListener(
      'click',
      function (event) {
        var targetBtn = event.target.closest('form.cart .single_add_to_cart_button');
        if (!targetBtn) {
          return;
        }
        var form = targetBtn.closest('form.cart');
        if (!form) {
          return;
        }

        var buyNowField = form.querySelector('input[name="noyona_buy_now"]');
        if (buyNowField && buyNowField.value === '1') {
          return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        if (typeof form.__noyonaRunAjaxAddToCart === 'function') {
          form.__noyonaRunAjaxAddToCart();
        }
      },
      true
    );

    document.addEventListener(
      'submit',
      function (event) {
        var form = event.target && event.target.closest ? event.target.closest('form.cart') : null;
        if (!form) {
          return;
        }

        var buyNowField = form.querySelector('input[name="noyona_buy_now"]');
        if (buyNowField && buyNowField.value === '1') {
          return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        if (typeof form.__noyonaRunAjaxAddToCart === 'function') {
          form.__noyonaRunAjaxAddToCart();
        }
      },
      true
    );
  }

  function addBuyNow(form) {
    var addBtn = form.querySelector('.single_add_to_cart_button');
    if (!addBtn || form.querySelector('.noyona-pdp-buy-now')) {
      return;
    }

    var buy = document.createElement('button');
    buy.type = 'button';
    buy.className =
      'noyona-pdp-buy-now button alt wp-element-button wc-block-components-button';
    buy.textContent =
      typeof window.noyonaPdp !== 'undefined' &&
      window.noyonaPdp.i18n &&
      window.noyonaPdp.i18n.buyNow
        ? window.noyonaPdp.i18n.buyNow
        : 'Buy now';

    var actions = document.createElement('div');
    actions.className = 'noyona-pdp-cart-actions';

    var btnParent = addBtn.parentNode;
    btnParent.insertBefore(actions, addBtn);
    actions.appendChild(addBtn);
    actions.appendChild(buy);

    buy.addEventListener('click', function () {
      if (!document.body.classList.contains('logged-in')) {
        openGlobalCheckoutLoginModal(window.location.href, {
          title: 'Log In to complete your purchase',
          copy: 'Please log in to your account so we can verify your purchase and publish your review.',
        });
        return;
      }

      var msg =
        typeof window.noyonaPdp !== 'undefined' &&
        window.noyonaPdp.i18n &&
        window.noyonaPdp.i18n.selectOptions
          ? window.noyonaPdp.i18n.selectOptions
          : 'Please select all options.';

      if (form.classList.contains('variations_form')) {
        var variationId = form.querySelector('input[name="variation_id"]');
        if (!variationId || !variationId.value || variationId.value === '0') {
          window.alert(msg);
          return;
        }
      }

      var existing = form.querySelector('input[name="noyona_buy_now"]');
      if (!existing) {
        existing = document.createElement('input');
        existing.type = 'hidden';
        existing.name = 'noyona_buy_now';
        form.appendChild(existing);
      }

      existing.value = '1';

      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit(addBtn);
      } else {
        form.submit();
      }
    });
  }

  function enhanceQuantity(form) {
    if (!form) {
      return;
    }
    var qtyWrap = form.querySelector('.quantity');
    var qtyInput = qtyWrap ? qtyWrap.querySelector('.qty') : null;
    if (!qtyWrap || !qtyInput || qtyWrap.classList.contains('noyona-pdp-qty')) {
      return;
    }

    qtyWrap.classList.add('noyona-pdp-qty');

    var minus = document.createElement('button');
    minus.type = 'button';
    minus.className = 'noyona-pdp-qty__btn noyona-pdp-qty__btn--minus';
    minus.setAttribute('aria-label', 'Decrease quantity');
    minus.textContent = '−';

    var plus = document.createElement('button');
    plus.type = 'button';
    plus.className = 'noyona-pdp-qty__btn noyona-pdp-qty__btn--plus';
    plus.setAttribute('aria-label', 'Increase quantity');
    plus.textContent = '+';

    qtyWrap.insertBefore(minus, qtyInput);
    qtyWrap.appendChild(plus);

    function asNumber(value, fallback) {
      var n = parseFloat(value);
      return isNaN(n) ? fallback : n;
    }

    function clamp(next) {
      var min = asNumber(qtyInput.min, 1);
      var max = qtyInput.max === '' ? Infinity : asNumber(qtyInput.max, Infinity);
      var step = asNumber(qtyInput.step, 1);
      if (!isFinite(step) || step <= 0) {
        step = 1;
      }
      var val = Math.max(min, Math.min(max, next));
      var rounded = Math.round(val / step) * step;
      if (Math.abs(rounded - Math.round(rounded)) < 0.0001) {
        rounded = Math.round(rounded);
      }
      return rounded;
    }

    minus.addEventListener('click', function () {
      var current = asNumber(qtyInput.value, asNumber(qtyInput.min, 1));
      qtyInput.value = String(clamp(current - asNumber(qtyInput.step, 1)));
      qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
    });

    plus.addEventListener('click', function () {
      var current = asNumber(qtyInput.value, asNumber(qtyInput.min, 1));
      qtyInput.value = String(clamp(current + asNumber(qtyInput.step, 1)));
      qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  function initPdp() {
    clearLegacyAddToCartState();
    initTabs(document);

    document.querySelectorAll('form.cart').forEach(function (form) {
      initSwatches(form);
      enhanceQuantity(form);
      bindVariationPriceSync(form);
      bindAjaxAddToCart(form);
      addBuyNow(form);
    });
  }

  /**
   * Open a simple lightbox modal showing the full-size image. Used by the
   * fallback gallery's magnifier button. ESC or backdrop click closes.
   * Idempotent: closes any existing instance before opening a new one.
   */
  function openNoyonaPdpLightbox(src, alt) {
    if (!src) return;
    var prev = document.getElementById('noyona-pdp-lightbox');
    if (prev) prev.remove();

    var overlay = document.createElement('div');
    overlay.id = 'noyona-pdp-lightbox';
    overlay.className = 'noyona-pdp-lightbox';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Product image preview');

    var inner = document.createElement('div');
    inner.className = 'noyona-pdp-lightbox__inner';

    var img = document.createElement('img');
    img.className = 'noyona-pdp-lightbox__img';
    img.src = src;
    img.alt = alt || '';
    inner.appendChild(img);

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'noyona-pdp-lightbox__close';
    closeBtn.setAttribute('aria-label', 'Close image preview');
    closeBtn.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';

    overlay.appendChild(inner);
    overlay.appendChild(closeBtn);
    document.body.appendChild(overlay);
    document.documentElement.classList.add('noyona-pdp-lightbox-open');

    function close() {
      overlay.remove();
      document.documentElement.classList.remove('noyona-pdp-lightbox-open');
      document.removeEventListener('keydown', onKey);
    }
    function onKey(e) {
      if (e.key === 'Escape') close();
    }

    overlay.addEventListener('click', function (e) {
      if (e.target === overlay || e.target === inner) close();
    });
    closeBtn.addEventListener('click', close);
    document.addEventListener('keydown', onKey);

    // Defer focus to the close button for keyboard users.
    setTimeout(function () { closeBtn.focus(); }, 0);
  }

  /**
   * Self-contained PDP gallery fallback.
   *
   * Why this exists: in production we hit a state where wc-single-product.js
   * (and FlexSlider with it) does not initialize the gallery — the markup is
   * present but the figures stack vertically because FlexSlider never wraps
   * them in `.flex-viewport`. Causes vary by host (perf-plugin defer rules,
   * jQuery race, Woo block-template detection edge cases). Adding theme
   * supports + force-enqueue covers the common case but leaves a tail of
   * environments where it still fails.
   *
   * This fallback waits 400 ms after DOMContentLoaded — long enough for
   * FlexSlider to win if it can. If `.flex-viewport` is still absent we
   * rebuild the gallery DOM into a `noyona-pdp-gallery-main` viewport plus a
   * `noyona-pdp-gallery-thumbs` strip, wire click-to-swap, and listen for
   * `found_variation` so variation images still swap. We do NOT touch the
   * original `__wrapper` figures other than hiding the wrapper, so if WC
   * later runs FlexSlider through some delayed path, our overlay just sits
   * on top of an idle wrapper without interfering.
   */
  function initGalleryFallback() {
    var galleries = document.querySelectorAll('.single-product .woocommerce-product-gallery');
    galleries.forEach(function (gallery) {
      // FlexSlider already initialized — leave it alone.
      if (gallery.querySelector('.flex-viewport')) return;
      // Already converted on a prior run.
      if (gallery.dataset.noyonaFallback === '1') return;

      var wrapper = gallery.querySelector('.woocommerce-product-gallery__wrapper');
      if (!wrapper) return;

      var figures = Array.prototype.slice.call(
        wrapper.querySelectorAll('.woocommerce-product-gallery__image')
      );
      if (figures.length === 0) return;

      // Capture each image's URLs/attrs from the existing markup. WC stores
      // the full-size URL on `data-large_image` and the displayed src on
      // the `<img>` itself.
      var imageData = figures.map(function (figure) {
        var img = figure.querySelector('img');
        if (!img) return null;
        var anchor = figure.querySelector('a');
        var fullSrc = (anchor && anchor.getAttribute('href')) ||
                      img.getAttribute('data-large_image') ||
                      img.getAttribute('data-src') ||
                      img.getAttribute('src') || '';
        return {
          full:   fullSrc,
          thumb:  img.getAttribute('src') || fullSrc,
          srcset: img.getAttribute('srcset') || '',
          sizes:  img.getAttribute('sizes') || '',
          alt:    img.getAttribute('alt') || '',
          width:  img.getAttribute('width') || '',
          height: img.getAttribute('height') || '',
        };
      }).filter(Boolean);
      if (imageData.length === 0) return;

      gallery.dataset.noyonaFallback = '1';
      gallery.classList.add('noyona-pdp-gallery-fallback');

      // Build new DOM: <div main><div zoom><img></div><button magnify></div> + <ul thumbs>
      var main = document.createElement('div');
      main.className = 'noyona-pdp-gallery-main';

      var zoomWrap = document.createElement('div');
      zoomWrap.className = 'noyona-pdp-gallery-zoom';

      var mainImg = document.createElement('img');
      mainImg.className = 'noyona-pdp-gallery-main-img';
      mainImg.alt = imageData[0].alt;
      mainImg.decoding = 'async';
      mainImg.src = imageData[0].full;
      if (imageData[0].srcset) mainImg.srcset = imageData[0].srcset;
      if (imageData[0].sizes)  mainImg.sizes  = imageData[0].sizes;
      if (imageData[0].width)  mainImg.setAttribute('width', imageData[0].width);
      if (imageData[0].height) mainImg.setAttribute('height', imageData[0].height);
      zoomWrap.appendChild(mainImg);
      main.appendChild(zoomWrap);

      // Magnifier button (top-right) — opens lightbox of the current image.
      // Mirrors WooCommerce's `.woocommerce-product-gallery__trigger` so the
      // page reads the same in fallback and FlexSlider modes.
      var magnifyBtn = document.createElement('button');
      magnifyBtn.type = 'button';
      magnifyBtn.className = 'noyona-pdp-gallery-magnify';
      magnifyBtn.setAttribute('aria-label', 'View larger image');
      magnifyBtn.innerHTML = '<i class="fa-solid fa-magnifying-glass-plus" aria-hidden="true"></i>';
      main.appendChild(magnifyBtn);

      // Hover zoom: track cursor as % of the container, expose to CSS as
      // --zoom-x / --zoom-y. CSS handles the actual transform. Disabled on
      // touch devices via @media (hover: none) in the stylesheet.
      zoomWrap.addEventListener('mouseenter', function () {
        zoomWrap.classList.add('is-zooming');
      });
      zoomWrap.addEventListener('mouseleave', function () {
        zoomWrap.classList.remove('is-zooming');
        zoomWrap.style.removeProperty('--zoom-x');
        zoomWrap.style.removeProperty('--zoom-y');
      });
      zoomWrap.addEventListener('mousemove', function (e) {
        var rect = zoomWrap.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) return;
        var x = ((e.clientX - rect.left) / rect.width) * 100;
        var y = ((e.clientY - rect.top) / rect.height) * 100;
        zoomWrap.style.setProperty('--zoom-x', x + '%');
        zoomWrap.style.setProperty('--zoom-y', y + '%');
      });

      // Magnify click → lightbox. Helper is module-private, defined once
      // outside this loop (see openNoyonaPdpLightbox below).
      magnifyBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        openNoyonaPdpLightbox(mainImg.src, mainImg.alt);
      });

      var thumbs = document.createElement('ul');
      thumbs.className = 'noyona-pdp-gallery-thumbs';

      imageData.forEach(function (data, i) {
        var li = document.createElement('li');
        li.className = 'noyona-pdp-gallery-thumb' + (i === 0 ? ' is-active' : '');

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.setAttribute('data-noyona-thumb-index', String(i));
        btn.setAttribute('aria-label', 'Show product image ' + (i + 1));

        var thumbImg = document.createElement('img');
        thumbImg.src = data.thumb;
        thumbImg.alt = '';
        thumbImg.loading = 'lazy';
        thumbImg.decoding = 'async';
        btn.appendChild(thumbImg);
        li.appendChild(btn);
        thumbs.appendChild(li);
      });

      // Hide the original (unstyled, vertically stacked) wrapper without
      // removing it — so any late FlexSlider init still finds its target.
      wrapper.style.display = 'none';
      gallery.appendChild(main);
      // Only show the thumbnail strip when there's more than one image.
      if (imageData.length > 1) {
        gallery.appendChild(thumbs);
      }

      thumbs.addEventListener('click', function (event) {
        var btn = event.target.closest('button[data-noyona-thumb-index]');
        if (!btn) return;
        var idx = parseInt(btn.getAttribute('data-noyona-thumb-index'), 10);
        if (isNaN(idx) || !imageData[idx]) return;

        var data = imageData[idx];
        mainImg.src = data.full;
        mainImg.alt = data.alt;
        if (data.srcset) { mainImg.srcset = data.srcset; } else { mainImg.removeAttribute('srcset'); }
        if (data.sizes)  { mainImg.sizes  = data.sizes;  } else { mainImg.removeAttribute('sizes'); }

        thumbs.querySelectorAll('.noyona-pdp-gallery-thumb').forEach(function (item) {
          item.classList.remove('is-active');
        });
        var parentLi = btn.closest('.noyona-pdp-gallery-thumb');
        if (parentLi) parentLi.classList.add('is-active');
      });

      // Variation image swap: WC's add-to-cart-variation script fires
      // `found_variation` on the .variations_form. The variation object's
      // `.image` carries the variation-specific src/srcset/full URL.
      if (window.jQuery) {
        var $form = window.jQuery('form.variations_form');
        if ($form.length) {
          $form.on('found_variation.noyonaFallback', function (_event, variation) {
            if (!variation || !variation.image) return;
            var v = variation.image;
            var newSrc = v.full_src || v.src || '';
            if (!newSrc) return;
            mainImg.src = newSrc;
            if (v.alt) mainImg.alt = v.alt;
            if (v.srcset) { mainImg.srcset = v.srcset; } else { mainImg.removeAttribute('srcset'); }
            if (v.sizes)  { mainImg.sizes  = v.sizes;  } else { mainImg.removeAttribute('sizes'); }
          });
          $form.on('reset_image.noyonaFallback', function () {
            var first = imageData[0];
            if (!first) return;
            mainImg.src = first.full;
            mainImg.alt = first.alt;
            if (first.srcset) { mainImg.srcset = first.srcset; } else { mainImg.removeAttribute('srcset'); }
            if (first.sizes)  { mainImg.sizes  = first.sizes;  } else { mainImg.removeAttribute('sizes'); }
          });
        }
      }
    });
  }

  function scheduleGalleryFallback() {
    // Wait long enough for wc-single-product.js / FlexSlider to win on hosts
    // where they actually do initialize. On hosts where they don't, this
    // delay is invisible — the pre-init CSS layout is already in place.
    setTimeout(initGalleryFallback, 400);
  }

  bindWooCommercePdpHooks();

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPdp);
    document.addEventListener('DOMContentLoaded', scheduleGalleryFallback);
  } else {
    initPdp();
    scheduleGalleryFallback();
  }

  // Handle delayed Woo markup injections in block-based templates.
  if (typeof MutationObserver !== 'undefined' && document.body) {
    var t = null;
    var observer = new MutationObserver(function () {
      clearTimeout(t);
      t = setTimeout(initPdp, 100);
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }
})();