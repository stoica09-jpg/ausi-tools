# ausi-tools – reguli de lucru

Repo cu extensii Joomla 4 / 5 (module, pluginuri etc.) create pentru Adrian.

## Regula obligatorie pentru FIECARE extensie / modul nou

1. **Un folder separat** la rădăcina repo-ului, cu numele extensiei
   (`mod_*`, `plg_*`, `com_*`, `tpl_*`, `lib_*`, `pkg_*`).
2. **Un fișier `CITESTE-MA.txt`** în acel folder, în limba română, UTF-8, cu secțiunile:
   - CE FACE
   - 1. INSTALARE (System → Install → Extensions → Upload Package File)
   - 2. ACTIVARE PE SITE (poziție, Published, Menu Assignment)
   - 3. SETĂRI (fiecare opțiune din manifest explicată)
   - 4. UTILIZARE (ce vede / face vizitatorul)
   - 5. FOLOSIRE AVANSATĂ (pentru webmaster: clase CSS, API JS, PHP)
   - 6. PROBLEME FRECVENTE
   - 7. DEZINSTALARE
   - CONȚINUTUL ARHIVEI (lista fișierelor)
   Modele: `mod_adrian_cookie/CITESTE-MA.txt`, `mod_adrian_accessibility_tools/CITESTE-MA.txt`.
3. **O arhivă `.zip` separată** pentru fiecare extensie, cu `CITESTE-MA.txt` inclus în ea:
   rulează `./build.sh` → `dist/<extensie>.zip`. Scriptul găsește singur folderele noi
   și se oprește cu eroare dacă lipsește `CITESTE-MA.txt`.
   Arhivele se trimit utilizatorului după build (`dist/` nu se comite).
4. Actualizează tabelul din `README.md` cu extensia nouă.

## Convenții Joomla folosite

- Compatibil Joomla 4 și 5: manifest `.xml`, fișier principal `.php`, `tmpl/default.php`.
- CSS / JS doar prin WebAssetManager: `media/joomla.asset.json`,
  `$wa->getRegistry()->addExtensionRegistryFile('<extensie>')`.
- Fișiere de limbă `en-GB` și `ro-RO` (`.ini` + `.sys.ini`).
- Fără biblioteci externe (FontAwesome, CDN-uri); iconițe ca SVG inline.
- Prefix de nume/clase: `adrian-` / `Adrian`.

## Verificări înainte de commit

`php -l` pe fișierele PHP, `xmllint --noout` pe manifeste, `node --check` pe JS,
`parse_ini_file` pe fișierele de limbă, apoi `./build.sh`.
