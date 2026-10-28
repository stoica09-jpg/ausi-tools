/**
 * Adrian Cookie - EU cookie consent banner
 *
 * Sets the "adrian_cookie_consent" cookie ("all" or "essential") for 365 days.
 * Other scripts can read the choice with window.AdrianCookie.get() or listen for
 * the "adrian-cookie-consent" event on document.
 */
(function () {
	'use strict';

	var COOKIE_NAME = 'adrian_cookie_consent';
	var COOKIE_DAYS = 365;
	var VALID = ['all', 'essential'];

	function getConsent() {
		var match = document.cookie.match(new RegExp('(?:^|;\\s*)' + COOKIE_NAME + '=([^;]*)'));
		var value = match ? decodeURIComponent(match[1]) : null;

		return VALID.indexOf(value) !== -1 ? value : null;
	}

	function setConsent(value) {
		var expires = new Date(Date.now() + COOKIE_DAYS * 24 * 60 * 60 * 1000);

		document.cookie = COOKIE_NAME + '=' + encodeURIComponent(value)
			+ '; expires=' + expires.toUTCString()
			+ '; path=/; SameSite=Lax'
			+ (window.location.protocol === 'https:' ? '; Secure' : '');
	}

	function clearConsent() {
		document.cookie = COOKIE_NAME + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax';
	}

	function show(banner) {
		banner.hidden = false;

		// Two frames so the browser paints the start state before the slide-in transition.
		window.requestAnimationFrame(function () {
			window.requestAnimationFrame(function () {
				banner.classList.add('is-visible');
			});
		});
	}

	function hide(banner) {
		var finished = false;

		function finish() {
			if (finished) {
				return;
			}

			finished = true;
			banner.hidden = true;
		}

		banner.classList.remove('is-visible');
		banner.addEventListener('transitionend', finish, { once: true });
		// Fallback for reduced motion / no transition support.
		window.setTimeout(finish, 600);
	}

	function init() {
		var banners = document.querySelectorAll('[data-adrian-cookie]');

		if (!banners.length) {
			return;
		}

		// If a choice was already made the banner simply stays hidden.
		var hasConsent = getConsent() !== null;

		Array.prototype.forEach.call(banners, function (banner) {
			banner.addEventListener('click', function (event) {
				var button = event.target.closest('[data-adrian-consent]');

				if (!button) {
					return;
				}

				var value = button.getAttribute('data-adrian-consent') === 'all' ? 'all' : 'essential';

				setConsent(value);
				Array.prototype.forEach.call(banners, hide);

				document.dispatchEvent(new CustomEvent('adrian-cookie-consent', { detail: { consent: value } }));
			});

			if (!hasConsent) {
				show(banner);
			}
		});
	}

	window.AdrianCookie = {
		get: getConsent,
		reset: function () {
			clearConsent();
			Array.prototype.forEach.call(document.querySelectorAll('[data-adrian-cookie]'), show);
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
