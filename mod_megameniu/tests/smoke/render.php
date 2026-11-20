<?php
/**
 * Test fără Joomla: construiește meniul site-ului scoala1buzau.ro (structura reală,
 * din structura-meniuri-referinta.md), îl randează și scrie o pagină HTML pentru
 * testele din browser (tests/e2e). Rulare: php tests/smoke/render.php [mega|list] [accordion|offcanvas]
 */

define('_JEXEC', 1);

require __DIR__ . '/../../src/MenuTree.php';
require __DIR__ . '/../../src/Renderer.php';

class FakeRegistry
{
	private $d;

	public function __construct(array $d = [])
	{
		$this->d = $d;
	}

	public function get($k, $def = null)
	{
		return array_key_exists($k, $this->d) ? $this->d[$k] : $def;
	}
}

class FakeItem
{
	public $id, $parent_id, $level, $title, $type, $link, $browserNav = 0, $published = 1, $access = 1, $tree = [];
	private $p;

	public function __construct($id, $parent, $level, $title, $type = 'component', array $p = [], $link = '', $published = 1)
	{
		$this->id = $id;
		$this->parent_id = $parent;
		$this->level = $level;
		$this->title = $title;
		$this->type = $type;
		$this->link = $link ?: 'index.php?option=com_content&view=article&id=' . $id;
		$this->published = $published;
		$this->p = new FakeRegistry($p);
	}

	public function getParams()
	{
		return $this->p;
	}
}

// Structura „Main Menu” (ordine, niveluri, titluri reale).
$rows = [
	[101, 1, 1, 'Home'],
	[321, 1, 1, 'Despre Instituție'],
	[322, 321, 2, 'Legislație'],
	[323, 321, 2, 'Conducere'],
	[125, 323, 3, 'Echipa Managerială'],
	[126, 323, 3, 'Consiliul de administrație'],
	[324, 321, 2, 'Organizare'],
	[325, 324, 3, 'Organigramă'],
	[326, 324, 3, 'Regulament de Organizare și Funcționare'],
	[134, 324, 3, 'Secretariat'],
	[135, 324, 3, 'Contabilitate'],
	[136, 324, 3, 'Bibliotecă'],
	[144, 324, 3, 'PERSONAL DIDACTIC'],
	[145, 324, 3, 'PERSONAL DIDACTIC AUX'],
	[146, 324, 3, 'PERSONAL NEDIDACTIC'],
	[327, 324, 3, 'CARIERĂ - Concurs'],
	[328, 324, 3, 'Rapoarte-Legea 544/2001'],
	[330, 321, 2, 'Protecția datelor cu caracter personal', 'component', [], '', -2], // la coș
	[106, 1, 1, 'Media'],
	[329, 106, 2, 'Purtător de cuvânt'],
	[123, 106, 2, 'Comunicate de presă'],
	[124, 106, 2, 'Istoric'],
	[127, 106, 2, 'Personalități'],
	[128, 106, 2, 'Galerie Foto'],
	[129, 1, 1, 'Examene'],
	[130, 129, 2, 'Evaluare II-IV-VI'],
	[131, 129, 2, 'Evaluare națională'],
	[132, 129, 2, 'Admitere'],
	[195, 129, 2, 'Înscriere în Clasa Pregătitoare'],
	[137, 1, 1, 'Proiecte'],
	[138, 137, 2, 'Proiecte Europene'],
	[632, 138, 3, 'Erasmus+ KA122 „ȘCOALA NOASTRĂ - ȘCOALA VIITORULUI” (2023-1-RO01-KA122-SCH-000131229)'],
	[577, 138, 3, 'Erasmus+ KA210 „DIGIBALANCE” (2024-2-RO01-KA210-SCH-000291775)'],
	[633, 138, 3, 'Acreditare Erasmus+ 2024-1-RO01-KA120-SCH-000277506'],
	[635, 138, 3, 'Acreditare Erasmus+ - Anul II- 2026-1-RO01-KA121-SCH-000414428'],
	[634, 138, 3, 'Etwinning'],
	[139, 137, 2, 'Proiecte Naționale'],
	[140, 137, 2, 'Proiecte Locale'],
	[557, 140, 3, 'EcoScoala'],
	[141, 1, 1, 'Concursuri'],
	[142, 141, 2, 'Concursuri Județene'],
	[143, 141, 2, 'Concursuri Naționale'],
	[178, 1, 1, 'Documente'],
	[186, 1, 1, 'Contact'],
	[531, 1, 1, 'Admitere Clasa a VIII-a 2022', 'component', [], '', 0], // nepublicat
	// Elemente suplimentare pentru acoperirea cazurilor speciale:
	[900, 1, 1, '<script>alert(1)</script> Test XSS', 'url', ['menu_show' => 0], 'https://example.org'], // ascuns
	[901, 186, 2, 'Site ISJ Buzău', 'url', [], 'https://isjbuzau.ro'],
];

$items = [];
$byId  = [];

foreach ($rows as $r)
{
	$it = new FakeItem($r[0], $r[1], $r[2], $r[3], isset($r[4]) ? $r[4] : 'component', isset($r[5]) ? $r[5] : [], isset($r[6]) ? $r[6] : '', isset($r[7]) ? $r[7] : 1);
	$byId[$it->id] = $it;
	$items[] = $it;
}

$items[count($items) - 1]->browserNav = 1;

foreach ($items as $it)
{
	$tree = [$it->id];
	$pid  = $it->parent_id;

	while ($pid > 1 && isset($byId[$pid]))
	{
		array_unshift($tree, $pid);
		$pid = $byId[$pid]->parent_id;
	}

	$it->tree = $tree;
}

$mode   = isset($argv[1]) ? $argv[1] : 'mega';
$mobile = isset($argv[2]) ? $argv[2] : 'accordion';

$params = new FakeRegistry([
	'menutype'         => 'mainmenu',
	'columnsMode'      => $mode === 'list' ? 'list' : 'auto',
	'maxColumns'       => 4,
	'mobileMode'       => $mobile,
	'mobileBreakpoint' => 992,
	'barBg'            => '#1d3557',
	'barColor'         => '#ffffff',
	'fontSize'         => '1rem',
	'fontFamily'       => 'Arial, sans-serif',
	'radius'           => 4,
	'dropWidth'        => '16rem; color:red', // trebuie respins
]);

// Elementul curent: „Secretariat” (Despre Instituție > Organizare > Secretariat).
$activeId = 134;
$path     = $byId[134]->tree;

$roots = MegaMeniuTree::buildFromItems($items, $params, $activeId, $path);

$strings = [
	'MOD_MEGAMENIU_NAV_LABEL'     => 'Meniu principal',
	'MOD_MEGAMENIU_MOBILE_LABEL'  => 'Meniu',
	'MOD_MEGAMENIU_CLOSE'         => 'Închide meniul',
	'MOD_MEGAMENIU_OPEN_SUBMENU'  => 'Deschide submeniul %s',
	'MOD_MEGAMENIU_NEW_WINDOW'    => '(se deschide într-o fereastră nouă)',
];
$t = function ($key, $arg = null) use ($strings) {
	$s = isset($strings[$key]) ? $strings[$key] : $key;

	return $arg === null ? $s : sprintf($s, $arg);
};

$module = (object) ['id' => 87, 'title' => 'Meniu'];
$html   = MegaMeniuRenderer::render(['items' => $roots, 'activeId' => $activeId], $params, $module, $t);

// ---------- Verificări ----------
$errors = [];

$titles = array_map(function ($n) {
	return $n->title;
}, $roots);
$expectedTop = ['Home', 'Despre Instituție', 'Media', 'Examene', 'Proiecte', 'Concursuri', 'Documente', 'Contact'];

if ($titles !== $expectedTop)
{
	$errors[] = 'Ordinea de nivel 1 diferă: ' . implode(' | ', $titles);
}

if (strpos($html, 'Admitere Clasa a VIII-a 2022') !== false)
{
	$errors[] = 'Elementul nepublicat apare.';
}

if (strpos($html, 'item-330') !== false)
{
	$errors[] = 'Elementul de la coș apare.';
}

if (strpos($html, 'Test XSS') !== false || strpos($html, '<script>alert') !== false)
{
	$errors[] = 'Elementul ascuns (menu_show=0) apare sau textul nu e escapat.';
}

if (strpos($html, 'color:red') !== false)
{
	$errors[] = 'Valoare CSS nevalidă acceptată.';
}

if (!preg_match('#<a class="mm-link" href="[^"]*Itemid=134"[^>]*aria-current="page"#', $html))
{
	$errors[] = 'Elementul curent nu are aria-current="page".';
}

if (strpos($html, 'target="_blank" rel="noopener noreferrer"') === false)
{
	$errors[] = 'Linkul extern în fereastră nouă nu are target/rel corecte.';
}

$dom = new DOMDocument();
libxml_use_internal_errors(true);
$ok = $dom->loadXML('<root>' . str_replace('&times;', '×', $html) . '</root>');

if (!$ok)
{
	$errors[] = 'HTML-ul nu este bine format: ' . libxml_get_last_error()->message;
}

// Numărarea elementelor de meniu randate (publicate + 1 link extern de test).
$count = substr_count($html, 'class="mm-item ') + substr_count($html, 'class="mm-col ');

if ($count !== 43 + 1)
{
	$errors[] = 'Număr de elemente randate: ' . $count . ' (așteptat 44 = 43 publicate + 1 test).';
}

$out = __DIR__ . '/out-' . $mode . '-' . $mobile . '.html';
$page = '<!doctype html><html lang="ro"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
	. '<title>Test mod_megameniu</title><link rel="stylesheet" href="../../media/css/megameniu.css">'
	. '<style>body{margin:0;font-family:Arial,sans-serif;background:#f4f4f4}header{padding:1rem 2rem;background:#fff}main{padding:2rem;min-height:120vh}</style>'
	. '</head><body><header><h1 style="margin:0">Școala Gimnazială „Căpitan Aviator Mircea T. Bădulescu” Buzău</h1></header>'
	. $html . '<main><h2>Conținut</h2><p><a href="#x" id="after-menu">Link după meniu</a></p></main>'
	. '<script src="../../media/js/megameniu.js" defer></script></body></html>';
file_put_contents($out, $page);

if ($errors)
{
	fwrite(STDERR, "EȘEC:\n - " . implode("\n - ", $errors) . "\n");
	exit(1);
}

echo "OK ($mode/$mobile): $count elemente, pagina de test: $out\n";
