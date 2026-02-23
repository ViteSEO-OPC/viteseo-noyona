(function () {
  const STICKY_AT = 15;

  const $ = (s, scope = document) => scope.querySelector(s);
  const $$ = (s, scope = document) => Array.from(scope.querySelectorAll(s));

  function updateHeaderOffsets() {
    const root = document.documentElement;
    const headerPart = $('header.wp-block-template-part');
    const adminBar = $('#wpadminbar');

    const headerHeight = headerPart ? Math.ceil(headerPart.getBoundingClientRect().height) : 72;
    const adminBarHeight = adminBar ? Math.ceil(adminBar.getBoundingClientRect().height) : 0;
    const totalOffset = headerHeight + adminBarHeight;

    root.style.setProperty('--noyona-header-height', headerHeight + 'px');
    root.style.setProperty('--noyona-adminbar-height', adminBarHeight + 'px');
    root.style.setProperty('--noyona-header-total-offset', totalOffset + 'px');
  }

  function toggleScrollState() {
    const y = window.scrollY || 0;
    const headerPart = $('header.wp-block-template-part');

    if (y > STICKY_AT) {
      document.body.classList.add('has-scrolled-header');
      if (headerPart) headerPart.classList.add('is-sticky');
    } else {
      document.body.classList.remove('has-scrolled-header');
      if (headerPart) headerPart.classList.remove('is-sticky');
    }

    updateHeaderOffsets();
    // Logo swap on scroll is intentionally disabled for now.
    // updateHeaderLogoVariant(y > STICKY_AT);
  }

  /*
  function updateHeaderLogoVariant(useScrolledLogo) {
    $$('.site-logo-img[data-logo-default][data-logo-scrolled]').forEach((img) => {
      const nextSrc = useScrolledLogo ? img.dataset.logoScrolled : img.dataset.logoDefault;
      if (!nextSrc) return;
      img.classList.toggle('is-scrolled-logo', useScrolledLogo);
      if (img.getAttribute('src') !== nextSrc) {
        img.setAttribute('src', nextSrc);
      }
    });
  }
  */

  function initWishlist() {
    const panel = $('#wishlist-panel');
    if (!panel) return;

    const overlay = panel.querySelector('.wishlist-panel__overlay');
    const closeBtn = panel.querySelector('.wishlist-panel__close');

    const closePanel = () => panel.classList.remove('is-open');
    const openPanel = () => panel.classList.add('is-open');

    document.addEventListener('click', (e) => {
      if (e.target.closest('.header-wishlist-toggle')) openPanel();
    });

    if (overlay) overlay.addEventListener('click', closePanel);
    if (closeBtn) closeBtn.addEventListener('click', closePanel);

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closePanel();
    });
  }

  function initAccountDropdown() {
    $$('.header-account').forEach((wrapper) => {
      const toggle = wrapper.querySelector('.header-account-toggle');
      const menu = wrapper.querySelector('.header-account-menu');
      if (!toggle || !menu) return;

      const closeMenu = () => {
        wrapper.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        menu.setAttribute('aria-hidden', 'true');
      };

      toggle.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const open = wrapper.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        menu.setAttribute('aria-hidden', open ? 'false' : 'true');
      });

      document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) closeMenu();
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeMenu();
      });
    });
  }

  function initLogoutLinks() {
    const logoutUrl = (window.noyonaHeader && window.noyonaHeader.logoutUrl) ? window.noyonaHeader.logoutUrl : '';
    if (!logoutUrl) return;

    $$('.header-account-logout, .mobile-logout').forEach((a) => {
      a.setAttribute('href', logoutUrl);
    });
  }

  function initMobileMenu() {
    const burger = $('.mobile-menu-toggle');
    const navList = $('.nav-list');
    if (!burger || !navList) return;

    const icon = burger.querySelector('i');

    const closeMenu = () => {
      document.body.classList.remove('is-menu-open');
      burger.setAttribute('aria-expanded', 'false');
      if (icon) {
        icon.classList.add('fa-bars');
        icon.classList.remove('fa-xmark');
      }
    };

    const openMenu = () => {
      document.body.classList.add('is-menu-open');
      burger.setAttribute('aria-expanded', 'true');
      if (icon) {
        icon.classList.remove('fa-bars');
        icon.classList.add('fa-xmark');
      }
    };

    burger.addEventListener('click', (e) => {
      e.preventDefault();
      document.body.classList.contains('is-menu-open') ? closeMenu() : openMenu();
    });

    document.addEventListener('click', (e) => {
      if (!document.body.classList.contains('is-menu-open')) return;
      if (navList.contains(e.target) || burger.contains(e.target)) return;
      closeMenu();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeMenu();
    });
  }

  function initMobileSubmenus() {
    $$('.submenu-toggle').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const navItem = btn.closest('.nav-item');
        const icon = btn.querySelector('i');
        if (!navItem) return;

        const isOpen = navItem.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

        if (icon) {
          icon.classList.toggle('fa-plus', !isOpen);
          icon.classList.toggle('fa-minus', isOpen);
        }
      });
    });
  }

  function initActiveNavLinks() {
    const currentPath = window.location.pathname;
    const navLinks = $$('.nav-link, .dropdown-menu a, .mobile-drawer-links a');

    navLinks.forEach((link) => {
      const linkPath = new URL(link.href, window.location.origin).pathname;

      if (currentPath === linkPath) {
        link.classList.add('is-active');

        // Handle dropdown logic
        const dropdown = link.closest('.dropdown-menu');
        if (dropdown) {
          const navItem = dropdown.closest('.nav-item');
          if (navItem) {
            navItem.classList.add('has-active-child');
            // Also mark parent Link as active to trigger its underline
            const parentLink = navItem.querySelector('.nav-link');
            if (parentLink) parentLink.classList.add('is-active');
          }
        }
      }
    });

    // Prevent click on active
    document.addEventListener('click', (e) => {
      const link = e.target.closest('.is-active');
      if (link && link.href) {
        const linkPath = new URL(link.href, window.location.origin).pathname;
        if (currentPath === linkPath) e.preventDefault();
      }
    });
  }

  function initMiniCartFallback() {
    const hasMiniCartButton = () =>
      !!$('.header-icons .wp-block-woocommerce-mini-cart .wc-block-mini-cart__button');

    const applyState = () => {
      document.body.classList.toggle('no-mini-cart', !hasMiniCartButton());
    };

    // Initial pass + delayed pass to catch late-rendered mini-cart markup.
    applyState();
    window.setTimeout(applyState, 900);
  }

  document.addEventListener('DOMContentLoaded', () => {
    updateHeaderOffsets();
    toggleScrollState();
    window.addEventListener('scroll', toggleScrollState, { passive: true });
    window.addEventListener('resize', updateHeaderOffsets);

    initWishlist();
    initAccountDropdown();
    initLogoutLinks();
    initMobileMenu();
    initMobileSubmenus();
    initActiveNavLinks();
    initMiniCartFallback();
  });
})();
