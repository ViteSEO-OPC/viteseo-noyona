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

  function formatShadeDisplayLabel(value) {
    var label = String(value || '').trim();
    var separatorIndex = label.indexOf('_');

    if (separatorIndex > -1) {
      label = label.slice(separatorIndex + 1).trim();
    }

    return label;
  }

  function normalizeImageMatchText(value) {
    var text = String(value || '');
    try {
      text = decodeURIComponent(text);
    } catch (e) {}
    if (typeof text.normalize === 'function') {
      text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }
    return text
      .toLowerCase()
      .replace(/\.[a-z0-9]{2,5}(?:\?.*)?$/i, '')
      .replace(/&/g, ' and ')
      .replace(/[_-]+/g, ' ')
      .replace(/[^a-z0-9]+/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function getImageBasename(url) {
    var clean = String(url || '').split('?')[0].split('#')[0];
    clean = clean.replace(/\/+$/, '');
    return clean.split('/').pop() || clean;
  }

  function normalizeImageBasenameForCompare(url) {
    var base = getImageBasename(url);
    if (!base) {
      return '';
    }

    try {
      base = decodeURIComponent(base);
    } catch (e) {}

    base = base
      .replace(/\.[a-z0-9]{2,5}$/i, '')
      // WordPress image sizes append dimensions to the same attachment slug.
      .replace(/-\d+x\d+$/i, '')
      .replace(/-scaled$/i, '');

    return normalizeImageMatchText(base);
  }

  function imageUrlsMatch(a, b) {
    var left = normalizeImageBasenameForCompare(a);
    var right = normalizeImageBasenameForCompare(b);
    return !!left && !!right && left === right;
  }

  var weakShadeImageWords = {
    and: true,
    color: true,
    colour: true,
    dark: true,
    eyeliner: true,
    eye: true,
    jet: true,
    light: true,
    liquid: true,
    noyona: true,
    pen: true,
    pencil: true,
    product: true,
    shade: true,
    the: true,
    tint: true,
    tone: true,
    waterproof: true,
  };

  function getShadeImageTokens(value, label) {
    var combined = normalizeImageMatchText([
      formatShadeDisplayLabel(label),
      formatShadeDisplayLabel(value),
      label,
      value
    ].join(' '));
    var seen = {};
    return combined.split(' ').filter(function (token) {
      if (!token || token.length < 2 || weakShadeImageWords[token] || seen[token]) {
        return false;
      }
      seen[token] = true;
      return true;
    });
  }

  function getShadeImagePhrases(value, label) {
    var candidates = [
      formatShadeDisplayLabel(label),
      formatShadeDisplayLabel(value),
      label,
      value
    ];
    var seen = {};

    return candidates
      .map(normalizeImageMatchText)
      .filter(function (phrase) {
        if (!phrase || phrase.length < 2 || seen[phrase]) {
          return false;
        }
        seen[phrase] = true;
        return true;
      });
  }

  function getSizeImageAliases(value, label) {
    var text = [value, label].join(' ');
    var matches = text.match(/\d+(?:\.\d+)?/g) || [];
    var numbers = matches
      .map(function (item) {
        return parseFloat(item);
      })
      .filter(function (item) {
        return !isNaN(item);
      });

    if (!numbers.length) {
      return [];
    }

    var largest = Math.max.apply(Math, numbers);

    if (largest <= 300) {
      return ['small'];
    }

    if (largest >= 500) {
      return ['big', 'large'];
    }

    return [];
  }

  function getPdpGallery() {
    return document.querySelector('.single-product .woocommerce-product-gallery');
  }

  function clearPdpOutOfStockImageState() {
    var gallery = getPdpGallery();
    if (!gallery) {
      return;
    }

    gallery
      .querySelectorAll(
        '.noyona-pdp-gallery-main.noyona-pdp-variation-out-of-stock,' +
          '.woocommerce-product-gallery__image.noyona-pdp-variation-out-of-stock'
      )
      .forEach(function (node) {
        node.classList.remove('noyona-pdp-variation-out-of-stock');
      });
  }

  function getActivePdpGalleryImageWrap(gallery) {
    if (!gallery) {
      return null;
    }
    return (
      gallery.querySelector('.woocommerce-product-gallery__image.flex-active-slide') ||
      gallery.querySelector('.woocommerce-product-gallery__image')
    );
  }

  function applyPdpOutOfStockImageState(isOutOfStock) {
    clearPdpOutOfStockImageState();
    if (!isOutOfStock) {
      return;
    }

    var gallery = getPdpGallery();
    if (!gallery) {
      return;
    }

    var fallbackMain = gallery.querySelector('.noyona-pdp-gallery-main');
    if (fallbackMain) {
      fallbackMain.classList.add('noyona-pdp-variation-out-of-stock');
      return;
    }

    var activeWrap = getActivePdpGalleryImageWrap(gallery);
    if (activeWrap) {
      activeWrap.classList.add('noyona-pdp-variation-out-of-stock');
    }
  }

  function getCurrentGalleryImageSrc(gallery) {
    if (!gallery) return '';
    var fallbackImg = gallery.querySelector('.noyona-pdp-gallery-main-img');
    if (fallbackImg) {
      return fallbackImg.currentSrc || fallbackImg.src || '';
    }
    var activeImg =
      gallery.querySelector('.woocommerce-product-gallery__image.flex-active-slide img') ||
      gallery.querySelector('.flex-active-slide img') ||
      gallery.querySelector('.woocommerce-product-gallery__image img');
    return activeImg ? (activeImg.currentSrc || activeImg.src || activeImg.getAttribute('data-large_image') || '') : '';
  }

  function collectGalleryImageData(gallery) {
    if (!gallery) return [];
    var wrapper = gallery.querySelector('.woocommerce-product-gallery__wrapper');
    var figures = wrapper
      ? Array.prototype.slice.call(wrapper.querySelectorAll('.woocommerce-product-gallery__image'))
      : [];
    return figures
      .filter(function (figure) {
        return !figure.classList.contains('clone');
      })
      .map(function (figure, index) {
        var img = figure.querySelector('img');
        if (!img) return null;
        var anchor = figure.querySelector('a');
        var fullSrc = (anchor && anchor.getAttribute('href')) ||
          img.getAttribute('data-large_image') ||
          img.getAttribute('data-src') ||
          img.getAttribute('src') || '';
        var title = img.getAttribute('title') ||
          img.getAttribute('data-caption') ||
          figure.getAttribute('data-thumb-alt') ||
          '';
        var fileText = normalizeImageMatchText(getImageBasename(fullSrc || img.getAttribute('src') || ''));
        var metaText = normalizeImageMatchText([
          fileText,
          img.getAttribute('alt') || '',
          title,
        ].join(' '));
        return {
          index: index,
          figure: figure,
          full: fullSrc,
          thumb: img.getAttribute('src') || fullSrc,
          srcset: img.getAttribute('srcset') || '',
          sizes: img.getAttribute('sizes') || '',
          alt: img.getAttribute('alt') || '',
          title: title,
          width: img.getAttribute('width') || '',
          height: img.getAttribute('height') || '',
          fileText: fileText,
          metaText: metaText,
        };
      })
      .filter(Boolean);
  }

  function findShadeGalleryMatch(gallery, value, label) {
    var phrases = getShadeImagePhrases(value, label);
    var sizeAliases = getSizeImageAliases(value, label);
    sizeAliases.forEach(function (alias) {
      var normalizedAlias = normalizeImageMatchText(alias);
      if (normalizedAlias && phrases.indexOf(normalizedAlias) === -1) {
        phrases.push(normalizedAlias);
      }
    });
    var tokens = getShadeImageTokens(value, label);
    if (!phrases.length && !tokens.length) return null;

    var images = collectGalleryImageData(gallery);
    var best = null;
    images.forEach(function (image) {
      var score = 0;
      phrases.forEach(function (phrase) {
        var fileText = (' ' + image.fileText + ' ');
        var metaText = (' ' + image.metaText + ' ');
        var phraseWords = phrase.split(' ').filter(Boolean).length;
        if (fileText.indexOf(' ' + phrase + ' ') !== -1) {
          score += 100 + phraseWords;
        } else if (image.fileText.indexOf(phrase) !== -1) {
          score += 80 + phraseWords;
        } else if (metaText.indexOf(' ' + phrase + ' ') !== -1) {
          score += 60 + phraseWords;
        } else if (image.metaText.indexOf(phrase) !== -1) {
          score += 40 + phraseWords;
        }
      });
      tokens.forEach(function (token) {
        var fileWords = (' ' + image.fileText + ' ');
        var metaWords = (' ' + image.metaText + ' ');
        if (fileWords.indexOf(' ' + token + ' ') !== -1) {
          score += 8;
        } else if (image.fileText.indexOf(token) !== -1) {
          score += 5;
        } else if (metaWords.indexOf(' ' + token + ' ') !== -1) {
          score += 4;
        } else if (image.metaText.indexOf(token) !== -1) {
          score += 2;
        }
      });
      if (score > 0 && (!best || score > best.score)) {
        best = { image: image, score: score };
      }
    });

    return best ? best.image : null;
  }

  function switchFallbackGalleryToImage(gallery, image) {
    if (!gallery || !image || typeof gallery._noyonaFallbackSwitchToIndex !== 'function') {
      return false;
    }
    return gallery._noyonaFallbackSwitchToIndex(image.index);
  }

  var galleryVariationSyncDepth = 0;

  function isGalleryVariationSyncSuppressed() {
    return galleryVariationSyncDepth > 0;
  }

  function runWithGalleryVariationSyncSuppressed(fn) {
    galleryVariationSyncDepth++;
    try {
      fn();
    } finally {
      galleryVariationSyncDepth--;
    }
  }

  function switchWooGalleryToImage(gallery, image) {
    if (!gallery || !image) return false;

    if (switchFallbackGalleryToImage(gallery, image)) {
      return true;
    }

    var thumbs = gallery.querySelectorAll('.flex-control-thumbs li img');
    if (thumbs[image.index]) {
      thumbs[image.index].dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
      return true;
    }

    if (window.jQuery) {
      var $gallery = window.jQuery(gallery);
      var slider = $gallery.data('flexslider');
      if (slider && typeof slider.flexAnimate === 'function') {
        slider.flexAnimate(image.index);
        return true;
      }
    }

    return false;
  }

  function getVariationImageUrls(variation) {
    var img = variation && variation.image ? variation.image : null;
    if (!img) {
      return [];
    }

    return [img.full_src, img.src, img.thumb_src, img.gallery_thumbnail_src, img.url].filter(
      Boolean
    );
  }

  function galleryImageMatchesVariation(image, variation) {
    if (!image || !variation) {
      return false;
    }

    var galleryUrls = [image.full, image.thumb].filter(Boolean);
    var variationUrls = getVariationImageUrls(variation);
    if (!galleryUrls.length || !variationUrls.length) {
      return false;
    }

    for (var gi = 0; gi < galleryUrls.length; gi++) {
      for (var vi = 0; vi < variationUrls.length; vi++) {
        if (imageUrlsMatch(galleryUrls[gi], variationUrls[vi])) {
          return true;
        }
      }
    }

    return false;
  }

  function scoreImageAgainstTokens(image, tokens) {
    if (!image || !tokens.length) {
      return 0;
    }

    var score = 0;
    tokens.forEach(function (token) {
      var fileWords = ' ' + image.fileText + ' ';
      var metaWords = ' ' + image.metaText + ' ';
      if (fileWords.indexOf(' ' + token + ' ') !== -1) {
        score += 8;
      } else if (image.fileText.indexOf(token) !== -1) {
        score += 5;
      } else if (metaWords.indexOf(' ' + token + ' ') !== -1) {
        score += 4;
      } else if (image.metaText.indexOf(token) !== -1) {
        score += 2;
      }
    });

    return score;
  }

  function findVariationForGalleryImage(form, image) {
    var variations = getInlineVariations(form);
    if (!variations || !image) {
      return null;
    }

    var urlMatch = null;
    variations.forEach(function (variation) {
      if (galleryImageMatchesVariation(image, variation)) {
        urlMatch = variation;
      }
    });
    if (urlMatch) {
      return urlMatch;
    }

    var best = null;
    variations.forEach(function (variation) {
      var attrs = (variation && variation.attributes) || {};
      Object.keys(attrs).forEach(function (attrKey) {
        var attrVal = attrs[attrKey];
        if (!attrVal) {
          return;
        }

        var tokens = getShadeImageTokens(attrVal, attrVal);
        var score = scoreImageAgainstTokens(image, tokens);
        if (score > 0 && (!best || score > best.score)) {
          best = { variation: variation, score: score };
        }
      });
    });

    return best ? best.variation : null;
  }

  function applyVariationAttributesFromVariation(form, variation) {
    if (!form || !variation || !variation.attributes) {
      return false;
    }

    var attrs = variation.attributes;
    var normalizedAttrMap = {};
    Object.keys(attrs).forEach(function (key) {
      normalizedAttrMap[normalizeAttributeKey(key)] = attrs[key];
    });

    var selects = Array.prototype.slice.call(
      form.querySelectorAll('select[name^="attribute_"]')
    );
    if (!selects.length) {
      return false;
    }

    var changed = false;
    selects.forEach(function (sel) {
      var name = sel.getAttribute('name') || '';
      var val = attrs[name];
      if (typeof val === 'undefined') {
        val = normalizedAttrMap[normalizeAttributeKey(name)];
      }
      if (!val) {
        return;
      }

      if (setSelectValueFromCustomUi(sel, val, null, val, name)) {
        changed = true;
      }
    });

    if (changed) {
      syncVariationId(form);
    }

    return changed;
  }

  function getActiveGalleryImageIndex(gallery, images) {
    if (!gallery || !images || !images.length) {
      return -1;
    }

    var activeThumbBtn = gallery.querySelector(
      '.noyona-pdp-gallery-thumb.is-active button[data-noyona-thumb-index]'
    );
    if (activeThumbBtn) {
      var thumbIndex = parseInt(activeThumbBtn.getAttribute('data-noyona-thumb-index'), 10);
      if (!isNaN(thumbIndex)) {
        return thumbIndex;
      }
    }

    var activeFigure = gallery.querySelector('.woocommerce-product-gallery__image.flex-active-slide');
    if (activeFigure) {
      for (var i = 0; i < images.length; i++) {
        if (images[i].figure === activeFigure) {
          return i;
        }
      }
    }

    var currentSrc = getCurrentGalleryImageSrc(gallery);
    if (currentSrc) {
      for (var j = 0; j < images.length; j++) {
        if (imageUrlsMatch(currentSrc, images[j].full || images[j].thumb)) {
          return j;
        }
      }
    }

    return 0;
  }

  function resolveGalleryImageFromEvent(gallery, event) {
    if (!gallery) {
      return null;
    }

    var images = collectGalleryImageData(gallery);
    if (!images.length) {
      return null;
    }

    if (event && event.target) {
      var thumbBtn = event.target.closest('button[data-noyona-thumb-index]');
      if (thumbBtn) {
        var thumbIdx = parseInt(thumbBtn.getAttribute('data-noyona-thumb-index'), 10);
        if (!isNaN(thumbIdx) && images[thumbIdx]) {
          return images[thumbIdx];
        }
      }

      var thumbImg = event.target.closest('.flex-control-thumbs li img');
      if (thumbImg) {
        var thumbList = Array.prototype.slice.call(
          gallery.querySelectorAll('.flex-control-thumbs li img')
        );
        var thumbListIndex = thumbList.indexOf(thumbImg);
        if (thumbListIndex > -1 && images[thumbListIndex]) {
          return images[thumbListIndex];
        }
      }
    }

    var activeIndex = getActiveGalleryImageIndex(gallery, images);
    return activeIndex > -1 && images[activeIndex] ? images[activeIndex] : images[0];
  }

  function syncVariationFromGallery(gallery, imageOverride) {
    if (isGalleryVariationSyncSuppressed()) {
      return;
    }

    var form = document.querySelector('.single-product form.variations_form');
    if (!form) {
      return;
    }

    gallery = gallery || getPdpGallery();
    if (!gallery) {
      return;
    }

    var image = imageOverride || resolveGalleryImageFromEvent(gallery, null);
    if (!image) {
      return;
    }

    var variation = findVariationForGalleryImage(form, image);
    if (!variation) {
      logVariationDebug('gallery-variation-sync-no-match', {
        imageFile: image.fileText || '',
        imageMeta: image.metaText || '',
      });
      return;
    }

    logVariationDebug('gallery-variation-sync', {
      variation_id: variation.variation_id || '',
      imageFile: image.fileText || '',
      imageMeta: image.metaText || '',
    });

    applyVariationAttributesFromVariation(form, variation);
  }

  function bindGalleryVariationSync(form) {
    if (!form || !form.classList.contains('variations_form')) {
      return;
    }
    if (form._noyonaGalleryVariationBound) {
      return;
    }

    var gallery = getPdpGallery();
    if (!gallery) {
      return;
    }

    form._noyonaGalleryVariationBound = true;

    function scheduleGalleryVariationSync(galleryNode, image) {
      window.setTimeout(function () {
        syncVariationFromGallery(galleryNode, image || null);
      }, 60);
    }

    gallery.addEventListener('click', function (event) {
      if (isGalleryVariationSyncSuppressed()) {
        return;
      }
      if (event.target.closest('.noyona-pdp-gallery-magnify')) {
        return;
      }

      var isThumbInteraction = !!event.target.closest(
        '.flex-control-thumbs li, .noyona-pdp-gallery-thumb, button[data-noyona-thumb-index]'
      );
      if (!isThumbInteraction) {
        return;
      }

      var image = resolveGalleryImageFromEvent(gallery, event);
      scheduleGalleryVariationSync(gallery, image);
    });

    if (typeof window.jQuery !== 'undefined') {
      var $gallery = window.jQuery(gallery);
      $gallery.on('click.noyonaGalleryVariation', '.flex-control-thumbs li', function () {
        if (isGalleryVariationSyncSuppressed()) {
          return;
        }
        scheduleGalleryVariationSync(gallery);
      });
      $gallery.on('after.noyonaGalleryVariation', function () {
        if (isGalleryVariationSyncSuppressed()) {
          return;
        }
        scheduleGalleryVariationSync(gallery);
      });
    }
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

  function isShadeGalleryAttributeSelect(select) {
    if (!select || !select.getAttribute) return false;
    if (isColorAttributeSelect(select)) return true;

    var name = (select.getAttribute('name') || '').toLowerCase();
    return /^attribute_(pa_)?(shade|swatch|tone|tint)$/.test(name);
  }

  function isGalleryFallbackAttributeSelect(select) {
    return isShadeGalleryAttributeSelect(select) || isSizePackAttributeSelect(select);
  }

  function getSelectedOptionText(select) {
    if (!select || select.selectedIndex < 0 || !select.options[select.selectedIndex]) {
      return '';
    }
    return String(select.options[select.selectedIndex].text || '').trim();
  }

  function getSelectedShadeSelect(form) {
    if (!form) return null;
    var selects = Array.prototype.slice.call(form.querySelectorAll('select[name^="attribute_"]'));
    var shadeSelect = selects.find(function (select) {
      return isShadeGalleryAttributeSelect(select) && !!select.value;
    });

    if (shadeSelect) {
      return shadeSelect;
    }

    return selects.find(function (select) {
      return isSizePackAttributeSelect(select) && !!select.value;
    }) || null;
  }

  function applyShadeGalleryFallback(form) {
    var shadeSelect = getSelectedShadeSelect(form);
    if (!shadeSelect) return;

    var gallery = getPdpGallery();
    if (!gallery) return;

    var currentSrc = getCurrentGalleryImageSrc(gallery);
    var match = findShadeGalleryMatch(gallery, shadeSelect.value, getSelectedOptionText(shadeSelect));
    if (!match) return;

    if (currentSrc && imageUrlsMatch(currentSrc, match.full || match.thumb)) {
      return;
    }

    runWithGalleryVariationSyncSuppressed(function () {
      switchWooGalleryToImage(gallery, match);
    });
  }

  function scheduleShadeGalleryFallback(form, beforeSrc) {
    if (!form || !form.classList.contains('variations_form')) return;
    [80, 260, 520].forEach(function (delay) {
      window.setTimeout(function () {
        applyShadeGalleryFallback(form, beforeSrc);
      }, delay);
    });
  }

  function bindShadeGalleryFallback(form) {
    if (!form || !form.classList.contains('variations_form') || form._noyonaShadeGalleryBound) {
      return;
    }

    var selects = Array.prototype.slice.call(form.querySelectorAll('select[name^="attribute_"]'))
      .filter(isGalleryFallbackAttributeSelect);
    if (!selects.length) return;

    form._noyonaShadeGalleryBound = true;
    selects.forEach(function (select) {
      select.addEventListener('change', function () {
        scheduleShadeGalleryFallback(form, getCurrentGalleryImageSrc(getPdpGallery()));
      });
    });

    if (window.jQuery) {
      window.jQuery(form).on('found_variation.noyonaShadeGallery show_variation.noyonaShadeGallery reset_image.noyonaShadeGallery', function () {
        scheduleShadeGalleryFallback(form, getCurrentGalleryImageSrc(getPdpGallery()));
      });
    }
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
      current.textContent = opt && opt.text ? formatShadeDisplayLabel(opt.text) : '';
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
    bindShadeGalleryFallback(form);
    bindGalleryVariationSync(form);
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

  function bindVariationStockSync(form) {
    if (!form || !form.classList.contains('variations_form')) {
      return;
    }
    if (typeof window.jQuery === 'undefined') {
      return;
    }

    var stockBadge = document.querySelector('.single-product .noyona-pdp-stock-shipping__stock');
    if (!stockBadge) {
      return;
    }

    if (!stockBadge.getAttribute('data-original-stock-label')) {
      stockBadge.setAttribute('data-original-stock-label', (stockBadge.textContent || '').trim());
      stockBadge.setAttribute('data-original-stock-class', stockBadge.className);
    }

    var $form = window.jQuery(form);
    if ($form.data('noyonaStockSyncBound')) {
      return;
    }
    $form.data('noyonaStockSyncBound', true);

    function getVariationStockCount(variation) {
      if (!variation) {
        return null;
      }
      var raw = variation.noyona_stock_quantity;
      if (raw === null || typeof raw === 'undefined' || raw === '') {
        raw = variation.max_qty;
      }
      if (raw === '' || raw === null || typeof raw === 'undefined') {
        return null;
      }
      var parsed = parseInt(raw, 10);
      return isFinite(parsed) && parsed > 0 ? parsed : null;
    }

    function formatInStockLabel(variation) {
      var stockCount = getVariationStockCount(variation);
      if (stockCount === null) {
        return getPdpText('inStock', 'In stock');
      }
      return getPdpText('inStockLeft', 'In stock (%d left)').replace('%d', String(stockCount));
    }

    function setStockBadgeFromVariation(variation) {
      if (!variation || typeof variation.is_in_stock === 'undefined') {
        return;
      }
      var inStock = !!variation.is_in_stock;
      stockBadge.textContent = inStock
        ? formatInStockLabel(variation)
        : getPdpText('outOfStockLeft', 'Out of stock (%d left)').replace('%d', '0');
      stockBadge.classList.toggle('noyona-pdp-stock-shipping__stock--in', inStock);
      stockBadge.classList.toggle('noyona-pdp-stock-shipping__stock--out', !inStock);
      applyPdpOutOfStockImageState(!inStock);
    }

    function resetStockBadgeLabel() {
      stockBadge.textContent = getPdpText(
        'selectOptionsAvailability',
        'Select options to see availability'
      );
      stockBadge.classList.remove('noyona-pdp-stock-shipping__stock--in');
      stockBadge.classList.remove('noyona-pdp-stock-shipping__stock--out');
      clearPdpOutOfStockImageState();
    }

    $form.on('found_variation show_variation', function (_event, variation) {
      setStockBadgeFromVariation(variation);
      window.setTimeout(function () {
        if (variation && typeof variation.is_in_stock !== 'undefined') {
          applyPdpOutOfStockImageState(!variation.is_in_stock);
        }
      }, 120);
    });

    $form.on('reset_data hide_variation', function () {
      resetStockBadgeLabel();
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

  function getPdpText(key, fallback) {
    if (typeof window.noyonaPdp !== 'undefined' && window.noyonaPdp.i18n && window.noyonaPdp.i18n[key]) {
      return window.noyonaPdp.i18n[key];
    }
    return fallback;
  }

  function showPdpToast(message, type) {
    var text = String(message || '').trim();
    if (!text) {
      return;
    }

    document.body.dispatchEvent(
      new CustomEvent('noyona_pdp_toast', {
        bubbles: true,
        detail: {
          message: text,
          type: type || 'error',
        },
      })
    );
  }

  function bindPdpAlertToasts() {
    if (window._noyonaPdpAlertToastBound) {
      return;
    }
    window._noyonaPdpAlertToastBound = true;
    window._noyonaNativeAlert = window.alert;
    window.alert = function (message) {
      // WooCommerce's variation script raises native alerts (e.g. "Please select
      // some product options…" / "Sorry, this product is unavailable…"). Route
      // them through the same normalizer so Add to Cart matches Buy Now.
      showPdpToast(normalizePdpMessage(message), 'error');
    };
  }

  function getPdpSelectOptionsMessage() {
    return getPdpText('selectOptions', 'Please select all product options before continuing.');
  }

  function getPdpOutOfStockMessage() {
    return getPdpText('outOfStockCartError', 'This product is out of stock.');
  }

  // Single source of truth for cart/variation error wording. Any message coming
  // from WooCommerce (native alert, variation script, AJAX response) is mapped
  // to our copy so both Add to Cart and Buy Now always say the same thing.
  function normalizePdpMessage(message) {
    var text = String(message || '');
    var lower = text.toLowerCase();
    if (!lower) {
      return text;
    }

    // Variation/options not chosen.
    if (
      lower.indexOf('select some product options') !== -1 ||
      lower.indexOf('select product options') !== -1 ||
      lower.indexOf('select all product options') !== -1 ||
      lower.indexOf('choose product options') !== -1 ||
      lower.indexOf('make a selection') !== -1
    ) {
      return getPdpSelectOptionsMessage();
    }

    // Out of stock / unavailable variation.
    if (
      lower.indexOf('out of stock') !== -1 ||
      lower.indexOf('cannot be purchased') !== -1 ||
      lower.indexOf('unavailable') !== -1 ||
      lower.indexOf('different combination') !== -1
    ) {
      return getPdpOutOfStockMessage();
    }

    return text;
  }

  function isPdpOutOfStockMessage(message) {
    return normalizePdpMessage(message) === getPdpOutOfStockMessage();
  }

  function getAjaxCartErrorMessage(data) {
    if (data && data.message) {
      return String(data.message);
    }
    if (data && data.data && data.data.message) {
      return String(data.data.message);
    }
    return getPdpText('cartError', 'This product cannot be added to cart right now.');
  }

  // Resolve the product/variation id that the current form would add to cart:
  // the selected variation id for variable products, otherwise the simple
  // product id. Used to look up how many are already in the cart.
  function getPdpSelectedProductId(form) {
    if (!form) {
      return 0;
    }
    if (form.classList.contains('variations_form')) {
      var variationId = form.querySelector('input[name="variation_id"]');
      if (variationId && variationId.value && variationId.value !== '0') {
        return parseInt(variationId.value, 10) || 0;
      }
    }
    var explicit =
      form.querySelector('input[name="add-to-cart"]') ||
      form.querySelector('button[name="add-to-cart"]') ||
      form.querySelector('input[name="product_id"]');
    if (explicit && explicit.value) {
      return parseInt(explicit.value, 10) || 0;
    }
    return 0;
  }

  // Current managed stock count for the active selection, or null when stock is
  // not managed (treated as unlimited). Variable products read the cached
  // current variation; simple products read the server-rendered badge.
  function getPdpCurrentStockCount(form) {
    if (form && form.classList.contains('variations_form') && form.__noyonaCurrentVariation) {
      var raw = form.__noyonaCurrentVariation.noyona_stock_quantity;
      if (raw === null || typeof raw === 'undefined' || raw === '') {
        raw = form.__noyonaCurrentVariation.max_qty;
      }
      var parsedVar = parseInt(raw, 10);
      return isFinite(parsedVar) && parsedVar >= 0 ? parsedVar : null;
    }

    var badge = getPdpStockBadge();
    if (badge) {
      var count = badge.getAttribute('data-noyona-stock-count');
      if (count !== null && count !== '') {
        var parsed = parseInt(count, 10);
        return isFinite(parsed) && parsed >= 0 ? parsed : null;
      }
    }
    return null;
  }

  // Whether the active selection is out of stock. For variable products this
  // depends on the chosen variation; if none is chosen yet we return false so
  // the "please select options" path handles it instead.
  function isPdpSelectionOutOfStock(form) {
    if (form && form.classList.contains('variations_form')) {
      if (form.__noyonaCurrentVariation && typeof form.__noyonaCurrentVariation.is_in_stock !== 'undefined') {
        return !form.__noyonaCurrentVariation.is_in_stock;
      }
      return false;
    }
    var badge = getPdpStockBadge();
    return !!(badge && badge.getAttribute('data-noyona-in-stock') === '0');
  }

  // How many of the given product/variation id are already in the cart, via the
  // Store API. Returns a promise resolving to a quantity (0 on any failure).
  function fetchPdpCartQuantity(productId) {
    var id = parseInt(productId, 10) || 0;
    if (!id) {
      return Promise.resolve(0);
    }
    return fetch('/wp-json/wc/store/cart?_t=' + Date.now(), {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
      cache: 'no-store',
    })
      .then(function (res) {
        return res.ok ? res.json() : null;
      })
      .then(function (cart) {
        if (!cart || !Array.isArray(cart.items)) {
          return 0;
        }
        var qty = 0;
        cart.items.forEach(function (item) {
          if (parseInt(item.id, 10) === id) {
            qty += parseInt(item.quantity, 10) || 0;
          }
        });
        return qty;
      })
      .catch(function () {
        return 0;
      });
  }

  // Pick the most specific message for a failed/blocked add based on stock vs.
  // how many are already in the cart.
  function buildPdpStockMessage(stockCount, cartQty, inStock) {
    if (!inStock) {
      return getPdpOutOfStockMessage();
    }
    if (stockCount !== null && cartQty >= stockCount) {
      return getPdpText(
        'maxInCart',
        'You already have all available stock (%d) of this item in your cart.'
      ).replace('%d', String(stockCount));
    }
    if (stockCount !== null) {
      return getPdpText(
        'notEnoughStock',
        'Only %1$d left in stock, and you already have %2$d in your cart.'
      )
        .replace('%1$d', String(stockCount))
        .replace('%2$d', String(cartQty));
    }
    return getPdpText('cartError', 'This product cannot be added to cart right now.');
  }

  // Cache the active variation on the form so the helpers above can read its
  // real stock without re-querying WooCommerce.
  function bindVariationCache(form) {
    if (!form || !form.classList.contains('variations_form') || typeof window.jQuery === 'undefined') {
      return;
    }
    var $form = window.jQuery(form);
    if ($form.data('noyonaVarCacheBound')) {
      return;
    }
    $form.data('noyonaVarCacheBound', true);
    $form.on('found_variation show_variation', function (_event, variation) {
      form.__noyonaCurrentVariation = variation || null;
    });
    $form.on('reset_data hide_variation', function () {
      form.__noyonaCurrentVariation = null;
    });
  }

  // Surface any WooCommerce error banner (e.g. a failed full-page Buy Now stock
  // rejection) as a persistent toast, then remove the raw banner so it does not
  // flash-and-vanish. Out-of-stock phrasing is normalized to our copy.
  function promotePdpErrorNoticesToToast() {
    var nodes = document.querySelectorAll(
      '.wp-block-woocommerce-store-notices .wc-block-components-notice-banner.is-error,' +
        '.woocommerce-notices-wrapper .woocommerce-error'
    );
    if (!nodes.length) {
      return;
    }
    var seen = {};
    nodes.forEach(function (node) {
      var text = normalizePdpMessage((node.textContent || '').trim());
      if (text) {
        if (!seen[text]) {
          seen[text] = true;
          showPdpToast(text, 'error');
        }
      }
      node.remove();
    });
  }

  function getPdpWishlistAjaxUrl() {
    if (typeof window.noyonaPdp !== 'undefined' && window.noyonaPdp.ajaxUrl) {
      return window.noyonaPdp.ajaxUrl;
    }
    return '/wp-admin/admin-ajax.php';
  }

  function getPdpWishlistNonce(button) {
    if (button && button.getAttribute('data-nonce')) {
      return button.getAttribute('data-nonce');
    }
    if (typeof window.noyonaPdp !== 'undefined' && window.noyonaPdp.wishlist && window.noyonaPdp.wishlist.nonce) {
      return window.noyonaPdp.wishlist.nonce;
    }
    return '';
  }

  function parsePdpWishlistKeys(button) {
    var raw = button ? button.getAttribute('data-saved-keys') : '';
    if (!raw) {
      return [];
    }
    try {
      var parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed.map(String) : [];
    } catch (e) {
      return [];
    }
  }

  function storePdpWishlistKeys(button, keys) {
    if (!button) {
      return;
    }
    var unique = [];
    keys.forEach(function (key) {
      key = String(key || '');
      if (key && unique.indexOf(key) === -1) {
        unique.push(key);
      }
    });
    button.setAttribute('data-saved-keys', JSON.stringify(unique));
  }

  function getPdpWishlistForm() {
    return document.querySelector('form.cart');
  }

  function isPdpWishlistVariableProduct(button, form) {
    var type = button ? String(button.getAttribute('data-product-type') || '').toLowerCase() : '';
    return type.indexOf('variable') !== -1 || !!(form && form.classList.contains('variations_form'));
  }

  function getPdpWishlistSelection(button) {
    var form = getPdpWishlistForm();
    var productId = parseInt(button.getAttribute('data-product-id') || '0', 10) || 0;
    var variationId = 0;
    var attributes = {};
    var labels = [];

    if (form) {
      form.querySelectorAll('select[name^="attribute_"]').forEach(function (select) {
        if (!select.name || !select.value) {
          return;
        }
        attributes[select.name] = select.value;
        var option = select.options[select.selectedIndex] || null;
        var optionText = option ? String(option.text || option.value || '').trim() : select.value;
        labels.push(getAttributeLabel(select) + ': ' + optionText);
      });

      var variationInput = form.querySelector('input[name="variation_id"]');
      variationId = variationInput ? parseInt(variationInput.value || '0', 10) || 0 : 0;
    }

    return {
      form: form,
      productId: productId,
      variationId: variationId,
      attributes: attributes,
      variationLabel: labels.join(' / '),
    };
  }

  function getPdpWishlistCurrentKey(button) {
    var selection = getPdpWishlistSelection(button);
    if (isPdpWishlistVariableProduct(button, selection.form) && selection.variationId < 1) {
      return '';
    }
    return String(selection.productId) + ':' + String(selection.variationId || 0);
  }

  function setPdpWishlistMessage(button, message, type) {
    var wrap = button ? button.closest('[data-noyona-pdp-wishlist-wrap]') : null;
    if (!wrap) {
      return;
    }

    var note = wrap.querySelector('.noyona-pdp-wishlist-message');
    if (!message) {
      if (note) {
        note.remove();
      }
      return;
    }

    if (!note) {
      note = document.createElement('span');
      note.className = 'noyona-pdp-wishlist-message';
      wrap.appendChild(note);
    }

    note.textContent = message;
    note.classList.toggle('is-error', type === 'error');
    note.classList.toggle('is-success', type === 'success');

    clearTimeout(button._noyonaWishlistMessageTimer);
    if (type !== 'error') {
      button._noyonaWishlistMessageTimer = setTimeout(function () {
        setPdpWishlistMessage(button, '', '');
      }, 2400);
    }
  }

  function setPdpWishlistButtonState(button, saved) {
    if (!button) {
      return;
    }

    var addLabel = button.getAttribute('data-label-add') || getPdpText('wishlistAdd', 'Add to wishlist');
    var removeLabel = button.getAttribute('data-label-remove') || getPdpText('wishlistRemove', 'Remove from wishlist');
    var label = saved ? removeLabel : addLabel;
    var icon = button.querySelector('i');
    var sr = button.querySelector('.screen-reader-text');

    button.classList.toggle('is-active', !!saved);
    button.setAttribute('aria-pressed', saved ? 'true' : 'false');
    button.setAttribute('aria-label', label);
    if (sr) {
      sr.textContent = label;
    }
    if (icon) {
      icon.classList.toggle('fa-solid', !!saved);
      icon.classList.toggle('fa-regular', !saved);
    }
  }

  function refreshPdpWishlistButtonState(button) {
    var key = getPdpWishlistCurrentKey(button);
    var saved = key ? parsePdpWishlistKeys(button).indexOf(key) !== -1 : false;
    setPdpWishlistButtonState(button, saved);
  }

  function handleLoggedOutPdpWishlistClick() {
    openGlobalCheckoutLoginModal(window.location.href, {
      title: getPdpText('wishlistLoginTitle', 'Log in to save your wishlist'),
      copy: getPdpText('wishlistLoginCopy', 'Please log in to save products and view them from My Account.'),
    });
  }

  function bindPdpWishlistFormEvents(button) {
    var form = getPdpWishlistForm();
    if (!form || form.getAttribute('data-noyona-wishlist-bound') === '1') {
      return;
    }

    form.setAttribute('data-noyona-wishlist-bound', '1');
    form.addEventListener('change', function () {
      refreshPdpWishlistButtonState(button);
      setPdpWishlistMessage(button, '', '');
    });

    if (window.jQuery) {
      window.jQuery(form).on('found_variation.noyonaWishlist reset_data.noyonaWishlist hide_variation.noyonaWishlist', function () {
        refreshPdpWishlistButtonState(button);
        setPdpWishlistMessage(button, '', '');
      });
    }
  }

  function bindPdpWishlistButton(button) {
    if (!button) {
      return;
    }

    bindPdpWishlistFormEvents(button);
    refreshPdpWishlistButtonState(button);

    if (button.getAttribute('data-noyona-bound') === '1') {
      return;
    }
    button.setAttribute('data-noyona-bound', '1');

    button.addEventListener('click', function () {
      if (!document.body.classList.contains('logged-in')) {
        handleLoggedOutPdpWishlistClick();
        return;
      }

      var selection = getPdpWishlistSelection(button);
      if (!selection.productId) {
        return;
      }

      if (isPdpWishlistVariableProduct(button, selection.form) && selection.variationId < 1) {
        setPdpWishlistMessage(
          button,
          getPdpText('wishlistSelectOptions', 'Please select a shade before saving this product.'),
          'error'
        );
        return;
      }

      if (button.classList.contains('is-loading')) {
        return;
      }

      var payload = new URLSearchParams();
      payload.set('action', 'noyona_toggle_product_wishlist');
      payload.set('nonce', getPdpWishlistNonce(button));
      payload.set('product_id', String(selection.productId));
      payload.set('variation_id', String(selection.variationId || 0));
      if (selection.variationLabel) {
        payload.set('variation_label', selection.variationLabel);
      }
      Object.keys(selection.attributes).forEach(function (key) {
        payload.set('attributes[' + key + ']', selection.attributes[key]);
      });

      button.classList.add('is-loading');
      button.disabled = true;

      fetch(getPdpWishlistAjaxUrl(), {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        },
        body: payload.toString(),
      })
        .then(function (response) {
          return response.json().catch(function () {
            return null;
          }).then(function (data) {
            return { ok: response.ok, data: data };
          });
        })
        .then(function (result) {
          if (!result || !result.ok || !result.data || !result.data.success) {
            var message = result && result.data && result.data.data ? result.data.data.message : '';
            if (message === 'not_logged_in') {
              handleLoggedOutPdpWishlistClick();
              return;
            }
            setPdpWishlistMessage(button, getPdpText('wishlistError', 'Wishlist could not be updated. Please try again.'), 'error');
            return;
          }

          var responseData = result.data.data || {};
          var saved = !!responseData.saved;
          var key = responseData.item_key || getPdpWishlistCurrentKey(button);
          var keys = parsePdpWishlistKeys(button);
          var keyIndex = keys.indexOf(key);
          if (saved && keyIndex === -1) {
            keys.push(key);
          } else if (!saved && keyIndex !== -1) {
            keys.splice(keyIndex, 1);
          }

          storePdpWishlistKeys(button, keys);
          setPdpWishlistButtonState(button, saved);
          setPdpWishlistMessage(
            button,
            saved
              ? getPdpText('wishlistSaved', 'Saved to your wishlist.')
              : getPdpText('wishlistRemoved', 'Removed from your wishlist.'),
            'success'
          );
        })
        .catch(function () {
          setPdpWishlistMessage(button, getPdpText('wishlistError', 'Wishlist could not be updated. Please try again.'), 'error');
        })
        .finally(function () {
          button.classList.remove('is-loading');
          button.disabled = false;
        });
    });
  }

  function initPdpWishlist() {
    document.querySelectorAll('[data-noyona-pdp-wishlist]').forEach(bindPdpWishlistButton);
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

    // Clear stale SUCCESS banners (e.g. legacy ?add-to-cart= flow), but do NOT
    // silently delete ERROR banners — a failed full-page Buy Now (out of stock /
    // already-at-max-in-cart) renders its reason there. Promote those to a
    // persistent toast instead so the shopper can actually read them.
    document
      .querySelectorAll(
        '.wp-block-woocommerce-store-notices .wc-block-components-notice-banner.is-success,' +
          '.woocommerce-notices-wrapper .woocommerce-message'
      )
      .forEach(function (notice) {
        notice.remove();
      });

    promotePdpErrorNoticesToToast();
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
    // Hard lock for Buy now: once a buy-now add is in progress we keep the
    // button locked until we either navigate to the cart (success) or hit an
    // error. This makes rapid clicks unable to queue extra cart lines.
    var buyNowInFlight = false;

    function setBuyNowLocked(locked) {
      buyNowInFlight = locked;
      var buyBtn = form.querySelector('.noyona-pdp-buy-now');
      if (!buyBtn) {
        return;
      }
      if (locked) {
        buyBtn.classList.add('loading');
        buyBtn.setAttribute('aria-disabled', 'true');
      } else {
        buyBtn.classList.remove('loading');
        buyBtn.removeAttribute('aria-disabled');
      }
    }

    function runAjaxAddToCart(options) {
      var isBuyNow = !!(options && options.buyNow);
      // Spam guard: ignore repeat Buy now clicks while one is already in
      // flight (the page navigates to the cart on success).
      if (isBuyNow && buyNowInFlight) {
        return;
      }
      addBtn = form.querySelector('.single_add_to_cart_button');
      if (form.classList.contains('variations_form')) {
        var currentVariationId = form.querySelector('input[name="variation_id"]');
        if (!currentVariationId || !currentVariationId.value || currentVariationId.value === '0') {
          showPdpToast(getPdpSelectOptionsMessage(), 'error');
          return;
        }
      }

      if (!addBtn) {
        return;
      }
      if (addBtn.disabled || addBtn.classList.contains('disabled') || isPdpSelectionOutOfStock(form)) {
        showPdpToast(getPdpOutOfStockMessage(), 'error');
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
          showPdpToast(getPdpSelectOptionsMessage(), 'error');
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
      if (isBuyNow) {
        setBuyNowLocked(true);
      }

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
            if (isBuyNow) {
              setBuyNowLocked(false);
            }
            addBtn.classList.add('is-error');
            // WooCommerce's AJAX add-to-cart error gives no useful reason for a
            // stock-limit rejection, and for an unavailable/out-of-stock
            // variation it returns its own "unavailable / choose a different
            // combination" copy. Normalize all out-of-stock/unavailable cases to
            // a single message, and compute the already-in-cart / not-enough
            // case from the live stock count vs. the cart quantity.
            var serverMsg = String((data && data.message) || (data && data.data && data.data.message) || '');
            var looksOutOfStock = isPdpSelectionOutOfStock(form) || isPdpOutOfStockMessage(serverMsg);

            if (looksOutOfStock) {
              showPdpToast(getPdpOutOfStockMessage(), 'error');
            } else {
              var stockCount = getPdpCurrentStockCount(form);
              fetchPdpCartQuantity(getPdpSelectedProductId(form)).then(function (cartQty) {
                showPdpToast(buildPdpStockMessage(stockCount, cartQty, true), 'error');
              });
            }
            return;
          }

          addBtn.classList.remove('is-error');
          addBtn.classList.remove('loading');
          addBtn.classList.add('added');

          // Buy now: go straight to the cart page (mirrors the old full-submit
          // redirect) instead of opening the mini-cart drawer. Navigate FIRST,
          // before the on-page cart-sync side effects below, so that if any of
          // them ever throws it cannot abort the redirect (which previously
          // left the item added but the shopper stranded on the PDP). The
          // buy-now lock stays engaged so the in-flight navigation can't be
          // interrupted by extra clicks.
          if (isBuyNow) {
            var cartUrl =
              (typeof window.noyonaHeader !== 'undefined' && window.noyonaHeader && window.noyonaHeader.cartUrl) ||
              '/cart/';
            window.location.assign(cartUrl);
            return;
          }

          var isBuySheetAdd = !!(form.closest && form.closest('[data-noyona-buysheet]'));

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

          // Buy-sheet adds (PDP mobile + listing): sheet closes + toast only — no drawer.
          // Capture isBuySheetAdd before added_to_cart teardown removes listing form nodes.
          if (
            !isBuySheetAdd &&
            window.noyonaCartFx &&
            typeof window.noyonaCartFx.openDrawer === 'function'
          ) {
            window.noyonaCartFx.openDrawer({ delay: 120 });
          }
        })
        .catch(function () {
          // Do not fallback-submit the form: this causes reload + duplicate cart lines.
          if (isBuyNow) {
            setBuyNowLocked(false);
          }
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
    if (noyonaIsListingBuySheet(form && form.closest('[data-noyona-buysheet]'))) {
      return;
    }

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
          copy: 'Please log in to your account to continue checkout.',
        });
        return;
      }

      if (form.classList.contains('variations_form')) {
        var variationId = form.querySelector('input[name="variation_id"]');
        if (!variationId || !variationId.value || variationId.value === '0') {
          showPdpToast(getPdpSelectOptionsMessage(), 'error');
          return;
        }
      }

      if (
        buy.disabled ||
        buy.classList.contains('disabled') ||
        addBtn.disabled ||
        addBtn.classList.contains('disabled') ||
        isPdpSelectionOutOfStock(form)
      ) {
        showPdpToast(getPdpOutOfStockMessage(), 'error');
        return;
      }

      // Buy now adds to cart via AJAX (then redirects to the cart on success),
      // so a stock rejection surfaces as a toast instead of a full-page reload
      // that briefly flashes a WooCommerce error banner.
      if (typeof form.__noyonaRunAjaxAddToCart === 'function') {
        form.__noyonaRunAjaxAddToCart({ buyNow: true });
        return;
      }

      // Fallback (AJAX binding unavailable): legacy full-page buy-now submit.
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

  function getSimplePdpCartRoot() {
    return (
      document.querySelector('.single-product form.cart') ||
      document.querySelector('.single-product .wp-block-add-to-cart-form')
    );
  }

  function createPdpQuantityField() {
    var wrap = document.createElement('div');
    wrap.className = 'quantity';

    var input = document.createElement('input');
    input.type = 'number';
    input.className = 'input-text qty text';
    input.name = 'quantity';
    input.value = '1';
    input.min = '1';
    input.step = '1';
    input.setAttribute('inputmode', 'numeric');
    wrap.appendChild(input);

    return wrap;
  }

  function setPdpQuantityDisabled(qtyWrap, disabled) {
    if (!qtyWrap) {
      return;
    }

    var qtyInput = qtyWrap.querySelector('.qty');
    if (qtyInput) {
      qtyInput.disabled = !!disabled;
      if (disabled) {
        qtyInput.setAttribute('aria-disabled', 'true');
      } else {
        qtyInput.removeAttribute('aria-disabled');
      }
    }

    qtyWrap.querySelectorAll('.noyona-pdp-qty__btn').forEach(function (button) {
      button.disabled = !!disabled;
      if (disabled) {
        button.setAttribute('aria-disabled', 'true');
      } else {
        button.removeAttribute('aria-disabled');
      }
    });
  }

  function enhanceQuantity(root) {
    if (!root) {
      return null;
    }
    var qtyWrap = root.querySelector('.quantity');
    var qtyInput = qtyWrap ? qtyWrap.querySelector('.qty') : null;
    if (!qtyWrap || !qtyInput || qtyWrap.classList.contains('noyona-pdp-qty')) {
      return qtyWrap || null;
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

    return qtyWrap;
  }

  function ensureSimplePdpQuantity() {
    var stockBadge = getPdpStockBadge();
    if (!stockBadge || stockBadge.getAttribute('data-noyona-product-type') !== 'simple') {
      return;
    }

    var root = getSimplePdpCartRoot();
    if (!root) {
      return;
    }

    var qtyWrap = root.querySelector('.quantity');
    if (!qtyWrap && isSimpleProductOutOfStock()) {
      return;
    }

    if (!qtyWrap) {
      qtyWrap = createPdpQuantityField();
      var actions = root.querySelector('.noyona-pdp-cart-actions, .noyona-pdp-cart-actions--unavailable');
      if (actions) {
        root.insertBefore(qtyWrap, actions);
      } else {
        root.appendChild(qtyWrap);
      }
    }

    enhanceQuantity(root);

    if (isSimpleProductOutOfStock()) {
      setPdpQuantityDisabled(root.querySelector('.quantity.noyona-pdp-qty'), true);
    }
  }

  /* ===================================================================
   * Mobile/tablet sticky buy bar + slide-up buy sheet (Strategy A).
   *
   * The real `form.cart` is RELOCATED into the sheet on viewports <= 767px
   * and RESTORED to its original DOM position on wider viewports. No form is
   * cloned and no variation/add-to-cart logic is duplicated: every existing
   * binding (swatches, quantity stepper, variation price sync, AJAX add to
   * cart, buy-now) lives on the form node itself and survives the move.
   * ================================================================= */

  var NOYONA_BUYSHEET_MQ = '(max-width: 767px)';
  var noyonaBuySheetLastFocus = null;
  var noyonaBuySheetKeyHandler = null;
  var noyonaArchiveBuySheetInFlight = false;
  var noyonaArchiveBuySheetAbortController = null;

  /* Listing Buy Sheet Cache — shop/listing variable-form payload cache (Phase 1). */
  var NOYONA_LISTING_BUYSHEET_CACHE_TTL_MS = 5 * 60 * 1000;
  var NOYONA_LISTING_BUYSHEET_CACHE_MAX = 25;
  var noyonaListingBuySheetFormCache = new Map();
  var noyonaListingBuySheetFetchPromises = new Map();

  function getListingBuySheetCachedPayload(productId) {
    productId = parseInt(productId, 10) || 0;
    if (productId < 1 || !noyonaListingBuySheetFormCache.has(productId)) {
      return null;
    }

    var entry = noyonaListingBuySheetFormCache.get(productId);
    if (!entry || Date.now() - entry.fetchedAt > NOYONA_LISTING_BUYSHEET_CACHE_TTL_MS) {
      noyonaListingBuySheetFormCache.delete(productId);
      return null;
    }

    // Touch entry for LRU ordering.
    noyonaListingBuySheetFormCache.delete(productId);
    noyonaListingBuySheetFormCache.set(productId, entry);
    return entry;
  }

  function storeListingBuySheetCache(productId, payload) {
    productId = parseInt(productId, 10) || 0;
    if (productId < 1 || !payload || !payload.formHtml) {
      return;
    }

    var entry = {
      productId: productId,
      formHtml: payload.formHtml,
      header: payload.header ? Object.assign({}, payload.header) : null,
      fetchedAt: Date.now(),
    };

    if (noyonaListingBuySheetFormCache.has(productId)) {
      noyonaListingBuySheetFormCache.delete(productId);
    }
    noyonaListingBuySheetFormCache.set(productId, entry);

    while (noyonaListingBuySheetFormCache.size > NOYONA_LISTING_BUYSHEET_CACHE_MAX) {
      var oldestKey = noyonaListingBuySheetFormCache.keys().next().value;
      noyonaListingBuySheetFormCache.delete(oldestKey);
    }
  }

  function normalizeListingBuySheetPayload(json, productId) {
    if (!json || !json.success || !json.data || !json.data.formHtml) {
      var message =
        (json && json.data && json.data.message) ||
        getPdpText('buySheetLoadError', 'Unable to load product options. Please try again.');
      throw { error: message };
    }

    return {
      productId: json.data.productId || productId,
      formHtml: json.data.formHtml,
      header: json.data.header ? Object.assign({}, json.data.header) : null,
      fetchedAt: Date.now(),
    };
  }

  function injectListingBuySheetPayload(productId, payload) {
    var sheet = getNoyonaBuySheet();
    var slot = getNoyonaBuySheetFormSlot();
    if (!sheet || !slot || !payload || !payload.formHtml) {
      throw { error: getPdpText('buySheetLoadError', 'Unable to load product options. Please try again.') };
    }

    var wrapper = document.createElement('div');
    wrapper.innerHTML = payload.formHtml;
    var form = wrapper.querySelector('form.variations_form, form.cart');
    if (!form) {
      throw { error: getPdpText('buySheetLoadError', 'Unable to load product options. Please try again.') };
    }

    var blockWrapper = document.createElement('div');
    blockWrapper.className = 'wp-block-add-to-cart-form wc-block-add-to-cart-form';
    blockWrapper.appendChild(form);
    slot.appendChild(blockWrapper);

    if (typeof window.jQuery !== 'undefined' && window.jQuery.fn.wc_variation_form) {
      window.jQuery(form).wc_variation_form();
    }

    initPdp();

    if (payload.header) {
      var header = Object.assign({}, payload.header);
      header.productId = payload.productId || productId;
      populateBuySheetHeaderFromMeta(header);
    }

    refreshNoyonaBuySheetHeader();
  }

  function fetchListingBuySheetPayload(productId, options) {
    options = options || {};
    productId = parseInt(productId, 10) || 0;
    if (productId < 1) {
      return Promise.reject({ error: getPdpText('buySheetLoadError', 'Unable to load product options. Please try again.') });
    }

    var cached = getListingBuySheetCachedPayload(productId);
    if (cached) {
      return Promise.resolve(cached);
    }

    if (noyonaListingBuySheetFetchPromises.has(productId)) {
      return noyonaListingBuySheetFetchPromises.get(productId);
    }

    var config = getNoyonaBuySheetConfig();
    if (!config || !config.enabled) {
      return Promise.reject({ error: getPdpText('buySheetLoadError', 'Unable to load product options. Please try again.') });
    }

    var payload = new URLSearchParams();
    payload.append('action', config.action || 'noyona_buy_sheet_variable_form');
    payload.append('nonce', config.nonce || '');
    payload.append('product_id', String(productId));

    var fetchOptions = {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: payload.toString(),
    };
    if (options.signal) {
      fetchOptions.signal = options.signal;
    }

    var requestPromise = fetch(config.ajaxUrl || getPdpWishlistAjaxUrl(), fetchOptions)
      .then(function (response) {
        return response.json();
      })
      .then(function (json) {
        var normalized = normalizeListingBuySheetPayload(json, productId);
        storeListingBuySheetCache(productId, normalized);
        return getListingBuySheetCachedPayload(productId) || normalized;
      })
      .finally(function () {
        noyonaListingBuySheetFetchPromises.delete(productId);
      });

    noyonaListingBuySheetFetchPromises.set(productId, requestPromise);
    return requestPromise;
  }

  function prefetchListingBuySheetForm(productId) {
    var sheet = getNoyonaBuySheet();
    if (!sheet || !noyonaIsListingBuySheet(sheet)) {
      return Promise.resolve(null);
    }

    productId = parseInt(productId, 10) || 0;
    if (productId < 1) {
      return Promise.resolve(null);
    }

    if (getListingBuySheetCachedPayload(productId)) {
      return Promise.resolve(null);
    }

    return fetchListingBuySheetPayload(productId).catch(function () {
      return null;
    });
  }

  function noyonaIsMobileViewport() {
    return typeof window.matchMedia === 'function' && window.matchMedia(NOYONA_BUYSHEET_MQ).matches;
  }

  function getNoyonaBuySheet() {
    return document.querySelector('[data-noyona-buysheet]');
  }

  function noyonaIsListingBuySheet(sheet) {
    sheet = sheet || getNoyonaBuySheet();
    return !!(sheet && sheet.getAttribute('data-noyona-buysheet-context') === 'listing');
  }

  function getNoyonaBuySheetFormSlot() {
    var sheet = getNoyonaBuySheet();
    return sheet ? sheet.querySelector('[data-noyona-buysheet-form-slot]') : null;
  }

  function getNoyonaBuySheetLoadingNode() {
    var sheet = getNoyonaBuySheet();
    return sheet ? sheet.querySelector('[data-noyona-buysheet-loading]') : null;
  }

  function setNoyonaBuySheetLoading(isLoading) {
    var loading = getNoyonaBuySheetLoadingNode();
    if (!loading) {
      return;
    }
    if (isLoading) {
      loading.removeAttribute('hidden');
    } else {
      loading.setAttribute('hidden', '');
    }
  }

  function getNoyonaBuySheetConfig() {
    if (typeof window.noyonaPdp !== 'undefined' && window.noyonaPdp.buySheet) {
      return window.noyonaPdp.buySheet;
    }
    return null;
  }

  function populateBuySheetHeaderFromMeta(meta) {
    var sheet = getNoyonaBuySheet();
    if (!sheet || !meta) {
      return;
    }

    sheet._noyonaArchiveHeaderMeta = meta;
    sheet.setAttribute('data-noyona-active-product-id', meta.productId ? String(meta.productId) : '');

    var titleTarget = sheet.querySelector('[data-noyona-buysheet-title]');
    if (titleTarget && meta.title) {
      titleTarget.textContent = meta.title;
    }

    var thumbTarget = sheet.querySelector('[data-noyona-buysheet-thumb]');
    if (thumbTarget && meta.thumbHtml) {
      thumbTarget.innerHTML = meta.thumbHtml;
    }

    var priceTarget = sheet.querySelector('[data-noyona-buysheet-price]');
    if (priceTarget && meta.priceHtml) {
      priceTarget.innerHTML = meta.priceHtml;
    }

    var stockTarget = sheet.querySelector('[data-noyona-buysheet-stock]');
    if (stockTarget) {
      var stockText = meta.stockHtml || '';
      stockTarget.textContent = stockText;
      stockTarget.hidden = !stockText;
      if (meta.stockClass) {
        stockTarget.className = 'noyona-pdp-buysheet__stock ' + meta.stockClass;
      }
    }
  }

  function destroyArchiveBuySheetForm(form) {
    if (!form) {
      return;
    }

    if (typeof window.jQuery !== 'undefined') {
      var $form = window.jQuery(form);
      $form.off('.noyonaBuySheet .noyonaBuyGuard');
      try {
        if ($form.data('wc_variation_form') && $form.wc_variation_form) {
          $form.wc_variation_form('destroy');
        }
      } catch (e) {
        /* WC destroy unsupported or already torn down */
      }
    }

    form.removeAttribute('data-noyona-ajax-cart-bound');
    delete form.__noyonaRunAjaxAddToCart;
    delete form._noyonaInteractionBound;
    delete form._noyonaVariationSyncBound;
    delete form._noyonaUserSelectedVariation;
    delete form.__noyonaCurrentVariation;
  }

  function teardownArchiveBuySheet() {
    var sheet = getNoyonaBuySheet();
    if (!sheet || !noyonaIsListingBuySheet(sheet)) {
      return;
    }

    var slot = getNoyonaBuySheetFormSlot();
    if (!slot) {
      return;
    }

    var form = slot.querySelector('form.cart');
    if (form) {
      destroyArchiveBuySheetForm(form);
    }

    slot.querySelectorAll('.wp-block-add-to-cart-form, form.cart').forEach(function (node) {
      node.remove();
    });

    setNoyonaBuySheetLoading(false);
    delete sheet._noyonaArchiveHeaderMeta;
    sheet.removeAttribute('data-noyona-active-product-id');

    var variantTarget = sheet.querySelector('[data-noyona-buysheet-variant]');
    if (variantTarget) {
      variantTarget.textContent = '';
    }

    var stockTarget = sheet.querySelector('[data-noyona-buysheet-stock]');
    if (stockTarget) {
      stockTarget.textContent = '';
      stockTarget.hidden = true;
    }
  }

  function openListingBuySheetUi() {
    var sheet = getNoyonaBuySheet();
    if (!sheet) {
      return;
    }

    noyonaBuySheetLastFocus = document.activeElement;
    sheet.removeAttribute('hidden');
    sheet.classList.add('is-open');
    document.documentElement.classList.add('noyona-pdp-buysheet-open');
    document.body.classList.add('noyona-pdp-buysheet-open');

    refreshNoyonaBuySheetHeader();

    var closeBtn = sheet.querySelector('[data-noyona-buysheet-close]');
    window.setTimeout(function () {
      if (closeBtn) {
        closeBtn.focus();
      }
    }, 0);

    if (!noyonaBuySheetKeyHandler) {
      noyonaBuySheetKeyHandler = function (event) {
        if (event.key === 'Escape' || event.key === 'Esc') {
          closeNoyonaBuySheet();
        }
      };
      document.addEventListener('keydown', noyonaBuySheetKeyHandler);
    }
  }

  function openArchiveBuySheet(productId, sourceButton) {
    var sheet = getNoyonaBuySheet();
    var config = getNoyonaBuySheetConfig();

    if (!sheet || !noyonaIsListingBuySheet(sheet) || !config || !config.enabled) {
      return Promise.reject({ error: getPdpText('buySheetLoadError', 'Unable to load product options. Please try again.') });
    }

    if (noyonaArchiveBuySheetInFlight) {
      return Promise.reject({ error: getPdpText('cartError', 'This product cannot be added to cart right now.') });
    }

    productId = parseInt(productId, 10) || 0;
    if (productId < 1) {
      return Promise.reject({ error: getPdpText('buySheetLoadError', 'Unable to load product options. Please try again.') });
    }

    if (sourceButton && typeof sourceButton.focus === 'function') {
      noyonaBuySheetLastFocus = sourceButton;
    }

    noyonaArchiveBuySheetInFlight = true;
    teardownArchiveBuySheet();
    setNoyonaBuySheetLoading(true);
    openListingBuySheetUi();

    var abortController = typeof AbortController !== 'undefined' ? new AbortController() : null;
    noyonaArchiveBuySheetAbortController = abortController;

    return fetchListingBuySheetPayload(productId, {
      signal: abortController ? abortController.signal : null,
    })
      .then(function (payload) {
        if (!sheet.classList.contains('is-open')) {
          return;
        }

        injectListingBuySheetPayload(productId, payload);
        setNoyonaBuySheetLoading(false);
        return payload;
      })
      .catch(function (error) {
        if (error && error.name === 'AbortError') {
          return;
        }

        if (sheet.classList.contains('is-open')) {
          closeNoyonaBuySheet(true);
        } else {
          setNoyonaBuySheetLoading(false);
        }

        if (error && error.error) {
          throw error;
        }
        throw {
          error: getPdpText('buySheetLoadError', 'Unable to load product options. Please try again.'),
        };
      })
      .finally(function () {
        noyonaArchiveBuySheetInFlight = false;
        noyonaArchiveBuySheetAbortController = null;
      });
  }

  function getNoyonaBuyBar() {
    return document.querySelector('[data-noyona-buybar]');
  }

  function getNoyonaPdpCartForm() {
    return document.querySelector('.single-product form.cart, body.single-product form.cart');
  }

  function getNoyonaPdpCartSheetTarget() {
    var simpleBlock = document.querySelector('.single-product .wp-block-add-to-cart-form');
    var simpleRoot = simpleBlock || getSimplePdpCartRoot();
    var stockBadge = getPdpStockBadge();
    if (stockBadge && stockBadge.getAttribute('data-noyona-product-type') === 'simple' && simpleBlock) {
      return simpleBlock;
    }
    return getNoyonaPdpCartForm() || simpleRoot;
  }

  function getNoyonaMainPriceNode() {
    return document.querySelector(
      '.single-product .wp-block-woocommerce-product-price .wc-block-components-product-price'
    );
  }

  /**
   * Move the real form.cart into the sheet on mobile, or back to its original
   * spot on desktop. Idempotent: only touches the DOM when the form is not
   * already where it should be. A comment node marks the original position so
   * restoration is exact even if sibling nodes change.
   */
  function relocateNoyonaFormForViewport() {
    if (noyonaIsListingBuySheet()) {
      return;
    }

    var form = getNoyonaPdpCartSheetTarget();
    var sheet = getNoyonaBuySheet();
    if (!form || !sheet) {
      return;
    }
    var slot = sheet.querySelector('[data-noyona-buysheet-form-slot]');
    if (!slot) {
      return;
    }

    if (!form._noyonaBuysheetOrigin) {
      var marker = document.createComment('noyona-buysheet-form-origin');
      form.parentNode.insertBefore(marker, form);
      form._noyonaBuysheetOrigin = marker;
      form._noyonaOriginWrapper = form.parentNode;
      form._noyonaHideOriginWrapper = form.matches && form.matches('form.cart');
    }

    if (noyonaIsMobileViewport()) {
      if (form.parentNode !== slot) {
        slot.appendChild(form);
      }
      if (form._noyonaHideOriginWrapper && form._noyonaOriginWrapper) {
        form._noyonaOriginWrapper.classList.add('noyona-pdp-form-relocated');
      }
    } else {
      var origin = form._noyonaBuysheetOrigin;
      if (origin && origin.parentNode && form.parentNode !== origin.parentNode) {
        origin.parentNode.insertBefore(form, origin.nextSibling);
      }
      if (form._noyonaHideOriginWrapper && form._noyonaOriginWrapper) {
        form._noyonaOriginWrapper.classList.remove('noyona-pdp-form-relocated');
      }
      // Never leave the sheet open on desktop.
      closeNoyonaBuySheet(true);
    }
  }

  /**
   * Mirror the live price + selected variant text into the sheet header.
   * Reads the same nodes the rest of the PDP keeps up to date, so it stays in
   * sync with WooCommerce variation events without owning any state.
   */
  function refreshNoyonaBuySheetHeader() {
    var sheet = getNoyonaBuySheet();
    if (!sheet) {
      return;
    }

    var isListing = noyonaIsListingBuySheet(sheet);
    var slot = getNoyonaBuySheetFormSlot();
    var form = slot ? slot.querySelector('form.variations_form, form.cart') : null;

    var priceTarget = sheet.querySelector('[data-noyona-buysheet-price]');
    if (priceTarget) {
      if (!isListing) {
        var mainPrice = getNoyonaMainPriceNode();
        if (mainPrice) {
          priceTarget.innerHTML = mainPrice.innerHTML;
        }
      } else if (form && form.__noyonaCurrentVariation && form.__noyonaCurrentVariation.price_html) {
        priceTarget.innerHTML = form.__noyonaCurrentVariation.price_html;
      } else if (sheet._noyonaArchiveHeaderMeta && sheet._noyonaArchiveHeaderMeta.priceHtml) {
        priceTarget.innerHTML = sheet._noyonaArchiveHeaderMeta.priceHtml;
      }
    }

    var variantTarget = sheet.querySelector('[data-noyona-buysheet-variant]');
    if (variantTarget) {
      var parts = [];
      var variantScope = isListing && slot ? slot : document;
      variantScope
        .querySelectorAll('.noyona-pdp-variation__shade-current, .noyona-pdp-variation__field-current')
        .forEach(function (node) {
          var txt = (node.textContent || '').trim();
          if (txt) {
            parts.push(txt);
          }
        });
      variantTarget.textContent = parts.join(' / ');
    }

    var stockTarget = sheet.querySelector('[data-noyona-buysheet-stock]');
    if (isListing && stockTarget && form && form.__noyonaCurrentVariation) {
      var listingVariation = form.__noyonaCurrentVariation;
      if (typeof listingVariation.is_in_stock !== 'undefined') {
        var listingInStock = !!listingVariation.is_in_stock;
        var listingStockCount = listingVariation.noyona_stock_quantity;
        if (listingStockCount === null || typeof listingStockCount === 'undefined' || listingStockCount === '') {
          listingStockCount = listingVariation.max_qty;
        }
        stockTarget.textContent = listingInStock
          ? (listingStockCount
              ? getPdpText('inStockLeft', 'In stock (%d left)').replace('%d', String(listingStockCount))
              : getPdpText('inStock', 'In stock'))
          : getPdpText('outOfStock', 'Out of stock');
        stockTarget.hidden = false;
        stockTarget.classList.toggle('noyona-pdp-stock-shipping__stock--in', listingInStock);
        stockTarget.classList.toggle('noyona-pdp-stock-shipping__stock--out', !listingInStock);
        return;
      }
    }

    var stockSource = getPdpStockBadge();
    if (stockTarget && stockSource) {
      var stockText = (stockSource.textContent || '').trim();
      stockTarget.textContent = stockText;
      stockTarget.hidden = !stockText;
      stockTarget.className = 'noyona-pdp-buysheet__stock ' + stockSource.className;
      stockTarget.setAttribute('data-noyona-in-stock', stockSource.getAttribute('data-noyona-in-stock') || '');
      stockTarget.setAttribute('data-noyona-stock-count', stockSource.getAttribute('data-noyona-stock-count') || '');
    } else if (stockTarget && isListing) {
      var fallbackStock =
        sheet._noyonaArchiveHeaderMeta && sheet._noyonaArchiveHeaderMeta.stockHtml
          ? sheet._noyonaArchiveHeaderMeta.stockHtml
          : '';
      stockTarget.textContent = fallbackStock;
      stockTarget.hidden = !fallbackStock;
    } else if (stockTarget) {
      stockTarget.textContent = '';
      stockTarget.hidden = true;
    }
  }

  function openNoyonaBuySheet() {
    var sheet = getNoyonaBuySheet();
    if (!sheet || !noyonaIsMobileViewport()) {
      return;
    }

    // Make sure the form lives in the sheet before showing it.
    relocateNoyonaFormForViewport();

    noyonaBuySheetLastFocus = document.activeElement;

    sheet.removeAttribute('hidden');
    sheet.classList.add('is-open');
    document.documentElement.classList.add('noyona-pdp-buysheet-open');
    document.body.classList.add('noyona-pdp-buysheet-open');

    refreshNoyonaBuySheetHeader();

    var closeBtn = sheet.querySelector('[data-noyona-buysheet-close]');
    window.setTimeout(function () {
      if (closeBtn) {
        closeBtn.focus();
      }
    }, 0);

    if (!noyonaBuySheetKeyHandler) {
      noyonaBuySheetKeyHandler = function (event) {
        if (event.key === 'Escape' || event.key === 'Esc') {
          closeNoyonaBuySheet();
        }
      };
      document.addEventListener('keydown', noyonaBuySheetKeyHandler);
    }
  }

  function closeNoyonaBuySheet(skipFocusReturn) {
    var sheet = getNoyonaBuySheet();
    if (!sheet) {
      return;
    }

    var wasOpen = sheet.classList.contains('is-open');
    var isListing = noyonaIsListingBuySheet(sheet);
    sheet.classList.remove('is-open');
    sheet.setAttribute('hidden', '');
    document.documentElement.classList.remove('noyona-pdp-buysheet-open');
    document.body.classList.remove('noyona-pdp-buysheet-open');

    if (noyonaBuySheetKeyHandler) {
      document.removeEventListener('keydown', noyonaBuySheetKeyHandler);
      noyonaBuySheetKeyHandler = null;
    }

    if (isListing && wasOpen) {
      if (noyonaArchiveBuySheetAbortController) {
        noyonaArchiveBuySheetAbortController.abort();
        noyonaArchiveBuySheetAbortController = null;
      }
      teardownArchiveBuySheet();
    }

    if (!skipFocusReturn && wasOpen && noyonaBuySheetLastFocus && typeof noyonaBuySheetLastFocus.focus === 'function') {
      try {
        noyonaBuySheetLastFocus.focus();
      } catch (e) {
        /* no-op */
      }
    }
    noyonaBuySheetLastFocus = null;
  }

  /**
   * Has the shopper actively confirmed a variation on this form?
   *
   * A WooCommerce default/preselected variation can populate `variation_id`
   * on load without any user interaction. We therefore require BOTH a real
   * variation id AND a user-interaction flag (set only on genuine swatch /
   * pill clicks or trusted native-select changes — see
   * bindNoyonaVariationInteraction). Simple products always return true.
   */
  function noyonaFormHasConfirmedVariation(form) {
    if (!form || !form.classList.contains('variations_form')) {
      return true; // Simple product — nothing to confirm.
    }
    if (!form._noyonaUserSelectedVariation) {
      return false; // Default/preselected only — not user-confirmed yet.
    }
    return noyonaFormHasValidVariation(form);
  }

  /**
   * Does the form currently resolve to a real, purchasable variation? Unlike
   * noyonaFormHasConfirmedVariation this does NOT require a prior user
   * interaction, so a WooCommerce default/preselected shade counts.
   */
  function noyonaFormHasValidVariation(form) {
    if (!form || !form.classList.contains('variations_form')) {
      return true; // Simple product — nothing to resolve.
    }
    var variationId = form.querySelector('input[name="variation_id"]');
    return !!(variationId && variationId.value && variationId.value !== '0');
  }

  function noyonaBuySheetIsOpen() {
    var sheet = getNoyonaBuySheet();
    return !!(sheet && sheet.classList.contains('is-open'));
  }

  /**
   * Track real user interaction with variation controls on a form, so we can
   * distinguish a user-chosen variation from a default/preselected one.
   *
   *  - Swatch (.noyona-pdp-swatch) and pill (.noyona-pdp-choice) clicks are
   *    genuine user input. The hidden native select's `change` is dispatched
   *    programmatically (isTrusted === false), so we cannot rely on it.
   *  - Dropdown controls keep the native <select> visible; a user change there
   *    is trusted, so we accept change events with isTrusted === true.
   *  - On reset_data (variation cleared/invalid) we drop the confirmation so a
   *    fresh selection is required.
   *
   * Also installs a mobile-only capture guard on the in-sheet buy-now button so
   * it cannot submit a default shade. Desktop is never affected (the guard
   * no-ops when the viewport is not <=767px and the bar/sheet are absent).
   */
  function bindNoyonaVariationInteraction(form) {
    if (!form || !form.classList.contains('variations_form')) {
      return;
    }
    if (form._noyonaInteractionBound) {
      return;
    }
    form._noyonaInteractionBound = true;
    form._noyonaUserSelectedVariation = false;

    form.addEventListener(
      'click',
      function (event) {
        var target = event.target;
        if (target && (target.closest('.noyona-pdp-swatch') || target.closest('.noyona-pdp-choice'))) {
          form._noyonaUserSelectedVariation = true;
        }
      },
      true
    );

    form.addEventListener(
      'change',
      function (event) {
        var target = event.target;
        if (
          event.isTrusted &&
          target &&
          target.matches &&
          target.matches('select[name^="attribute_"]')
        ) {
          form._noyonaUserSelectedVariation = true;
        }
      },
      true
    );

    // Block the in-sheet buy-now from submitting a default shade (mobile only).
    form.addEventListener(
      'click',
      function (event) {
        var buyBtn = event.target ? event.target.closest('.noyona-pdp-buy-now') : null;
        if (!buyBtn) {
          return;
        }
        if (!noyonaIsMobileViewport()) {
          return; // Desktop PDP unchanged.
        }
        if (noyonaFormHasConfirmedVariation(form)) {
          return; // Confirmed — let the existing buy-now flow run.
        }
        // If the sheet is already open the shopper can see exactly which shade
        // is selected, so a valid (even default/preselected) variation is good
        // enough to buy. Without this, tapping the in-sheet Buy now on a product
        // with a preselected shade just re-opens the already-open sheet and the
        // button appears dead until the shopper pointlessly changes the shade.
        if (noyonaBuySheetIsOpen() && noyonaFormHasValidVariation(form)) {
          return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        openNoyonaBuySheet();
      },
      true
    );

    if (typeof window.jQuery !== 'undefined') {
      window
        .jQuery(form)
        .off('reset_data.noyonaBuyGuard')
        .on('reset_data.noyonaBuyGuard', function () {
          form._noyonaUserSelectedVariation = false;
        });
    }
  }

  /**
   * Sticky "Buy now": if the product is variable and the shopper has not
   * actively confirmed a shade/variant (default/preselected does NOT count),
   * open the sheet so they can pick one. Otherwise click the existing in-form
   * buy-now button, which already handles login gating, variation validation,
   * the noyona_buy_now hidden input, and the WooCommerce cart redirect.
   */
  function handleNoyonaStickyBuyNow() {
    var form = getNoyonaPdpCartForm();
    if (!form) {
      return;
    }

    if (!noyonaFormHasConfirmedVariation(form)) {
      openNoyonaBuySheet();
      return;
    }

    var realBuyNow = form.querySelector('.noyona-pdp-buy-now');
    if (realBuyNow) {
      realBuyNow.click();
    }
  }

  function bindNoyonaBuyBar() {
    var bar = getNoyonaBuyBar();
    if (!bar || bar.getAttribute('data-noyona-bound') === '1') {
      return;
    }
    bar.setAttribute('data-noyona-bound', '1');

    var addBtn = bar.querySelector('[data-noyona-buybar-add]');
    var buyBtn = bar.querySelector('[data-noyona-buybar-buy]');

    if (addBtn) {
      addBtn.addEventListener('click', function () {
        openNoyonaBuySheet();
      });
    }
    if (buyBtn) {
      buyBtn.addEventListener('click', function () {
        handleNoyonaStickyBuyNow();
      });
    }
  }

  function bindNoyonaBuySheet() {
    var sheet = getNoyonaBuySheet();
    if (!sheet || sheet.getAttribute('data-noyona-bound') === '1') {
      return;
    }
    sheet.setAttribute('data-noyona-bound', '1');

    var closeBtn = sheet.querySelector('[data-noyona-buysheet-close]');
    var backdrop = sheet.querySelector('[data-noyona-buysheet-backdrop]');
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        closeNoyonaBuySheet();
      });
    }
    if (backdrop) {
      backdrop.addEventListener('click', function () {
        closeNoyonaBuySheet();
      });
    }

    if (typeof window.jQuery !== 'undefined') {
      // Close the sheet once an add succeeds; the existing flow then opens the
      // mini-cart drawer (skip focus return so it doesn't fight the drawer).
      window.jQuery(document.body).off('added_to_cart.noyonaBuySheet').on('added_to_cart.noyonaBuySheet', function () {
        closeNoyonaBuySheet(true);
      });

      // Keep the sheet header price + selected variant text in sync.
      window
        .jQuery(document.body)
        .off('found_variation.noyonaBuySheet show_variation.noyonaBuySheet reset_data.noyonaBuySheet hide_variation.noyonaBuySheet')
        .on(
          'found_variation.noyonaBuySheet show_variation.noyonaBuySheet reset_data.noyonaBuySheet hide_variation.noyonaBuySheet',
          'form.variations_form',
          function () {
            refreshNoyonaBuySheetHeader();
          }
        );
    }
  }

  function initNoyonaBuySheet() {
    var bar = getNoyonaBuyBar();
    var sheet = getNoyonaBuySheet();
    if (!sheet) {
      return;
    }

    if (!bar && !noyonaIsListingBuySheet(sheet)) {
      return;
    }

    if (bar) {
      bindNoyonaBuyBar();
    }
    bindNoyonaBuySheet();

    if (!noyonaIsListingBuySheet(sheet)) {
      relocateNoyonaFormForViewport();

      if (!window.__noyonaBuySheetMqlBound) {
        window.__noyonaBuySheetMqlBound = true;
        var mql = window.matchMedia(NOYONA_BUYSHEET_MQ);
        var onChange = function () {
          relocateNoyonaFormForViewport();
        };
        if (typeof mql.addEventListener === 'function') {
          mql.addEventListener('change', onChange);
        } else if (typeof mql.addListener === 'function') {
          mql.addListener(onChange);
        }
      }
    }
  }

  function getPdpStockBadge() {
    return document.querySelector('.single-product .noyona-pdp-stock-shipping__stock');
  }

  function isSimpleProductOutOfStock() {
    var stockBadge = getPdpStockBadge();
    return !!(
      stockBadge &&
      stockBadge.getAttribute('data-noyona-product-type') === 'simple' &&
      stockBadge.getAttribute('data-noyona-in-stock') === '0'
    );
  }

  function ensureSimpleOutOfStockActions() {
    if (!isSimpleProductOutOfStock()) {
      return;
    }
    if (document.querySelector('.single-product .noyona-pdp-cart-actions--unavailable')) {
      return;
    }
    if (document.querySelector('.single-product form.cart .single_add_to_cart_button')) {
      return;
    }

    var addToCartBlock = getSimplePdpCartRoot();
    if (!addToCartBlock) {
      return;
    }

    var qtyWrap = addToCartBlock.querySelector('.quantity');
    if (!qtyWrap) {
      qtyWrap = createPdpQuantityField();
      addToCartBlock.appendChild(qtyWrap);
    }
    enhanceQuantity(addToCartBlock);
    setPdpQuantityDisabled(addToCartBlock.querySelector('.quantity.noyona-pdp-qty'), true);

    var actions = document.createElement('div');
    actions.className = 'noyona-pdp-cart-actions noyona-pdp-cart-actions--unavailable';

    var addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.className = 'single_add_to_cart_button button alt wp-element-button wc-block-components-button';
    addButton.textContent = getPdpText('addToCart', 'Add to cart');

    var buyButton = document.createElement('button');
    buyButton.type = 'button';
    buyButton.className = 'noyona-pdp-buy-now button alt wp-element-button wc-block-components-button';
    buyButton.textContent = getPdpText('buyNow', 'Buy now');

    [addButton, buyButton].forEach(function (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        showPdpToast(getPdpOutOfStockMessage(), 'error');
      });
    });

    actions.appendChild(addButton);
    actions.appendChild(buyButton);
    addToCartBlock.appendChild(actions);
  }

  function initSimpleProductStockState() {
    var stockBadge = getPdpStockBadge();
    if (!stockBadge || stockBadge.getAttribute('data-noyona-product-type') !== 'simple') {
      return;
    }

    var isOutOfStock = stockBadge.getAttribute('data-noyona-in-stock') === '0';
    applyPdpOutOfStockImageState(isOutOfStock);
    ensureSimplePdpQuantity();

    if (isOutOfStock) {
      ensureSimpleOutOfStockActions();
    }
  }

  function initPdp() {
    clearLegacyAddToCartState();
    bindPdpAlertToasts();
    initTabs(document);
    initPdpWishlist();

    document.querySelectorAll('form.cart').forEach(function (form) {
      initSwatches(form);
      enhanceQuantity(form);
      bindVariationCache(form);
      bindVariationPriceSync(form);
      bindVariationStockSync(form);
      bindAjaxAddToCart(form);
      addBuyNow(form);
      bindNoyonaVariationInteraction(form);
    });

    initNoyonaBuySheet();
    initSimpleProductStockState();

    // WooCommerce store-notice banners can hydrate after init (block theme),
    // so catch a late-rendered Buy Now error banner and promote it to a toast.
    window.setTimeout(promotePdpErrorNoticesToToast, 600);
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
      var imageData = collectGalleryImageData(gallery);
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

      function setFallbackMainImage(index) {
        if (isNaN(index) || !imageData[index]) return false;
        var data = imageData[index];
        mainImg.src = data.full;
        mainImg.alt = data.alt;
        if (data.srcset) { mainImg.srcset = data.srcset; } else { mainImg.removeAttribute('srcset'); }
        if (data.sizes)  { mainImg.sizes  = data.sizes;  } else { mainImg.removeAttribute('sizes'); }
        thumbs.querySelectorAll('.noyona-pdp-gallery-thumb').forEach(function (item) {
          item.classList.remove('is-active');
        });
        var activeThumb = thumbs.querySelector('[data-noyona-thumb-index="' + index + '"]');
        var parentLi = activeThumb ? activeThumb.closest('.noyona-pdp-gallery-thumb') : null;
        if (parentLi) parentLi.classList.add('is-active');
        return true;
      }

      gallery._noyonaFallbackSwitchToIndex = setFallbackMainImage;

      // Magnifier button (top-right) — opens lightbox of the current image.
      // Mirrors WooCommerce's `.woocommerce-product-gallery__trigger` so the
      // page reads the same in fallback and FlexSlider modes.
      if(gallery.querySelector('.woocommerce-product-gallery__trigger')) {
        return;
      }
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

        setFallbackMainImage(idx);
        syncVariationFromGallery(gallery, imageData[idx]);
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
    setTimeout(function () {
      initGalleryFallback();
      initSimpleProductStockState();
    }, 400);
  }

  bindWooCommercePdpHooks();

  window.noyonaBuySheet = window.noyonaBuySheet || {};
  window.noyonaBuySheet.open = function (productId, sourceButton) {
    return openArchiveBuySheet(productId, sourceButton);
  };
  window.noyonaBuySheet.close = function (skipFocusReturn) {
    closeNoyonaBuySheet(!!skipFocusReturn);
  };
  /* Listing Buy Sheet Cache — silent background prefetch API (Phase 2). */
  window.noyonaBuySheet.prefetch = function (productId) {
    return prefetchListingBuySheetForm(productId);
  };

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