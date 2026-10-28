<?php

/**
 * @package     Adrian Accessibility Tools
 * @subpackage  mod_adrian_accessibility_tools
 *
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var \stdClass $module */
/** @var array    $tools          param key => CSS class of the enabled toggle tools */
/** @var bool     $showTextSize */
/** @var bool     $showReset */
/** @var string   $position       "left" or "right" */
/** @var string   $themeColor */
/** @var string   $themeContrast */
/** @var string   $themeAccent */

$id = 'adrian-a11y-' . (int) $module->id;

// Universal web accessibility symbol: a human figure with open arms inside a circle.
$symbol = '<svg class="adrian-a11y__symbol" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="34" height="34" aria-hidden="true" focusable="false">'
	. '<circle cx="32" cy="32" r="29" fill="none" stroke="currentColor" stroke-width="4"/>'
	. '<circle cx="32" cy="16.5" r="4.6" fill="currentColor"/>'
	. '<path d="M16.5 24.5c5.2 1.6 10.4 2.4 15.5 2.4s10.3-.8 15.5-2.4" fill="none" stroke="currentColor" stroke-width="4.2" stroke-linecap="round"/>'
	. '<path d="M32 27v10.5M32 37.5l-6 12.5M32 37.5l6 12.5" fill="none" stroke="currentColor" stroke-width="4.2" stroke-linecap="round" stroke-linejoin="round"/>'
	. '</svg>';

$svg = static function (string $paths): string {
	return '<svg class="adrian-a11y__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>';
};

$icons = [
	'invert'             => $svg('<circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/>'),
	'monochrome'         => $svg('<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"/><path d="M12 9v11"/>'),
	'dark_contrast'      => $svg('<path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5z" fill="currentColor"/>'),
	'light_contrast'     => $svg('<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>'),
	'highlight_links'    => $svg('<path d="M10 14a4.5 4.5 0 0 0 6.4 0l3-3a4.5 4.5 0 0 0-6.4-6.4l-1.2 1.2"/><path d="M14 10a4.5 4.5 0 0 0-6.4 0l-3 3a4.5 4.5 0 0 0 6.4 6.4l1.2-1.2"/>'),
	'highlight_headings' => $svg('<path d="M5 4v16M15 4v16M5 12h10"/><path d="M19 10l2-1v11"/>'),
	'readable_font'      => $svg('<path d="M4 20 9.5 4h1L16 20"/><path d="M6.2 14h7.6"/><path d="M17 20v-6.5a2.5 2.5 0 0 1 5 0V20"/>'),
	'text_increase'      => $svg('<path d="M3 19 8 5l5 14M4.8 14h6.4"/><path d="M18 8v8M14 12h8"/>'),
	'text_decrease'      => $svg('<path d="M3 19 8 5l5 14M4.8 14h6.4"/><path d="M14 12h8"/>'),
	'reset'              => $svg('<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/>'),
	'close'              => $svg('<path d="M6 6l12 12M18 6 6 18"/>'),
];

$esc = static function (string $text): string {
	return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
};
?>
<div
	id="<?php echo $id; ?>"
	class="adrian-a11y adrian-a11y--<?php echo $position; ?>"
	style="--adrian-a11y-color: <?php echo $themeColor; ?>; --adrian-a11y-on-color: <?php echo $themeContrast; ?>; --adrian-a11y-accent: <?php echo $themeAccent; ?>;"
	data-adrian-a11y
	data-tools="<?php echo $esc(implode(' ', $tools)); ?>"
	data-text-on="<?php echo $esc(Text::_('MOD_ADRIAN_ACCESSIBILITY_TOOLS_ANNOUNCE_ON')); ?>"
	data-text-off="<?php echo $esc(Text::_('MOD_ADRIAN_ACCESSIBILITY_TOOLS_ANNOUNCE_OFF')); ?>"
	data-text-size="<?php echo $esc(Text::_('MOD_ADRIAN_ACCESSIBILITY_TOOLS_ANNOUNCE_TEXT_SIZE')); ?>"
	data-text-reset="<?php echo $esc(Text::_('MOD_ADRIAN_ACCESSIBILITY_TOOLS_ANNOUNCE_RESET')); ?>"
>
	<button
		type="button"
		class="adrian-a11y__toggle"
		aria-expanded="false"
		aria-controls="<?php echo $id; ?>-panel"
		data-adrian-a11y-toggle
	>
		<?php echo $symbol; ?>
		<span class="adrian-a11y__sr-only"><?php echo Text::_('MOD_ADRIAN_ACCESSIBILITY_TOOLS_TOGGLE'); ?></span>
	</button>

	<div
		id="<?php echo $id; ?>-panel"
		class="adrian-a11y__panel"
		role="dialog"
		aria-modal="false"
		aria-labelledby="<?php echo $id; ?>-title"
		data-adrian-a11y-panel
	>
		<div class="adrian-a11y__header">
			<span class="adrian-a11y__header-symbol"><?php echo $symbol; ?></span>
			<h2 id="<?php echo $id; ?>-title" class="adrian-a11y__title">
				<?php echo Text::_('MOD_ADRIAN_ACCESSIBILITY_TOOLS_PANEL_TITLE'); ?>
			</h2>
			<button type="button" class="adrian-a11y__close" data-adrian-a11y-close>
				<?php echo $icons['close']; ?>
				<span class="adrian-a11y__sr-only"><?php echo Text::_('MOD_ADRIAN_ACCESSIBILITY_TOOLS_CLOSE'); ?></span>
			</button>
		</div>

		<div class="adrian-a11y__body">
			<?php if ($showTextSize) : ?>
				<div class="adrian-a11y__textsize" role="group" aria-labelledby="<?php echo $id; ?>-textsize-label">
					<span id="<?php echo $id; ?>-textsize-label" class="adrian-a11y__textsize-label">
						<?php echo Text::_('MOD_ADRIAN_ACCESSIBILITY_TOOLS_TEXT_SIZE'); ?>
					</span>
					<div class="adrian-a11y__textsize-controls">
						<button type="button" class="adrian-a11y__step" data-adrian-a11y-font="-1">
							<?php echo $icons['text_decrease']; ?>
							<span class="adrian-a11y__sr-only"><?php echo Text::_('MOD_ADRIAN_ACCESSIBILITY_TOOLS_TEXT_DECREASE'); ?></span>
						</button>
						<output class="adrian-a11y__textsize-value" data-adrian-a11y-font-value aria-live="off">100%</output>
						<button type="button" class="adrian-a11y__step" data-adrian-a11y-font="1">
							<?php echo $icons['text_increase']; ?>
							<span class="adrian-a11y__sr-only"><?php echo Text::_('MOD_ADRIAN_ACCESSIBILITY_TOOLS_TEXT_INCREASE'); ?></span>
						</button>
					</div>
				</div>
			<?php endif; ?>

			<?php if ($tools) : ?>
				<ul class="adrian-a11y__grid">
					<?php foreach ($tools as $key => $class) : ?>
						<li>
							<button
								type="button"
								class="adrian-a11y__tool"
								aria-pressed="false"
								data-adrian-a11y-tool="<?php echo $class; ?>"
							>
								<?php echo $icons[$key]; ?>
								<span class="adrian-a11y__tool-label"><?php echo Text::_('MOD_ADRIAN_ACCESSIBILITY_TOOLS_' . strtoupper($key)); ?></span>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<?php if ($showReset) : ?>
			<div class="adrian-a11y__footer">
				<button type="button" class="adrian-a11y__reset" data-adrian-a11y-reset>
					<?php echo $icons['reset']; ?>
					<span><?php echo Text::_('MOD_ADRIAN_ACCESSIBILITY_TOOLS_RESET'); ?></span>
				</button>
			</div>
		<?php endif; ?>

		<div class="adrian-a11y__sr-only" role="status" aria-live="polite" data-adrian-a11y-status></div>
	</div>
</div>
