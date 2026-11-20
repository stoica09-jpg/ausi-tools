/*
 * Teste pe un site Joomla real (instalat local sau copia de test).
 * Rulare: node tests/e2e/joomla.js http://127.0.0.1:8085/ eticheta
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.argv[2];
const LABEL = process.argv[3] || 'joomla';
const AXE = fs.readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');
const SHOTS = path.resolve(__dirname, '../screenshots');
fs.mkdirSync(SHOTS, { recursive: true });

const results = [];
const check = (name, cond, detail) => results.push({ name: `[${LABEL}] ${name}`, ok: !!cond, detail: detail || '' });

(async () => {
	const browser = await chromium.launch();

	// Desktop
	let page = await browser.newPage({ viewport: { width: 1366, height: 900 } });
	const errors = [];
	page.on('pageerror', (e) => errors.push(String(e)));
	// Erorile de resurse ale nucleului Joomla (ex. 404 sub serverul PHP de test) nu țin de modul.
	page.on('console', (m) => { if (m.type() === 'error' && !/Failed to load resource/.test(m.text())) errors.push(m.text()); });
	page.on('response', (r) => { if (r.status() >= 400 && /megameniu/.test(r.url())) errors.push(r.status() + ' ' + r.url()); });
	const resp = await page.goto(BASE);
	check('pagina se încarcă (HTTP 200)', resp.status() === 200, String(resp.status()));
	await page.waitForSelector('nav.mm.mm-js', { timeout: 10000 });

	const body = await page.content();
	check('fără avertismente PHP în pagină', !/(<b>(Warning|Notice|Deprecated|Fatal error)<\/b>|Warning: |Deprecated: )/.test(body));

	const tops = await page.$$eval('nav.mm .mm-list > .mm-l1', (els) => els.map((e) => e.querySelector('.mm-text').textContent.trim()));
	check('ordinea de nivel 1', tops.join('|') === 'Home|Despre Instituție|Media|Examene|Proiecte|Concursuri|Documente|Contact', tops.join('|'));

	const total = await page.$$eval('nav.mm .mm-item, nav.mm .mm-col', (els) => els.length);
	check('număr elemente randate = 44 (fără cel nepublicat)', total === 44, String(total));

	const li = page.locator('nav.mm .mm-l1', { hasText: 'Despre Instituție' }).first();
	await li.locator('> .mm-link').hover();
	await page.waitForTimeout(80);
	check('hover deschide submeniul cu coloane', await li.locator('> .mm-drop.mm-mega').isVisible());
	await page.screenshot({ path: path.join(SHOTS, `${LABEL}-desktop.png`) });

	await li.locator('> .mm-sub-toggle').focus();
	await page.mouse.move(600, 800);
	await page.waitForTimeout(250);
	await page.keyboard.press('Escape');
	await page.keyboard.press('Enter');
	check('tastatură: Enter deschide', await li.locator('> .mm-drop').isVisible());
	await page.keyboard.press('Escape');
	check('tastatură: Escape închide', !(await li.locator('> .mm-drop').isVisible()));

	const ext = page.locator('nav.mm a', { hasText: 'Site ISJ Buzău' }).first();
	check('link extern: target și rel', (await ext.getAttribute('target')) === '_blank' && (await ext.getAttribute('rel')) === 'noopener noreferrer');

	await li.locator('> .mm-sub-toggle').click();
	await page.addScriptTag({ content: AXE });
	const viol = await page.evaluate(async () => (await window.axe.run('nav.mm', { runOnly: ['wcag2a', 'wcag2aa', 'wcag21aa'] })).violations.map((v) => ({ id: v.id, impact: v.impact })));
	check('axe: fără încălcări serioase/critice', !viol.some((v) => v.impact === 'serious' || v.impact === 'critical'), JSON.stringify(viol));
	check('fără erori JavaScript', errors.length === 0, errors.join(' / '));
	await page.close();

	// Mobil
	page = await browser.newPage({ viewport: { width: 375, height: 812 } });
	await page.goto(BASE);
	await page.waitForSelector('nav.mm.mm-js');
	check('mobil: buton Meniu vizibil', await page.isVisible('nav.mm .mm-toggle'));
	await page.click('nav.mm .mm-toggle');
	check('mobil: lista se deschide', await page.isVisible('nav.mm .mm-list'));
	const m = page.locator('nav.mm .mm-l1', { hasText: 'Proiecte' }).first();
	await m.locator('> .mm-sub-toggle').click();
	check('mobil: acordeon Proiecte', await m.locator('> .mm-drop').isVisible());
	check('mobil: fără derulare orizontală', !(await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth)));
	await page.screenshot({ path: path.join(SHOTS, `${LABEL}-mobil.png`), fullPage: true });
	await page.close();

	await browser.close();
	const failed = results.filter((r) => !r.ok);
	console.log(results.map((r) => (r.ok ? '✔ ' : '✘ ') + r.name + (r.ok || !r.detail ? '' : '  → ' + r.detail)).join('\n'));
	console.log(`\n${results.length - failed.length}/${results.length} verificări trecute.`);
	process.exit(failed.length ? 1 : 0);
})();
