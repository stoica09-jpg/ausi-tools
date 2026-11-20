<?php
/**
 * Creează pe un site Joomla de test (3.10 sau 4/5/6) meniul „Main Menu” cu structura reală a
 * site-ului scoala1buzau.ro și o instanță a modulului mod_megameniu.
 * Rulare: php seed.php /cale/site [pozitie]
 */

$root     = rtrim($argv[1], '/');
$position = isset($argv[2]) ? $argv[2] : 'menu';

define('_JEXEC', 1);
define('JPATH_BASE', $root);
require_once $root . '/includes/defines.php';
require_once $root . '/includes/framework.php';

$major = (int) explode('.', JVERSION)[0];

if ($major >= 4)
{
	$container = \Joomla\CMS\Factory::getContainer();
	$db        = $container->get(\Joomla\Database\DatabaseInterface::class);
	\Joomla\CMS\Factory::$database = $db;
	// Aplicație minimă pentru tabelele care o cer.
	$container->alias('session.web', 'session.web.site')
		->alias('session', 'session.web.site')
		->alias('JSession', 'session.web.site')
		->alias(\Joomla\CMS\Session\Session::class, 'session.web.site')
		->alias(\Joomla\Session\Session::class, 'session.web.site')
		->alias(\Joomla\Session\SessionInterface::class, 'session.web.site');
	$app = $container->get(\Joomla\CMS\Application\SiteApplication::class);
	\Joomla\CMS\Factory::$application = $app;
}
else
{
	$db = \JFactory::getDbo();
	\JFactory::getApplication('site');
}

$menutype = 'mainmenu';

// Elementul Home existent devine primul din meniul nostru.
$homeId = (int) $db->setQuery("SELECT id FROM #__menu WHERE home = 1 AND client_id = 0 AND language = '*' LIMIT 1")->loadResult();
$db->setQuery("UPDATE #__menu SET menutype = " . $db->quote($menutype) . ", title = 'Home' WHERE id = " . $homeId)->execute();
$db->setQuery("DELETE FROM #__menu WHERE menutype = " . $db->quote($menutype) . " AND id <> " . $homeId . " AND client_id = 0")->execute();

$exists = (int) $db->setQuery("SELECT COUNT(*) FROM #__menu_types WHERE menutype = " . $db->quote($menutype))->loadResult();

if (!$exists)
{
	$db->setQuery("INSERT INTO #__menu_types (menutype, title, description" . ($major >= 4 ? ', client_id' : '') . ") VALUES ('mainmenu', 'Main Menu', ''" . ($major >= 4 ? ', 0' : '') . ")")->execute();
}

$tree = [
	['Despre Instituție', [
		['Legislație'],
		['Conducere', [['Echipa Managerială'], ['Consiliul de administrație']]],
		['Organizare', [['Organigramă'], ['Regulament de Organizare și Funcționare'], ['Secretariat'], ['Contabilitate'], ['Bibliotecă'],
			['PERSONAL DIDACTIC'], ['PERSONAL DIDACTIC AUX'], ['PERSONAL NEDIDACTIC'], ['CARIERĂ - Concurs'], ['Rapoarte-Legea 544/2001']]],
	]],
	['Media', [['Purtător de cuvânt'], ['Comunicate de presă'], ['Istoric'], ['Personalități'], ['Galerie Foto']]],
	['Examene', [['Evaluare II-IV-VI'], ['Evaluare națională'], ['Admitere'], ['Înscriere în Clasa Pregătitoare']]],
	['Proiecte', [
		['Proiecte Europene', [['Erasmus+ KA122 „ȘCOALA NOASTRĂ - ȘCOALA VIITORULUI” (2023-1-RO01-KA122-SCH-000131229)'],
			['Erasmus+ KA210 „DIGIBALANCE” (2024-2-RO01-KA210-SCH-000291775)'], ['Acreditare Erasmus+ 2024-1-RO01-KA120-SCH-000277506'],
			['Acreditare Erasmus+ - Anul II- 2026-1-RO01-KA121-SCH-000414428'], ['Etwinning']]],
		['Proiecte Naționale'],
		['Proiecte Locale', [['EcoScoala']]],
	]],
	['Concursuri', [['Concursuri Județene'], ['Concursuri Naționale']]],
	['Documente'],
	['Contact', [['Site ISJ Buzău', [], 'url']]],
	['Admitere Clasa a VIII-a 2022', [], 'component', 0],
];

$componentId = (int) $db->setQuery("SELECT extension_id FROM #__extensions WHERE element = 'com_content' AND type = 'component'")->loadResult();
$n = 0;

$add = function ($title, $parentId, $type = 'component', $published = 1) use (&$add, $db, $menutype, $componentId, $major, &$n) {
	$table = $major >= 4
		? new \Joomla\CMS\Table\Menu($db)
		: \JTable::getInstance('Menu');

	$n++;
	$alias = 'mm-test-' . $n;
	$data  = [
		'menutype'     => $menutype,
		'title'        => $title,
		'alias'        => $alias,
		'type'         => $type,
		'link'         => $type === 'url' ? 'https://isjbuzau.ro' : 'index.php?option=com_content&view=featured',
		'published'    => $published,
		'component_id' => $type === 'url' ? 0 : $componentId,
		'access'       => 1,
		'language'     => '*',
		'client_id'    => 0,
		'browserNav'   => $type === 'url' ? 1 : 0,
		'params'       => '{"menu_show":1,"menu_text":1}',
		'img'          => '',
	];

	$table->setLocation($parentId, 'last-child');

	if (!$table->bind($data) || !$table->check() || !$table->store())
	{
		fwrite(STDERR, "Eroare la '$title': " . $table->getError() . "\n");
		exit(1);
	}

	return (int) $table->id;
};

$walk = function (array $nodes, $parentId) use (&$walk, $add) {
	foreach ($nodes as $node)
	{
		$id = $add($node[0], $parentId, isset($node[2]) ? $node[2] : 'component', isset($node[3]) ? $node[3] : 1);

		if (!empty($node[1]))
		{
			$walk($node[1], $id);
		}
	}
};

$walk($tree, 1);

// Modulul: o singură instanță, pe toate paginile.
$db->setQuery("DELETE FROM #__modules WHERE module = 'mod_megameniu'")->execute();
$params = json_encode(['menutype' => $menutype, 'columnsMode' => 'auto', 'maxColumns' => 4, 'openOn' => 'hover', 'mobileMode' => 'accordion', 'mobileBreakpoint' => 992]);
$cols   = "title, position, published, module, access, showtitle, params, client_id, language, ordering";
$vals   = "'Mega Meniu test', " . $db->quote($position) . ", 1, 'mod_megameniu', 1, 0, " . $db->quote($params) . ", 0, '*', 1";
$db->setQuery("INSERT INTO #__modules ($cols) VALUES ($vals)")->execute();
$moduleId = (int) $db->insertid();
$db->setQuery("INSERT INTO #__modules_menu (moduleid, menuid) VALUES ($moduleId, 0)")->execute();

// Modulul de meniu standard al temei e dezactivat, ca să nu se dubleze meniul.
$db->setQuery("UPDATE #__modules SET published = 0 WHERE module = 'mod_menu' AND client_id = 0")->execute();

echo "Joomla " . JVERSION . ": $n elemente de meniu create, modul #$moduleId pe poziția '$position'.\n";
