# Instrucțiuni pentru Claude Code — modul Joomla „Mega Meniu” (mod_megameniu)

## Scop

Construiește de la zero un modul de site Joomla, **mod_megameniu**, care afișează un meniu Joomla existent ca meniu orizontal cu submeniuri derulante pe mai multe niveluri și coloane („mega meniu”), plus meniu pentru telefon (buton hamburger).

Același pachet ZIP trebuie să se instaleze și să funcționeze pe:

| Joomla | PHP minim testat | PHP maxim testat |
|---|---|---|
| 3.10.x | 7.4 | 8.0 |
| 4.4.x | 7.4 | 8.3 |
| 5.x (5.2+) | 8.1 | 8.4 |
| 6.x | 8.3 | 8.4 |

Primul site pe care va fi folosit: **scoala1buzau.ro** (școală, instituție publică din România). Modulul înlocuiește extensia comercială DJ-MegaMenu la migrarea Joomla 3 → 5. Structura meniului e impusă legal și **nu se modifică**: modulul doar afișează meniul așa cum e definit în Joomla.

## Reguli obligatorii

1. **Cod original.** Nu copia cod, CSS, imagini, nume sau texte din DJ-MegaMenu sau din alte extensii comerciale. Nu folosi numele „DJ” nicăieri. Ia ca model doar comportamentul descris mai jos.
2. **Fără dependențe externe la rulare.** Fără jQuery, Bootstrap, Font Awesome sau CDN-uri. Doar PHP, JavaScript simplu (ES2017, fără build obligatoriu) și CSS.
3. **Un singur ZIP pentru toate versiunile.** Fără ramuri separate pe versiuni de Joomla. Diferențele dintre versiuni se izolează într-o clasă adaptor (vezi Arhitectura).
4. **Fără clase depreciate direct în logică.** `JFactory`, `JText`, `JHtml`, `JUri`, `JRoute` apar doar în adaptor, și doar ca rezervă pentru Joomla 3. Pe Joomla 4+ se folosesc clasele cu namespace (`Joomla\CMS\Factory`, `Joomla\CMS\Language\Text`, `Joomla\CMS\Router\Route`, `Joomla\CMS\Uri\Uri`, `Joomla\CMS\HTML\HTMLHelper`).
5. **Modulul nu are nevoie de pluginul „Behaviour - Backward Compatibility”** pe Joomla 5/6.
6. **Securitate.** Orice text afișat trece prin `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`. Linkurile se construiesc cu `Route::_()`. Nu se scrie nimic în baza de date.
7. **Accesibilitate WCAG 2.1 AA.** Site-ul aparține unei instituții publice și are deja bară de accesibilitate.
8. **Limba.** Fișiere de limbă `ro-RO` (implicită) și `en-GB`, cu diacritice corecte (ș, ț cu virgulă dedesubt).

## Arhitectura

```
mod_megameniu/
├── mod_megameniu.xml          # manifest unic, compatibil J3–J6
├── mod_megameniu.php          # punct de intrare clasic (merge pe toate versiunile)
├── src/
│   ├── Compat.php             # ADAPTORUL: singurul loc cu if (versiune Joomla)
│   ├── MenuTree.php           # citește elementele și construiește arborele
│   └── Renderer.php           # (opțional) logica de randare separată de layout
├── tmpl/
│   └── default.php            # HTML-ul meniului
├── media/
│   ├── css/megameniu.css
│   └── js/megameniu.js
├── language/
│   ├── ro-RO/ro-RO.mod_megameniu.ini
│   ├── ro-RO/ro-RO.mod_megameniu.sys.ini
│   ├── en-GB/en-GB.mod_megameniu.ini
│   └── en-GB/en-GB.mod_megameniu.sys.ini
├── build.sh                   # creează mod_megameniu-x.y.z.zip
└── tests/                     # testele descrise mai jos
```

**Clasele din `src/`:** prefix propriu fără namespace Joomla (de exemplu `MegaMeniuCompat`, `MegaMeniuTree`), încărcate cu `require_once` din `mod_megameniu.php`. Așa merg identic pe J3 (fără autoloader PSR-4 pentru module) și pe J4+.

**`Compat.php` oferă metode statice:**
- `version()`: versiunea majoră Joomla (3, 4, 5, 6);
- `app()`, `menu()`, `user()`, `lang()`;
- `route($link)`, `text($key)`, `sprintf()`;
- `addStyle($path)`, `addScript($path)`: pe J4+ prin WebAssetManager (`$app->getDocument()->getWebAssetManager()->registerAndUseStyle(...)`), pe J3 prin `HTMLHelper::_('stylesheet'/'script', ..., ['relative' => true, 'version' => 'auto'])`;
- `isJ4plus()`.

**Manifest unic (`mod_megameniu.xml`):**
- `<extension type="module" client="site" method="upgrade" version="3.10">` (J4+ ignoră atributul `version`);
- `<files>` cu `<filename module="mod_megameniu">mod_megameniu.php</filename>`, `src`, `tmpl`;
- `<media destination="mod_megameniu" folder="media">`;
- `<languages folder="language">`;
- parametrii în `<config><fields name="params"><fieldset name="basic">`. Folosește doar tipuri de câmp care există în toate versiunile: `menu`, `list`, `radio`, `text`, `color`, `number`, `spacer`, `menuitem`. **Nu folosi** `switcher` (doar J4+) și nici `layout="joomla.form.field.radio.switcher"`. Pentru da/nu folosește `radio` cu `class="btn-group btn-group-yesno"`.

## Citirea meniului (`MenuTree.php`)

- Sursa: `Compat::menu()->getItems('menutype', $menutype)`. Rezultatul e deja filtrat pe publicat, nivel de acces al vizitatorului și limbă. Verifică pe toate versiunile că elementele nepublicate și cele de la coș nu apar.
- Parametri respectați: „Nivel de start”, „Nivel de final”, „Afișează subelementele” (ca la mod_menu).
- Pentru fiecare element:
  - **tip `component`**: link `Route::_('index.php?Itemid=' . $id)`, sau elementul principal (Home) când e pagina implicită;
  - **tip `url`**: link extern, așa cum e;
  - **tip `alias`**: urmează elementul țintă (`aliasoptions`);
  - **tip `separator` / `heading`**: text fără link (titlu de coloană);
  - fereastră țintă (`browserNav`): 0 = aceeași fereastră; 1 = fereastră nouă, cu `target="_blank" rel="noopener noreferrer"`; 2 = popup, tratat ca fereastră nouă;
  - parametrii elementului: `menu-anchor_title`, `menu-anchor_css`, `menu-anchor_rel`, `menu_image`, `menu_text` (afișează sau nu textul), `menu_show` (ascunde elementul din meniu, J3.5+);
  - starea activă: elementul curent primește `current` și `aria-current="page"`, iar toți părinții lui primesc `active`.
- Ordinea elementelor = ordinea din administrarea Joomla (`lft`). **Nu reordona nimic.**

## Parametrii modulului

Fieldset „Meniu”:
- `menutype` (meniul afișat, obligatoriu), `startLevel` (implicit 1), `endLevel` (0 = toate), `showAllChildren` (da);
- `columnsMode`: `auto` (subelementele de nivel 2 cu copii devin coloane) sau `list` (dropdown simplu pe niveluri);
- `maxColumns` (implicit 4);
- `openOn`: `hover` (cu întârziere de 150 ms la închidere) sau `click`.

Fieldset „Aspect” (toate devin variabile CSS pe containerul modulului, fără CSS generat pe server):
- `barBg`, `barColor`, `barHoverBg`, `barHoverColor`, `activeBg`, `activeColor`;
- `dropBg`, `dropColor`, `dropHoverBg`, `dropHoverColor`, `dropBorder`;
- `fontSize`, `fontFamily` (text liber, implicit `inherit`), `uppercase` (da/nu);
- `alignment` (stânga, centru, dreapta, justificat), `dropWidth` (`auto` sau px), `radius`, `shadow` (da/nu);
- `sticky` (da/nu: meniul rămâne sus la derulare).

Fieldset „Mobil”:
- `mobileBreakpoint` (px, implicit 992);
- `mobileMode`: `accordion` (se deschide sub buton) sau `offcanvas` (panou lateral);
- `mobileLabel` (implicit „Meniu”).

Fieldset „Avansat”: `moduleclass_sfx` și `layout`, standard Joomla.

## HTML (tmpl/default.php)

```html
<nav class="mm" id="mm-{moduleId}" aria-label="{titlul modulului sau „Meniu principal”}"
     style="--mm-bar-bg:…; --mm-bar-color:…; …" data-open-on="hover" data-breakpoint="992">
  <button class="mm-toggle" type="button" aria-expanded="false" aria-controls="mm-list-{moduleId}">
    <span class="mm-toggle-icon" aria-hidden="true"></span><span>Meniu</span>
  </button>
  <ul class="mm-list" id="mm-list-{moduleId}">
    <li class="mm-item mm-has-sub">
      <a href="…" class="mm-link" aria-haspopup="true" aria-expanded="false">Despre Instituție</a>
      <button class="mm-sub-toggle" type="button" aria-expanded="false" aria-label="Deschide submeniul Despre Instituție"></button>
      <div class="mm-drop mm-cols-3">
        <ul class="mm-col">…</ul>
      </div>
    </li>
  </ul>
</nav>
```

- ID unic pe modul, ca să meargă mai multe instanțe pe aceeași pagină.
- Elementele de nivel 1 cu copii au atât link (dacă tipul îl permite), cât și un buton separat de deschidere. Asta e necesar pentru tastatură și ecran tactil.
- Elementele de tip `heading`/`separator` se randează ca `<span class="mm-heading">`.

## JavaScript (media/js/megameniu.js)

Fără dependențe. Se inițializează la `DOMContentLoaded` pentru fiecare `.mm`.
- **Desktop:** deschidere la hover sau click, după `data-open-on`; un singur submeniu de nivel 1 deschis odată; închidere la click în afara meniului și la `Escape`, cu focusul întors pe elementul părinte.
- **Tastatură** (modelul WAI-ARIA „disclosure navigation”, fără `role="menu"`): `Tab`/`Shift+Tab` normal; `Enter`/`Space` pe butonul de submeniu deschide sau închide; `ArrowDown` din butonul deschis mută focusul pe primul link din submeniu; `Escape` închide.
- **Ecran tactil:** prima atingere pe un element cu submeniu îl deschide, a doua navighează.
- **Mobil** (lățime sub `data-breakpoint`): butonul hamburger afișează lista; submeniurile devin acordeon; la `offcanvas`, panou lateral cu fundal semitransparent, blocarea derulării paginii și capcană de focus.
- Actualizează mereu `aria-expanded` pe butoane.
- Respectă `prefers-reduced-motion`: fără animații.
- Funcționează și fără JavaScript: pe desktop, CSS-ul afișează submeniurile la `:hover` și `:focus-within`.

## CSS (media/css/megameniu.css)

- Toate culorile și dimensiunile din variabile `--mm-*`, cu valori implicite neutre.
- Prefix `.mm` pe toate selectoarele, ca să nu se amestece cu tema (Cassiopeia pe J4/5/6, Protostar pe J3).
- Contur de focus vizibil (`:focus-visible`, minim 2 px, contrast 3:1).
- Contrast text/fundal minim 4.5:1 la valorile implicite.
- Zonă de atingere minimă 44×44 px pe mobil.
- Fără `!important`, cu excepția regulilor de ascundere pe mobil, dacă e nevoie.
- Compatibil cu bara de accesibilitate a site-ului (mărirea fontului, contrast ridicat): folosește unități `rem`/`em`, nu px fix pentru text.

## Teste (obligatorii înainte de livrare)

1. **Mediu de test** cu Docker (`tests/docker-compose.yml`), câte o instanță pentru fiecare combinație:
   - Joomla 3.10 + PHP 7.4,
   - Joomla 4.4 + PHP 8.1,
   - Joomla 5.x + PHP 8.3,
   - Joomla 6.x + PHP 8.4,
   - toate cu MariaDB 10.6.
2. **Date de test:** un script care creează meniul „Main Menu” cu aceeași structură ca `structura-meniuri-referinta.md` (9 elemente pe nivelul 1, 3 niveluri, 1 element nepublicat, 1 la coș, tipuri: articol, listă categorii, blog, URL extern, jDownloads/Phoca, simulate cu tip `url` dacă extensiile lipsesc).
3. **Teste automate** cu Playwright (`tests/e2e/`), pe fiecare instanță:
   - instalarea ZIP-ului reușește din Extensii → Instalare;
   - modulul publicat pe o poziție afișează exact elementele publicate, în ordinea din administrare, fără cel nepublicat și cel de la coș;
   - submeniurile se deschid cu mouse, tastatură (`Tab`, `Enter`, `Escape`) și pe mobil (viewport 375×812);
   - elementul curent are `aria-current="page"`;
   - linkurile externe cu „fereastră nouă” au `target="_blank"` și `rel="noopener noreferrer"`;
   - **zero erori** în consola browserului și în jurnalul PHP (`error_reporting` maxim pe instanțele de test);
   - audit axe-core fără încălcări de nivel „serious” sau „critical”.
4. **Test PHP static:** `php -l` pe toate fișierele cu PHP 7.4 și 8.4. PHPStan nivel 5 dacă se poate configura fără efort mare.
5. Raport final în `tests/RAPORT.md`: ce s-a testat, pe ce versiuni, rezultate și capturi de ecran desktop/mobil.

## Livrare

- `build.sh` produce `dist/mod_megameniu-1.0.0.zip`.
- `README.md` în română: instalare, configurare, cum se înlocuiește DJ-MegaMenu pe un site existent (vezi mai jos), limitări cunoscute.
- `CHANGELOG.md`.
- Licență GPL v2 sau ulterioară, ca Joomla.

## Înlocuirea DJ-MegaMenu pe scoala1buzau.ro (pentru README)

1. Pe copia de test, după upgrade: instalează mod_megameniu.
2. Creează un modul nou: meniul „Main Menu”, aceeași poziție în care era modulul DJ-MegaMenu, aceleași pagini asociate („Toate”).
3. Copiază culorile din modulul DJ-MegaMenu existent (sau din CSS-ul site-ului actual) în parametrii „Aspect”.
4. Dezactivează modulul DJ-MegaMenu (nu-l șterge până la validare).
5. Verifică meniul față de `structura-meniuri-referinta.md`, element cu element.

## Ordinea de lucru recomandată pentru Claude Code

1. Creează scheletul de foldere și manifestul; instalează pe J3.10 și pe J5 un modul care afișează doar „Hello”. Confirmă că același ZIP merge pe ambele înainte de orice altceva.
2. `Compat.php` + `MenuTree.php` + layout HTML fără CSS/JS; verifică arborele pe toate versiunile.
3. CSS desktop, apoi JavaScript desktop, apoi mobil.
4. Parametri de aspect (variabile CSS).
5. Testele Playwright și axe pe toată matricea.
6. README, build, raport.

După fiecare etapă, rulează testele existente pe toată matricea de versiuni și raportează scurt ce merge și ce nu.
