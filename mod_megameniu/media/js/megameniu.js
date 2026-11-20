/*
 * mod_megameniu — comportament
 * Fără dependențe (fără jQuery/Bootstrap). Model WAI-ARIA „disclosure navigation”.
 */
(function () {
	'use strict';

	var CLOSE_DELAY = 150;

	function closest(el, selector) {
		while (el && el.nodeType === 1) {
			if (matches(el, selector)) {
				return el;
			}
			el = el.parentNode;
		}
		return null;
	}

	function matches(el, selector) {
		var fn = el.matches || el.msMatchesSelector || el.webkitMatchesSelector;
		return fn ? fn.call(el, selector) : false;
	}

	function directChild(parent, selector) {
		for (var i = 0; i < parent.children.length; i++) {
			if (matches(parent.children[i], selector)) {
				return parent.children[i];
			}
		}
		return null;
	}

	function focusables(root) {
		return Array.prototype.filter.call(
			root.querySelectorAll('a[href], button:not([disabled])'),
			function (el) {
				return el.offsetWidth > 0 || el.offsetHeight > 0 || el === document.activeElement;
			}
		);
	}

	function init(nav) {
		if (nav.getAttribute('data-mm-init') === '1') {
			return;
		}
		nav.setAttribute('data-mm-init', '1');
		nav.classList.add('mm-js');

		var list = nav.querySelector('.mm-list');
		var toggle = nav.querySelector('.mm-toggle');
		var openOn = nav.getAttribute('data-open-on') || 'hover';
		var mobileMode = nav.getAttribute('data-mobile-mode') || 'accordion';
		var breakpoint = parseInt(nav.getAttribute('data-breakpoint') || '992', 10);
		var closeLabel = nav.getAttribute('data-close-label') || 'Close';
		var mq = window.matchMedia('(max-width: ' + (breakpoint - 0.02) + 'px)');
		var lastPointer = 'mouse';
		var backdrop = null;
		var closeLi = null;

		if (!list) {
			return;
		}

		if (mobileMode === 'offcanvas') {
			nav.classList.add('mm-offcanvas');
		}

		var tops = Array.prototype.filter.call(list.children, function (li) {
			return li.classList.contains('mm-has-sub');
		});

		function isMobile() {
			return nav.classList.contains('mm-is-mobile');
		}

		function btnOf(li) {
			return directChild(li, '.mm-sub-toggle');
		}

		function dropOf(li) {
			return directChild(li, '.mm-drop');
		}

		function openItem(li) {
			if (!isMobile()) {
				tops.forEach(function (other) {
					if (other !== li) {
						closeItem(other);
					}
				});
			}
			li.classList.add('mm-open');
			var b = btnOf(li);
			if (b) {
				b.setAttribute('aria-expanded', 'true');
			}
			keepInViewport(li);
		}

		function closeItem(li) {
			if (li._mmTimer) {
				clearTimeout(li._mmTimer);
				li._mmTimer = null;
			}
			li.classList.remove('mm-open');
			var b = btnOf(li);
			if (b) {
				b.setAttribute('aria-expanded', 'false');
			}
		}

		function closeAll() {
			tops.forEach(closeItem);
		}

		function keepInViewport(li) {
			var drop = dropOf(li);
			if (!drop || isMobile()) {
				return;
			}
			drop.classList.remove('mm-drop-right');
			drop.style.left = '';
			var vw = document.documentElement.clientWidth || window.innerWidth;
			var rect = drop.getBoundingClientRect();
			var overflow = rect.right - (vw - 8);
			if (overflow > 0) {
				// Mutare spre stânga doar cât e nevoie, fără să iasă pe marginea stângă.
				var shift = Math.min(overflow, Math.max(0, rect.left - 8));
				drop.style.left = (-shift) + 'px';
			} else {
				overflow = 0;
			}
			// Submeniurile laterale (mod listă) se deschid spre stânga lângă marginea dreaptă.
			if (rect.right - Math.max(0, overflow) + rect.width > vw) {
				drop.classList.add('mm-drop-right');
			}
		}

		// ---------- Elemente de nivel 1 ----------
		tops.forEach(function (li) {
			var b = btnOf(li);
			var link = directChild(li, '.mm-link');

			if (b) {
				b.addEventListener('click', function (e) {
					e.preventDefault();
					if (li.classList.contains('mm-open')) {
						closeItem(li);
					} else {
						openItem(li);
					}
				});

				b.addEventListener('keydown', function (e) {
					if (e.key === 'ArrowDown' || e.key === 'Down') {
						e.preventDefault();
						openItem(li);
						var first = dropOf(li) && dropOf(li).querySelector('a[href], button');
						if (first) {
							first.focus();
						}
					}
				});
			}

			if (openOn === 'hover') {
				li.addEventListener('mouseenter', function () {
					if (isMobile() || lastPointer === 'touch') {
						return;
					}
					if (li._mmTimer) {
						clearTimeout(li._mmTimer);
						li._mmTimer = null;
					}
					openItem(li);
				});

				li.addEventListener('mouseleave', function () {
					if (isMobile() || lastPointer === 'touch') {
						return;
					}
					li._mmTimer = setTimeout(function () {
						closeItem(li);
					}, CLOSE_DELAY);
				});
			}

			// Ecran tactil, pe desktop: prima atingere deschide, a doua navighează.
			if (link) {
				link.addEventListener('click', function (e) {
					if (!isMobile() && lastPointer === 'touch' && !li.classList.contains('mm-open')) {
						e.preventDefault();
						openItem(li);
					}
				});
			}

			// Focusul a ieșit din element: se închide (desktop).
			li.addEventListener('focusout', function (e) {
				if (isMobile()) {
					return;
				}
				var next = e.relatedTarget;
				if (next && !li.contains(next)) {
					closeItem(li);
				}
			});
		});

		// ---------- Tastatura: Escape ----------
		nav.addEventListener('keydown', function (e) {
			if (e.key !== 'Escape' && e.key !== 'Esc') {
				return;
			}

			var openLi = closest(document.activeElement, '.mm-l1.mm-open') ||
				tops.filter(function (li) { return li.classList.contains('mm-open'); })[0];

			if (openLi) {
				e.preventDefault();
				closeItem(openLi);
				var b = btnOf(openLi);
				if (b) {
					b.focus();
				}
				return;
			}

			if (isMobile() && nav.classList.contains('mm-mobile-open')) {
				e.preventDefault();
				closeMobile(true);
			}
		});

		// ---------- Clic în afara meniului ----------
		document.addEventListener('click', function (e) {
			if (!nav.contains(e.target) && !isMobile()) {
				closeAll();
			}
		});

		// ---------- Tipul de pointer (mouse/touch) ----------
		nav.addEventListener('pointerdown', function (e) {
			lastPointer = e.pointerType || 'mouse';
		});
		nav.addEventListener('touchstart', function () {
			lastPointer = 'touch';
		}, { passive: true });

		// ---------- Mobil ----------
		function openMobile() {
			nav.classList.add('mm-mobile-open');
			toggle.setAttribute('aria-expanded', 'true');

			if (mobileMode === 'offcanvas') {
				if (!closeLi) {
					closeLi = document.createElement('li');
					closeLi.className = 'mm-close-li';
					var cb = document.createElement('button');
					cb.type = 'button';
					cb.className = 'mm-close';
					cb.innerHTML = '<span aria-hidden="true">&times;</span>';
					var txt = document.createElement('span');
					txt.textContent = closeLabel;
					cb.appendChild(txt);
					cb.addEventListener('click', function () {
						closeMobile(true);
					});
					closeLi.appendChild(cb);
					list.insertBefore(closeLi, list.firstChild);
				}
				backdrop = document.createElement('div');
				backdrop.className = 'mm-backdrop';
				backdrop.addEventListener('click', function () {
					closeMobile(true);
				});
				document.body.appendChild(backdrop);
				document.documentElement.style.overflow = 'hidden';
				setTimeout(function () {
					var f = focusables(list)[0];
					if (f) {
						f.focus();
					}
				}, 50);
			}
		}

		function closeMobile(returnFocus) {
			if (!nav.classList.contains('mm-mobile-open')) {
				return;
			}
			nav.classList.remove('mm-mobile-open');
			toggle.setAttribute('aria-expanded', 'false');
			if (backdrop && backdrop.parentNode) {
				backdrop.parentNode.removeChild(backdrop);
			}
			backdrop = null;
			document.documentElement.style.overflow = '';
			if (returnFocus) {
				toggle.focus();
			}
		}

		if (toggle) {
			toggle.addEventListener('click', function () {
				if (nav.classList.contains('mm-mobile-open')) {
					closeMobile(false);
				} else {
					openMobile();
				}
			});
		}

		// Capcană de focus în panoul lateral.
		list.addEventListener('keydown', function (e) {
			if (e.key !== 'Tab' || !isMobile() || mobileMode !== 'offcanvas' || !nav.classList.contains('mm-mobile-open')) {
				return;
			}
			var f = focusables(list);
			if (!f.length) {
				return;
			}
			var first = f[0];
			var last = f[f.length - 1];
			if (e.shiftKey && document.activeElement === first) {
				e.preventDefault();
				last.focus();
			} else if (!e.shiftKey && document.activeElement === last) {
				e.preventDefault();
				first.focus();
			}
		});

		function applyMode() {
			var mobile = mq.matches;
			nav.classList.toggle('mm-is-mobile', mobile);
			nav.classList.toggle('mm-is-desktop', !mobile);
			closeAll();
			if (!mobile) {
				closeMobile(false);
			}
			// Pe mobil, ramura activă rămâne deschisă.
			if (mobile) {
				tops.forEach(function (li) {
					if (li.classList.contains('mm-active')) {
						openItem(li);
					}
				});
			}
		}

		if (mq.addEventListener) {
			mq.addEventListener('change', applyMode);
		} else if (mq.addListener) {
			mq.addListener(applyMode);
		}

		applyMode();
	}

	function initAll() {
		Array.prototype.forEach.call(document.querySelectorAll('nav.mm'), init);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}
})();
