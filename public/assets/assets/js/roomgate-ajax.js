/**
 * RoomGate AJAX helper
 */
'use strict';

window.RoomGateAjax = window.RoomGateAjax || {};

window.RoomGateAjax.initSwappableTabs = function (options) {
  const config = {
    containerSelector: options.containerSelector,
    linkSelector: options.linkSelector,
    activeClass: options.activeClass || 'active',
    loadingClass: options.loadingClass || 'rg-ajax-loading',
    overlayClass: options.overlayClass || 'rg-ajax-overlay',
    onLoaded: options.onLoaded
  };

  let isLoading = false;

  function ensureOverlay(container) {
    if (container.querySelector('.' + config.overlayClass)) return;
    const overlay = document.createElement('div');
    overlay.className = config.overlayClass;
    overlay.innerHTML =
      '<div class="spinner-border text-primary" role="status" aria-hidden="true"></div>' +
      '<span class="visually-hidden">Loading</span>';
    container.appendChild(overlay);
  }

  function setLoading(container, state) {
    if (!container) return;
    container.classList.toggle(config.loadingClass, state);
    if (state) {
      ensureOverlay(container);
    }
  }

  function swapContent(url, pushState) {
    if (isLoading) return;
    const container = document.querySelector(config.containerSelector);
    if (!container) {
      window.location.href = url;
      return;
    }

    isLoading = true;
    setLoading(container, true);

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(response => response.text())
      .then(html => {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const newContainer = doc.querySelector(config.containerSelector);
        if (!newContainer) {
          window.location.href = url;
          return;
        }
        container.replaceWith(newContainer);
        if (pushState) {
          window.history.pushState({ roomgateAjax: true }, '', url);
        }
        bindLinks();
        if (typeof config.onLoaded === 'function') {
          config.onLoaded(url);
        }
      })
      .catch(() => {
        window.location.href = url;
      })
      .finally(() => {
        const currentContainer = document.querySelector(config.containerSelector);
        setLoading(currentContainer, false);
        isLoading = false;
      });
  }

  function bindLinks() {
    document.querySelectorAll(config.linkSelector).forEach(link => {
      if (link.dataset.ajaxBound === 'true') return;
      link.dataset.ajaxBound = 'true';
      link.addEventListener('click', event => {
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        if (link.classList.contains(config.activeClass)) return;
        swapContent(link.href, true);
      });
    });
  }

  window.addEventListener('popstate', () => {
    swapContent(window.location.href, false);
  });

  bindLinks();
};
