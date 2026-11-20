# Raport teste — mod_megameniu 1.0.1

Data: 28.10.2026. Mediu: Ubuntu 24.04, PHP 8.3.6, MariaDB 10.11, Chromium (Playwright), axe-core 4.10.

## 1. Randare fără Joomla (tests/smoke/render.php)
Structura reală a meniului școlii (43 de elemente publicate, 1 nepublicat, 1 la coș, 1 ascuns cu cod injectat, 1 link extern).
```
OK (mega/accordion): 44 elemente, pagina de test: /home/claude/mod_megameniu/tests/smoke/out-mega-accordion.html
OK (mega/offcanvas): 44 elemente, pagina de test: /home/claude/mod_megameniu/tests/smoke/out-mega-offcanvas.html
OK (list/accordion): 44 elemente, pagina de test: /home/claude/mod_megameniu/tests/smoke/out-list-accordion.html
OK (list/offcanvas): 44 elemente, pagina de test: /home/claude/mod_megameniu/tests/smoke/out-list-offcanvas.html
```

## 2. Browser, pagini de test (tests/e2e/run.js)
```
✔ desktop: ordinea elementelor de nivel 1
✔ desktop: butonul mobil e ascuns
✔ hover: submeniul „Despre Instituție” se deschide
✔ hover: submeniul are coloane (grid)
✔ hover: aria-expanded=true
✔ hover: submeniul se închide la ieșire
✔ tastatură: focus pe butonul de submeniu
✔ tastatură: Enter deschide submeniul
✔ tastatură: Escape închide submeniul
✔ tastatură: după Escape focusul revine pe buton
✔ tastatură: ArrowDown deschide și duce focusul în submeniu
✔ tastatură: submeniul se închide când focusul iese
✔ aria-current pe „Secretariat”
✔ ramura activă marcată pe nivelul 1
✔ submeniul „Contact” rămâne în ecran
✔ toate submeniurile rămân în ecran
✔ „Proiecte” în ecran la 1024px
✔ „Proiecte” în ecran la 1100px
✔ clic în afară închide submeniurile
✔ axe fără încălcări serioase/critice (desktop, submeniu deschis)
✔ desktop: fără erori JavaScript
✔ listă: submeniul simplu se deschide
✔ listă: nivelul 3 apare lateral
✔ listă: fără erori JavaScript
✔ mobil: butonul „Meniu” vizibil
✔ mobil: lista ascunsă inițial
✔ mobil: lista apare la apăsare
✔ mobil: aria-expanded pe buton
✔ mobil: ramura activă deschisă
✔ mobil: acordeon „Proiecte” se deschide
✔ mobil: celălalt acordeon rămâne deschis
✔ mobil: zone de atingere ≥ 44px
✔ mobil: fără derulare orizontală
✔ axe fără încălcări serioase/critice (mobil acordeon)
✔ mobil: fără erori JavaScript
✔ offcanvas: panoul apare
✔ offcanvas: fundal semitransparent
✔ offcanvas: butonul „Închide meniul” are focus
✔ offcanvas: focusul rămâne în panou
✔ offcanvas: Escape închide panoul
✔ offcanvas: focus înapoi pe butonul „Meniu”
✔ offcanvas: fără erori JavaScript
✔ fără JS: submeniul se deschide la hover
✔ mișcare redusă: fără tranziții

44/44 verificări trecute.
```

## 3. Joomla 5.3.4 real, tema Cassiopeia (tests/e2e/joomla.js)
Instalare din ZIP prin `php cli/joomla.php extension:install`: reușită. Meniul școlii creat cu tests/joomla/seed.php. Raportare erori PHP: maximă.
```
✔ [joomla-5.3] pagina se încarcă (HTTP 200)
✔ [joomla-5.3] fără avertismente PHP în pagină
✔ [joomla-5.3] ordinea de nivel 1
✔ [joomla-5.3] număr elemente randate = 44 (fără cel nepublicat)
✔ [joomla-5.3] hover deschide submeniul cu coloane
✔ [joomla-5.3] tastatură: Enter deschide
✔ [joomla-5.3] tastatură: Escape închide
✔ [joomla-5.3] link extern: target și rel
✔ [joomla-5.3] axe: fără încălcări serioase/critice
✔ [joomla-5.3] fără erori JavaScript
✔ [joomla-5.3] mobil: buton Meniu vizibil
✔ [joomla-5.3] mobil: lista se deschide
✔ [joomla-5.3] mobil: acordeon Proiecte
✔ [joomla-5.3] mobil: fără derulare orizontală

14/14 verificări trecute.
```

## 4. De testat
- **Joomla 3.10.12 pe PHP 7.4 (copia test.scoala1buzau.ro), versiunea 1.0.0:** instalare reușită; formularul modulului afișat corect în română; pe pagina „Istoric”: 43 de elemente, ordinea corectă, elementul nepublicat ascuns, `aria-current` pe „Istoric”, ramura „Media” activă, CSS/JS încărcate, fără avertismente PHP. Problemă găsită: submeniul „Proiecte” (titluri lungi) ieșea din ecran pe stânga, rezolvată în 1.0.1.
- **Joomla 4.4:** pe copia de test, după primul upgrade.
- **Joomla 6:** la prima instalare.

Capturi de ecran: tests/screenshots/.
