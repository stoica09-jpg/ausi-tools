# ausi-tools

## Adrian jDownloads (`com_adrianjdownloads`)

Componentă Joomla pentru gestionarea fișierelor de descărcat, după modelul jDownloads.
Funcționează pe **Joomla 4.4+, 5.x** (PHP 8.1+). Interfața este în **română și engleză**.

Pachetul instalabil: [`dist/com_adrianjdownloads-1.0.0.zip`](dist/com_adrianjdownloads-1.0.0.zip)

### Ce face

- **Categorii pe mai multe niveluri** (sistemul de categorii Joomla: ordonare, permisiuni, publicare).
- **Administrare fișiere**: încărcare, înlocuire, ștergere; titlu, descriere (editor), versiune, autor,
  date de publicare, stare (publicat / nepublicat / arhivat / coș de gunoi), ordonare prin tragere.
- **Două niveluri de acces** pentru fiecare fișier: cine îl *vede* și cine îl poate *descărca*
  (ex.: vizitatorii văd fișierul, dar doar utilizatorii înregistrați îl descarcă).
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

1. Administrare Joomla → **Sistem → Instalare → Extensii** → încărcați `com_adrianjdownloads-1.0.0.zip`.
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
./build.sh   # creează dist/com_adrianjdownloads-<versiune>.zip
```

### Structura codului

```
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
fișierelor `.php`; acces „doar înregistrați” (vizitatorul este trimis la autentificare); blocarea
accesului direct la dosar (403); căutare; adrese SEF; afișare pe telefon; interfața în română și engleză.
