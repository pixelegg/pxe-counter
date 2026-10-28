/*
 * pxe_counter - dependency-free animated number counter.
 *
 * The server renders the final, localised value. This script counts each .pxe-counter from
 * data-from to data-to once it scrolls into view, formatting frames with Intl.NumberFormat
 * for the site language (data-locale).
 *
 * Accessibility: while animating, the visible digits are aria-hidden and a visually hidden
 * copy of the final text is announced instead (no aria-live, so nothing is read per frame).
 * On completion the original markup is restored. prefers-reduced-motion skips the animation.
 * Layout stability: the value keeps the width of the final number while counting.
 */
(function () {
  'use strict';

  var EASINGS = {
    linear: function (t) { return t; },
    'ease-out': function (t) { return 1 - Math.pow(1 - t, 3); },
    'ease-in-out': function (t) {
      return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
    }
  };

  function num(value, fallback) {
    var n = parseFloat(value);
    return isNaN(n) ? fallback : n;
  }

  function buildFormatter(el) {
    var decimals = Math.max(0, Math.min(4, parseInt(el.getAttribute('data-decimals'), 10) || 0));
    var grouping = el.getAttribute('data-separator') !== '0';
    var locale = el.getAttribute('data-locale') || document.documentElement.lang || undefined;
    try {
      return new Intl.NumberFormat(locale, {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
        useGrouping: grouping
      });
    } catch (e) {
      return { format: function (v) { return v.toFixed(decimals); } };
    }
  }

  function readConfig(el) {
    var from = num(el.getAttribute('data-from'), 0);
    var to = num(el.getAttribute('data-to'), 0);
    var duration = Math.max(0, num(el.getAttribute('data-duration'), 2)) * 1000;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    return {
      from: from,
      to: to,
      duration: duration,
      delay: Math.max(0, num(el.getAttribute('data-delay'), 0)) * 1000,
      ease: EASINGS[el.getAttribute('data-easing')] || EASINGS['ease-out'],
      animate: !reduce && duration > 0 && from !== to
    };
  }

  /*
   * Swap the final value for the start value before the element is observed, so the user
   * sees the count-up once the counter scrolls in. Returns a restore function.
   */
  function prepare(el, config) {
    var valueEl = el.querySelector('.pxe-counter__value');
    var numberEl = el.querySelector('.pxe-counter__number');
    if (!valueEl || !numberEl) { return null; }

    var formatter = buildFormatter(el);
    var finalText = valueEl.textContent;

    // keep the width of the final number so nothing shifts while counting
    var width = valueEl.getBoundingClientRect().width;
    if (width > 0) { valueEl.style.minWidth = Math.ceil(width) + 'px'; }

    var sr = document.createElement('span');
    sr.className = 'pxe-counter__sr';
    sr.textContent = numberEl.textContent.replace(/\s+/g, ' ').trim();
    numberEl.setAttribute('aria-hidden', 'true');
    numberEl.parentNode.insertBefore(sr, numberEl.nextSibling);

    valueEl.textContent = formatter.format(config.from);
    el.classList.add('is-pending');

    return {
      frame: function (value) { valueEl.textContent = formatter.format(value); },
      finish: function () {
        valueEl.textContent = finalText;
        valueEl.style.minWidth = '';
        numberEl.removeAttribute('aria-hidden');
        if (sr.parentNode) { sr.parentNode.removeChild(sr); }
        el.classList.remove('is-pending');
        el.classList.add('is-done');
      }
    };
  }

  function animate(el, config, handle) {
    var start = null;
    function step(now) {
      if (start === null) { start = now; }
      var t = Math.min(1, (now - start) / config.duration);
      if (t < 1) {
        handle.frame(config.from + (config.to - config.from) * config.ease(t));
        window.requestAnimationFrame(step);
      } else {
        handle.finish();
      }
    }
    window.setTimeout(function () { window.requestAnimationFrame(step); }, config.delay);
  }

  function init() {
    var counters = Array.prototype.slice.call(document.querySelectorAll('.pxe-counter'));
    if (!counters.length) { return; }

    var pending = [];
    counters.forEach(function (el) {
      if (el.classList.contains('is-done') || el.classList.contains('is-pending')) { return; }
      var config = readConfig(el);
      if (!config.animate) { el.classList.add('is-done'); return; }
      var handle = prepare(el, config);
      if (handle) { pending.push({ el: el, config: config, handle: handle }); }
    });
    if (!pending.length) { return; }

    if (!('IntersectionObserver' in window)) {
      pending.forEach(function (item) { animate(item.el, item.config, item.handle); });
      return;
    }

    var observer = new IntersectionObserver(function (entries, obs) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) { return; }
        obs.unobserve(entry.target);
        pending.forEach(function (item) {
          if (item.el === entry.target) { animate(item.el, item.config, item.handle); }
        });
      });
    }, { threshold: 0.35 });

    pending.forEach(function (item) { observer.observe(item.el); });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
