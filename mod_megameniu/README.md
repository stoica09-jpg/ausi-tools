# Adrian Megameniu (mod_megameniu) — mega meniu pentru Joomla 3.10, 4, 5 și 6

Modul de site care afișează un meniu Joomla existent ca bară orizontală cu submeniuri pe coloane („mega meniu”) și meniu pentru telefon. Nu are dependențe externe: fără jQuery, Bootstrap sau CDN. Același pachet ZIP se instalează pe toate versiunile.

## Instalare
1. Administrare Joomla → **Extensii → Administrare → Instalare** (în Joomla 3: *Extensii → Instalare*) → **Încărcare fișier pachet** → `mod_megameniu-1.0.2.zip`.
2. **Conținut → Module de site → Nou → Adrian Megameniu**.
3. La **Meniul afișat** alegeți meniul („Main Menu”), apoi poziția și paginile pe care apare.

## Setări
- **Meniu:** nivel de start și de final, tip submeniu (*Coloane* sau *Listă pe niveluri*), număr maxim de coloane, deschidere la trecerea mouse-ului sau la clic.
- **Aspect:** culorile barei, ale elementului activ și ale submeniului, fontul și mărimea lui, majuscule, aliniere, lățimea submeniului, colțuri, umbră, meniu fix la derulare.
- **Mobil:** pragul (implicit 992 px) și tipul de meniu, *Se deschide sub buton* sau *Panou lateral*.

Modulul respectă exact ordinea și nivelurile din administrarea meniului. Elementele nepublicate, cele de la coș, cele marcate „Afișează în meniu: Nu” și cele la care vizitatorul nu are acces nu apar.

## Accesibilitate
- Se poate folosi doar cu tastatura: `Tab`, `Enter`/`Space` pe butonul de submeniu, `↓` pentru a intra în submeniu, `Esc` pentru a-l închide.
- Folosește `aria-expanded`, `aria-controls` și `aria-current="page"`, iar butoanele au etichete pentru cititoare de ecran.
- Contur de focus vizibil, zone de atingere de minimum 44 px pe mobil, iar animațiile se opresc când sistemul cere mișcare redusă.
- Fără JavaScript, submeniurile se deschid tot, la trecerea mouse-ului sau la focus.

## Înlocuirea DJ-MegaMenu pe scoala1buzau.ro
1. Pe copia de test, instalați modulul.
2. Creați un modul nou cu meniul „Main Menu”, pe **aceeași poziție** cu modulul DJ-MegaMenu și pe aceleași pagini.
3. Copiați culorile site-ului în fila **Aspect**.
4. **Dezactivați** modulul DJ-MegaMenu, fără să-l ștergeți până la validare.
5. Verificați meniul element cu element față de `structura-meniuri-referinta.md`.

## Personalizare din temă
Puteți suprascrie layout-ul fără să modificați modulul: copiați `tmpl/default.php` în `templates/<tema>/html/mod_megameniu/default.php`. Stilurile se pot ajusta prin variabilele CSS `--mm-*` puse pe `.mm`.

## Dezvoltare și teste
- `bash build.sh` creează `dist/mod_megameniu-<versiune>.zip`.
- `php tests/smoke/render.php` randează meniul școlii fără Joomla și verifică ordinea, elementele ascunse, protecția la cod injectat (XSS) și `aria-current`.
- `node tests/e2e/run.js` rulează testele în browser (Chromium): mouse, tastatură, mobil, panou lateral, fără JavaScript, mișcare redusă și axe-core (WCAG 2.1 AA).
- `php tests/joomla/seed.php <cale-site>` creează pe un site Joomla de test meniul școlii și o instanță a modulului.
- `node tests/e2e/joomla.js <url> <eticheta>` rulează testele pe un site Joomla real.

Rezultatele sunt în `tests/RAPORT.md`.

## Limitări cunoscute
- Coloanele se formează automat: un subelement care are la rândul lui subelemente devine coloană. Nu se pot alege coloanele manual pentru fiecare element.
- Pe Joomla 6, modulul se bazează pe suportul pentru module clasice (`mod_megameniu.php`). Verificați la prima instalare pe Joomla 6.

Licență: GNU GPL v2 sau ulterioară.
