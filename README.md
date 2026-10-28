# ausi-tools

## Adrian jDownloads (`com_adrianjdownloads`)

Componentă Joomla pentru gestionarea fișierelor de descărcat, după modelul jDownloads.
Funcționează pe **Joomla 4.4+, 5.x** (PHP 8.1+). Interfața este în **română și engleză**.

Pachetul instalabil (componentă + 2 pluginuri): [`dist/pkg_adrianjdownloads-1.1.0.zip`](dist/pkg_adrianjdownloads-1.1.0.zip)

### Ce face

- **Categorii pe mai multe niveluri** (sistemul de categorii Joomla: ordonare, permisiuni, publicare).
- **Administrare fișiere**: încărcare, înlocuire, ștergere; titlu, descriere (editor), versiune, autor,
  date de publicare, stare (publicat / nepublicat / arhivat / coș de gunoi), ordonare prin tragere.
- **Descărcare liberă, implicit**: oricine vede un fișier îl poate descărca, fără autentificare.
  La publicare puteți bifa **„Doar pentru utilizatori conectați”**: vizitatorii văd fișierul în listă,
  dar sunt trimiși la autentificare când încearcă să-l descarce. Cine *vede* fișierul se alege cu
  câmpul „Acces (vizualizare)”.
- **Click pe titlu = descărcare directă** (implicit). Din Opțiuni se poate alege ca titlul să deschidă
  pagina de detalii; la fișierele cu descriere apare și un link mic „Detalii”.
- **Buton în editorul de articole** („Fișier jDownloads”): alegeți un fișier sau o categorie și se
  inserează în articol ca `{adrianjd file=12}` / `{adrianjd category=5}`; pe site apare o casetă cu
  buton „Descarcă” sau un link simplu (`layout=link`), cu aceleași reguli de acces.
- **Iconițe după tipul fișierului**, văzute de vizitatori și în administrare: PDF, Word, Excel,
  PowerPoint, arhivă (zip/rar/7z), imagine, audio, video, text și link extern. Pot fi ascunse din Opțiuni.
- **Link extern** în loc de fișier (descărcarea se numără și în acest caz).
- **Contor de descărcări** și de vizualizări.
- **Fișiere protejate**: se păstrează într-un dosar blocat cu `.htaccess` (`/adrianjdownloads_files`)
  și se descarcă doar prin componentă, după verificarea drepturilor.
- **Siguranță la încărcare**: listă de extensii permise, limită de dimensiune, verificarea Joomla
  pentru fișiere periculoase. Numele fișierelor cu diacritice sunt păstrate la descărcare.
- **Site (frontend)**: lista categoriilor, pagina categoriei (subcategorii, tabel de fișiere,
  căutare, paginare), pagina fiecărui fișier. Aspect adaptat pentru telefon.
- **Adrese SEF**: `/documente/9-hotarari-ca/10-2026/3-hotarare-nr-5`.
- **Meniu**: trei tipuri de elemente de meniu (Toate categoriile, Fișierele dintr-o categorie, Un singur fișier).
- **Opțiuni**: dosarul de stocare, tipurile permise, dimensiunea maximă, ce coloane se afișează, ordonarea,
  numărul de fișiere pe pagină, tipurile care se deschid direct în browser (ex. pdf), permisiuni.

### Instalare

Instrucțiunile complete de instalare, configurare și folosire sunt în
[`com_adrianjdownloads/citestema.txt`](com_adrianjdownloads/citestema.txt) (inclus și în pachetul .zip).

1. Administrare Joomla → **Sistem → Instalare → Extensii** → încărcați `pkg_adrianjdownloads-1.1.0.zip`
   (instalează componenta și cele două pluginuri, activate automat).
2. **Componente → Adrian jDownloads → Categorii**: creați categoriile.
3. **Componente → Adrian jDownloads → Fișiere → Nou**: încărcați fișierele.
4. **Meniuri → Meniul principal → Nou** → tip *Adrian jDownloads* → alegeți „Lista tuturor categoriilor”
   sau „Fișierele dintr-o categorie”.

Textele componentei apar în română când site-ul/administrarea folosesc limba română (pachetul de limbă
ro-RO pentru Joomla trebuie să fie instalat).

La dezinstalare, tabela cu înregistrări se șterge, dar **fișierele încărcate rămân** în
`/adrianjdownloads_files`, ca să nu se piardă documente din greșeală.

### Construirea pachetului

```sh
./build.sh   # creează dist/pkg_adrianjdownloads-<versiune>.zip
```

### Structura codului

```
package/                          manifestul pachetului + script (activează pluginurile)
plg_editors-xtd_adrianjdownloads/ butonul „Fișier jDownloads” din editor
plg_content_adrianjdownloads/     afișarea etichetelor {adrianjd ...} în articole
com_adrianjdownloads/
  adrianjdownloads.xml   manifestul extensiei
  script.php             verificări la instalare + crearea dosarului protejat
  admin/                 administrarea (MVC, formulare, opțiuni, SQL, limbi ro-RO/en-GB)
  site/                  partea publică (vizualizări, router SEF, descărcare, limbi ro-RO/en-GB)
  media/                 CSS pentru site
```

### Testat

Testat pe Joomla 5.4 (PHP 8.2, MariaDB 10.11): instalare, actualizare, dezinstalare; categorii și
subcategorii; încărcare, înlocuire și ștergere de fișiere; fișiere cu diacritice în nume; respingerea
fișierelor `.php`; descărcare liberă implicită și opțiunea „Doar pentru utilizatori conectați”
(vizitatorul este trimis la autentificare); actualizarea de la 1.0.0 la 1.0.1; blocarea
accesului direct la dosar (403); butonul din editor și afișarea în articol (casetă, link, categorie); căutare; adrese SEF; afișare pe telefon; interfața în română și engleză.
