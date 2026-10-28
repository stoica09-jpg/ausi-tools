/**
 * Adrian Accessibility Tools
 *
 * - Opens / closes the slide-out panel (keyboard friendly: Esc closes, focus returns to the button).
 * - Toggles tool classes (adrian-invert, adrian-monochrome, ...) on <html>.
 * - Scales text size in steps.
 * - Saves everything in localStorage ("adrian_a11y_prefs") so it persists across pages.
 */
(function () {
	'use strict';

	var STORAGE_KEY = 'adrian_a11y_prefs';
	var FONT_STEP = 0.1;   // 10% per click
	var FONT_MIN = -2;     // 80%
	var FONT_MAX = 5;      // 150%

	// Tools that cannot be active at the same time.
	var EXCLUSIVE = {
		'adrian-dark-contrast': ['adrian-light-contrast'],
		'adrian-light-contrast': ['adrian-dark-contrast']
	};

	var root = document.documentElement;
	var widgets = [];
	var enabledTools = [];
	var fontLevel = 0;
	var fontOriginals = new Map();

	/* ---------- Storage ---------- */

	function loadPrefs() {
		try {
			var prefs = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '{}') || {};

			return {
				classes: Array.isArray(prefs.classes) ? prefs.classes : [],
				font: parseInt(prefs.font, 10) || 0
			};
		} catch (e) {
			return { classes: [], font: 0 };
		}
	}

	function savePrefs() {
		var active = enabledTools.filter(function (cls) {
			return root.classList.contains(cls);
		});

		try {
			if (!active.length && fontLevel === 0) {
				window.localStorage.removeItem(STORAGE_KEY);
			} else {
				window.localStorage.setItem(STORAGE_KEY, JSON.stringify({ classes: active, font: fontLevel }));
			}
		} catch (e) {
			// Storage unavailable (private mode, blocked): preferences just won't persist.
		}
	}

	/* ---------- Text size ---------- */

	var SKIP_TAGS = /^(SCRIPT|STYLE|NOSCRIPT|TEMPLATE|LINK|META|BR|HR|IMG|VIDEO|AUDIO|CANVAS|IFRAME|OBJECT|EMBED|PICTURE|SOURCE)$/;

	function scalableElements() {
		return Array.prototype.filter.call(document.body.getElementsByTagName('*'), function (el) {
			return !SKIP_TAGS.test(el.tagName)
				&& !(el instanceof SVGElement)
				&& !el.closest('.adrian-a11y');
		});
	}

	function applyFontLevel() {
		var factor = 1 + fontLevel * FONT_STEP;
		var elements = scalableElements();

		if (fontLevel === 0) {
			fontOriginals.forEach(function (original, el) {
				el.style.setProperty('font-size', original.inline, original.priority);

				if (!el.getAttribute('style')) {
					el.removeAttribute('style');
				}
			});
			fontOriginals.clear();

			return;
		}

		// Read every original size first, before changing anything, so nested elements are not scaled twice.
		elements.forEach(function (el) {
			if (!fontOriginals.has(el)) {
				fontOriginals.set(el, {
					size: parseFloat(window.getComputedStyle(el).fontSize) || 16,
					inline: el.style.getPropertyValue('font-size'),
					priority: el.style.getPropertyPriority('font-size')
				});
			}
		});

		elements.forEach(function (el) {
			var original = fontOriginals.get(el);

			el.style.setProperty('font-size', (original.size * factor).toFixed(2) + 'px', 'important');
		});
	}

	function setFontLevel(level) {
		fontLevel = Math.max(FONT_MIN, Math.min(FONT_MAX, level));
		applyFontLevel();
	}

	/* ---------- Tools ---------- */

	function setTool(cls, on) {
		if (enabledTools.indexOf(cls) === -1) {
			return;
		}

		if (on && EXCLUSIVE[cls]) {
			EXCLUSIVE[cls].forEach(function (other) {
				root.classList.remove(other);
			});
		}

		root.classList.toggle(cls, on);
	}

	function resetAll() {
		enabledTools.forEach(function (cls) {
			root.classList.remove(cls);
		});
		setFontLevel(0);
	}

	/* ---------- UI ---------- */

	function syncUi() {
		widgets.forEach(function (w) {
			Array.prototype.forEach.call(w.el.querySelectorAll('[data-adrian-a11y-tool]'), function (btn) {
				var active = root.classList.contains(btn.getAttribute('data-adrian-a11y-tool'));

				btn.setAttribute('aria-pressed', active ? 'true' : 'false');
			});

			Array.prototype.forEach.call(w.el.querySelectorAll('[data-adrian-a11y-font-value]'), function (out) {
				out.textContent = Math.round((1 + fontLevel * FONT_STEP) * 100) + '%';
			});

			Array.prototype.forEach.call(w.el.querySelectorAll('[data-adrian-a11y-font]'), function (btn) {
				var dir = parseInt(btn.getAttribute('data-adrian-a11y-font'), 10);

				btn.disabled = (dir < 0 && fontLevel <= FONT_MIN) || (dir > 0 && fontLevel >= FONT_MAX);
			});
		});
	}

	function announce(w, message) {
		var status = w.el.querySelector('[data-adrian-a11y-status]');

		if (!status) {
			return;
		}

		status.textContent = '';
		window.setTimeout(function () {
			status.textContent = message;
		}, 50);
	}

	function format(template, value) {
		return (template || '%s').replace('%s', value);
	}

	function isOpen(w) {
		return w.panel.classList.contains('is-open');
	}

	function openPanel(w) {
		widgets.forEach(function (other) {
			if (other !== w) {
				closePanel(other, false);
			}
		});

		w.panel.classList.add('is-open');
		w.toggle.setAttribute('aria-expanded', 'true');

		var first = w.panel.querySelector('button:not(:disabled)');

		if (first) {
			first.focus();
		}
	}

	function closePanel(w, returnFocus) {
		if (!isOpen(w)) {
			return;
		}

		w.panel.classList.remove('is-open');
		w.toggle.setAttribute('aria-expanded', 'false');

		if (returnFocus) {
			w.toggle.focus();
		}
	}

	function bind(w) {
		w.toggle.addEventListener('click', function () {
			if (isOpen(w)) {
				closePanel(w, true);
			} else {
				openPanel(w);
			}
		});

		w.panel.addEventListener('click', function (event) {
			var target = event.target.closest('button');

			if (!target || !w.panel.contains(target)) {
				return;
			}

			if (target.hasAttribute('data-adrian-a11y-close')) {
				closePanel(w, true);

				return;
			}

			if (target.hasAttribute('data-adrian-a11y-tool')) {
				var cls = target.getAttribute('data-adrian-a11y-tool');
				var on = !root.classList.contains(cls);
				var label = target.querySelector('.adrian-a11y__tool-label');

				setTool(cls, on);
				announce(w, format(w.el.getAttribute(on ? 'data-text-on' : 'data-text-off'), label ? label.textContent.trim() : cls));
			} else if (target.hasAttribute('data-adrian-a11y-font')) {
				setFontLevel(fontLevel + parseInt(target.getAttribute('data-adrian-a11y-font'), 10));
				announce(w, format(w.el.getAttribute('data-text-size'), Math.round((1 + fontLevel * FONT_STEP) * 100) + '%'));
			} else if (target.hasAttribute('data-adrian-a11y-reset')) {
				resetAll();
				announce(w, w.el.getAttribute('data-text-reset') || '');
			} else {
				return;
			}

			savePrefs();
			syncUi();
		});
	}

	// Close when clicking outside an open panel.
	document.addEventListener('click', function (event) {
		widgets.forEach(function (w) {
			if (isOpen(w) && !w.panel.contains(event.target) && !w.toggle.contains(event.target)) {
				closePanel(w, false);
			}
		});
	});

	document.addEventListener('keydown', function (event) {
		if (event.key !== 'Escape') {
			return;
		}

		widgets.forEach(function (w) {
			if (isOpen(w)) {
				closePanel(w, true);
			}
		});
	});

	/* ---------- Init ---------- */

	function init() {
		Array.prototype.forEach.call(document.querySelectorAll('[data-adrian-a11y]'), function (el) {
			var w = {
				el: el,
				toggle: el.querySelector('[data-adrian-a11y-toggle]'),
				panel: el.querySelector('[data-adrian-a11y-panel]')
			};

			if (!w.toggle || !w.panel) {
				return;
			}

			(el.getAttribute('data-tools') || '').split(/\s+/).forEach(function (cls) {
				if (cls && enabledTools.indexOf(cls) === -1) {
					enabledTools.push(cls);
				}
			});

			// The panel's text-size controls only exist when the tool is enabled.
			w.hasFontTool = !!el.querySelector('[data-adrian-a11y-font]');

			widgets.push(w);
			bind(w);
		});

		if (!widgets.length) {
			return;
		}

		var prefs = loadPrefs();

		// Remove classes restored early by the inline script whose tool is disabled here.
		Array.prototype.slice.call(root.classList).forEach(function (cls) {
			if (/^adrian-/.test(cls) && enabledTools.indexOf(cls) === -1) {
				root.classList.remove(cls);
			}
		});

		prefs.classes.forEach(function (cls) {
			setTool(cls, true);
		});

		var fontEnabled = widgets.some(function (w) {
			return w.hasFontTool;
		});

		if (fontEnabled && prefs.font) {
			setFontLevel(prefs.font);
		}

		syncUi();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
