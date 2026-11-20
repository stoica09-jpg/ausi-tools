/*
 * Teste în browser (Chromium/Playwright) pe paginile generate de tests/smoke/render.php.
 * Rulare: node tests/e2e/run.js
 */
const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const AXE = fs.readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');
const SMOKE = path.resolve(__dirname, '../smoke');
const SHOTS = path.resolve(__dirname, '../screenshots');
fs.mkdirSync(SHOTS, { recursive: true });

const http = require('http');
const ROOT = path.resolve(__dirname, '../..');
const TYPES = { '.html': 'text/html; charset=utf-8', '.css': 'text/css', '.js': 'application/javascript' };
let BASE = '';
function startServer() {
	return new Promise((resolve) => {
		const srv = http.createServer((req, res) => {
			const f = path.join(ROOT, decodeURIComponent(req.url.split('?')[0]));
			if (!f.startsWith(ROOT) || !fs.existsSync(f) || fs.statSync(f).isDirectory()) { res.writeHead(404); return res.end(); }
			res.writeHead(200, { 'Content-Type': TYPES[path.extname(f)] || 'application/octet-stream' });
			fs.createReadStream(f).pipe(res);
		});
		srv.listen(0, '127.0.0.1', () => { BASE = 'http://127.0.0.1:' + srv.address().port + '/tests/smoke/'; resolve(srv); });
	});
}

const results = [];
function check(name, cond, detail) {
	results.push({ name, ok: !!cond, detail: detail || '' });
}

async function openPage(browser, file, viewport) {
	const page = await browser.newPage({ viewport });
	const errors = [];
	page.on('pageerror', (e) => errors.push(String(e)));
	page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
	await page.goto(BASE + file);
	await page.waitForSelector('nav.mm.mm-js');
	return { page, errors };
}

async function axe(page, label) {
	await page.addScriptTag({ content: AXE });
	const r = await page.evaluate(async () => {
		const res = await window.axe.run('nav.mm', { runOnly: ['wcag2a', 'wcag2aa', 'wcag21aa'] });
		return res.violations.map((v) => ({ id: v.id, impact: v.impact, n: v.nodes.length }));
	});
	const bad = r.filter((v) => v.impact === 'serious' || v.impact === 'critical');
	check(`axe fără încălcări serioase/critice (${label})`, bad.length === 0, JSON.stringify(r));
}

(async () => {
	const server = await startServer();
	const browser = await chromium.launch();

	// ---------- Desktop, mod coloane ----------
	{
		const { page, errors } = await openPage(browser, 'out-mega-accordion.html', { width: 1366, height: 900 });

		const tops = await page.$$eval('.mm-list > .mm-l1', (els) => els.map((e) => e.querySelector('.mm-text').textContent));
		check('desktop: ordinea elementelor de nivel 1', tops.join('|') === 'Home|Despre Instituție|Media|Examene|Proiecte|Concursuri|Documente|Contact', tops.join('|'));
		check('desktop: butonul mobil e ascuns', !(await page.isVisible('.mm-toggle')));

		// Hover deschide, mouse afară închide (după întârziere).
		await page.hover('.item-321 > .mm-link');
		await page.waitForTimeout(50);
		check('hover: submeniul „Despre Instituție” se deschide', await page.isVisible('#mm-87-sub-321'));
		check('hover: submeniul are coloane (grid)', (await page.$eval('#mm-87-sub-321', (e) => getComputedStyle(e).display)) === 'grid');
		check('hover: aria-expanded=true', (await page.getAttribute('.item-321 > .mm-sub-toggle', 'aria-expanded')) === 'true');
		await page.screenshot({ path: path.join(SHOTS, 'desktop-mega-hover.png') });
		await page.mouse.move(700, 800);
		await page.waitForTimeout(300);
		check('hover: submeniul se închide la ieșire', !(await page.isVisible('#mm-87-sub-321')));

		// Tastatură.
		await page.focus('.item-321 > .mm-link');
		await page.keyboard.press('Tab'); // pe butonul de submeniu
		check('tastatură: focus pe butonul de submeniu', await page.evaluate(() => document.activeElement.classList.contains('mm-sub-toggle')));
		await page.keyboard.press('Enter');
		check('tastatură: Enter deschide submeniul', await page.isVisible('#mm-87-sub-321'));
		await page.keyboard.press('Escape');
		check('tastatură: Escape închide submeniul', !(await page.isVisible('#mm-87-sub-321')));
		check('tastatură: după Escape focusul revine pe buton', await page.evaluate(() => document.activeElement.classList.contains('mm-sub-toggle')));
		await page.keyboard.press('ArrowDown');
		const firstText = await page.evaluate(() => document.activeElement.textContent.trim());
		check('tastatură: ArrowDown deschide și duce focusul în submeniu', firstText === 'Legislație', firstText);
		// Tab până iese din submeniu -> se închide.
		for (let i = 0; i < 25; i++) {
			await page.keyboard.press('Tab');
		}
		check('tastatură: submeniul se închide când focusul iese', !(await page.isVisible('#mm-87-sub-321')));

		// Elementul curent.
		check('aria-current pe „Secretariat”', (await page.$eval('[aria-current="page"]', (e) => e.textContent.trim())) === 'Secretariat');
		check('ramura activă marcată pe nivelul 1', await page.$eval('.item-321', (e) => e.classList.contains('mm-active')));

		// Submeniul de lângă marginea dreaptă nu iese din ecran.
		await page.hover('.item-186 > .mm-link');
		await page.waitForTimeout(50);
		const box = await page.$eval('#mm-87-sub-186', (e) => e.getBoundingClientRect().right);
		check('submeniul „Contact” rămâne în ecran', box <= 1366, String(box));

		// Fiecare submeniu rămâne integral în ecran (inclusiv cele cu titluri lungi).
		const outside = [];
		for (const id of [321, 106, 129, 137, 141, 186]) {
			await page.hover(`.item-${id} > .mm-link`);
			await page.waitForTimeout(40);
			const r = await page.$eval(`#mm-87-sub-${id}`, (e) => { const b = e.getBoundingClientRect(); return [b.left, b.right]; });
			if (r[0] < 0 || r[1] > 1366) outside.push(id + ':' + r.map(Math.round).join('-'));
		}
		check('toate submeniurile rămân în ecran', outside.length === 0, outside.join(' '));
		for (const vw of [1024, 1100]) {
			await page.setViewportSize({ width: vw, height: 900 });
			await page.hover('.item-137 > .mm-link');
			await page.waitForTimeout(40);
			const r = await page.$eval('#mm-87-sub-137', (e) => { const b = e.getBoundingClientRect(); return [b.left, b.right]; });
			check(`„Proiecte” în ecran la ${vw}px`, r[0] >= 0 && r[1] <= vw, r.map(Math.round).join('-'));
		}
		await page.setViewportSize({ width: 1366, height: 900 });

		// Clic în afara meniului.
		await page.click('.item-137 > .mm-sub-toggle');
		await page.mouse.click(10, 880);
		await page.waitForTimeout(250);
		check('clic în afară închide submeniurile', !(await page.isVisible('#mm-87-sub-137')));

		// Contrast și accesibilitate cu submeniul deschis.
		await page.click('.item-321 > .mm-sub-toggle');
		await axe(page, 'desktop, submeniu deschis');
		check('desktop: fără erori JavaScript', errors.length === 0, errors.join(' / '));
		await page.close();
	}

	// ---------- Desktop, mod listă (flyout pe niveluri) ----------
	{
		const { page, errors } = await openPage(browser, 'out-list-accordion.html', { width: 1366, height: 900 });
		await page.hover('.item-321 > .mm-link');
		await page.waitForTimeout(50);
		check('listă: submeniul simplu se deschide', await page.isVisible('#mm-87-sub-321'));
		await page.hover('#mm-87-sub-321 .item-324 > .mm-link');
		await page.waitForTimeout(50);
		check('listă: nivelul 3 apare lateral', await page.isVisible('#mm-87-sub-321 .item-324 > .mm-fly'));
		await page.screenshot({ path: path.join(SHOTS, 'desktop-list-flyout.png') });
		check('listă: fără erori JavaScript', errors.length === 0, errors.join(' / '));
		await page.close();
	}

	// ---------- Mobil, acordeon ----------
	{
		const { page, errors } = await openPage(browser, 'out-mega-accordion.html', { width: 375, height: 812 });
		check('mobil: butonul „Meniu” vizibil', await page.isVisible('.mm-toggle'));
		check('mobil: lista ascunsă inițial', !(await page.isVisible('.mm-list')));
		await page.click('.mm-toggle');
		check('mobil: lista apare la apăsare', await page.isVisible('.mm-list'));
		check('mobil: aria-expanded pe buton', (await page.getAttribute('.mm-toggle', 'aria-expanded')) === 'true');
		check('mobil: ramura activă deschisă', await page.isVisible('#mm-87-sub-321'));
		await page.click('.item-137 > .mm-sub-toggle');
		check('mobil: acordeon „Proiecte” se deschide', await page.isVisible('#mm-87-sub-137'));
		check('mobil: celălalt acordeon rămâne deschis', await page.isVisible('#mm-87-sub-321'));
		const tooSmall = await page.$$eval('.mm-list a, .mm-list button', (els) => els.filter((e) => e.offsetParent && e.getBoundingClientRect().height < 44).map((e) => e.textContent.trim()));
		check('mobil: zone de atingere ≥ 44px', tooSmall.length === 0, tooSmall.slice(0, 5).join(' | '));
		const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
		check('mobil: fără derulare orizontală', !overflow);
		await page.screenshot({ path: path.join(SHOTS, 'mobile-accordion.png'), fullPage: true });
		await axe(page, 'mobil acordeon');
		check('mobil: fără erori JavaScript', errors.length === 0, errors.join(' / '));
		await page.close();
	}

	// ---------- Mobil, panou lateral ----------
	{
		const { page, errors } = await openPage(browser, 'out-mega-offcanvas.html', { width: 375, height: 812 });
		await page.click('.mm-toggle');
		await page.waitForTimeout(300);
		check('offcanvas: panoul apare', await page.isVisible('.mm-list'));
		check('offcanvas: fundal semitransparent', await page.isVisible('.mm-backdrop'));
		check('offcanvas: butonul „Închide meniul” are focus', await page.evaluate(() => document.activeElement.classList.contains('mm-close')));
		// Capcana de focus: Shift+Tab din primul element duce la ultimul.
		await page.keyboard.press('Shift+Tab');
		const inPanel = await page.evaluate(() => !!document.activeElement.closest('.mm-list'));
		check('offcanvas: focusul rămâne în panou', inPanel);
		await page.screenshot({ path: path.join(SHOTS, 'mobile-offcanvas.png') });
		await page.keyboard.press('Escape');
		await page.keyboard.press('Escape');
		await page.waitForTimeout(300);
		check('offcanvas: Escape închide panoul', !(await page.isVisible('.mm-backdrop')));
		check('offcanvas: focus înapoi pe butonul „Meniu”', await page.evaluate(() => document.activeElement.classList.contains('mm-toggle')));
		check('offcanvas: fără erori JavaScript', errors.length === 0, errors.join(' / '));
		await page.close();
	}

	// ---------- Fără JavaScript ----------
	{
		const ctx = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 1366, height: 900 } });
		const page = await ctx.newPage();
		await page.goto(BASE + 'out-mega-accordion.html');
		await page.hover('.item-321 > .mm-link');
		check('fără JS: submeniul se deschide la hover', await page.isVisible('#mm-87-sub-321'));
		await ctx.close();
	}

	// ---------- Mișcare redusă ----------
	{
		const ctx = await browser.newContext({ reducedMotion: 'reduce', viewport: { width: 1366, height: 900 } });
		const page = await ctx.newPage();
		await page.goto(BASE + 'out-mega-accordion.html');
		const t = await page.$eval('.mm-caret', (e) => getComputedStyle(e).transitionDuration);
		check('mișcare redusă: fără tranziții', t === '0s', t);
		await ctx.close();
	}

	await browser.close();
	server.close();

	const failed = results.filter((r) => !r.ok);
	const lines = results.map((r) => (r.ok ? '✔ ' : '✘ ') + r.name + (r.ok || !r.detail ? '' : '  → ' + r.detail));
	console.log(lines.join('\n'));
	console.log(`\n${results.length - failed.length}/${results.length} verificări trecute.`);
	fs.writeFileSync(path.resolve(__dirname, 'last-run.txt'), lines.join('\n') + `\n\n${results.length - failed.length}/${results.length}\n`);
	process.exit(failed.length ? 1 : 0);
})();
