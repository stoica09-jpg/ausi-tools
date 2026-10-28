<?php

/**
 * @package     Adrian Cookie
 * @subpackage  mod_adrian_cookie
 *
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var \stdClass $module */
/** @var string $bgColor */
/** @var string $textColor */
/** @var string $position */
/** @var string $message */
/** @var string $acceptText */
/** @var string $essentialText */
/** @var string $policyUrl */
/** @var string $policyText */

$id = 'adrian-cookie-' . (int) $module->id;
?>
<div
	id="<?php echo $id; ?>"
	class="adrian-cookie adrian-cookie--<?php echo $position; ?>"
	role="dialog"
	aria-modal="false"
	aria-labelledby="<?php echo $id; ?>-title"
	aria-describedby="<?php echo $id; ?>-message"
	style="--adrian-cookie-bg: <?php echo $bgColor; ?>; --adrian-cookie-text: <?php echo $textColor; ?>;"
	data-adrian-cookie
	hidden
>
	<div class="adrian-cookie__inner">
		<h2 id="<?php echo $id; ?>-title" class="adrian-cookie__sr-only">
			<?php echo Text::_('MOD_ADRIAN_COOKIE_TITLE'); ?>
		</h2>

		<div id="<?php echo $id; ?>-message" class="adrian-cookie__message">
			<?php echo $message; ?>
			<?php if ($policyUrl !== '') : ?>
				<a class="adrian-cookie__policy" href="<?php echo htmlspecialchars($policyUrl, ENT_QUOTES, 'UTF-8'); ?>">
					<?php echo htmlspecialchars($policyText, ENT_QUOTES, 'UTF-8'); ?>
				</a>
			<?php endif; ?>
		</div>

		<div class="adrian-cookie__actions">
			<button type="button" class="adrian-cookie__btn adrian-cookie__btn--essential" data-adrian-consent="essential">
				<?php echo htmlspecialchars($essentialText, ENT_QUOTES, 'UTF-8'); ?>
			</button>
			<button type="button" class="adrian-cookie__btn adrian-cookie__btn--accept" data-adrian-consent="all">
				<?php echo htmlspecialchars($acceptText, ENT_QUOTES, 'UTF-8'); ?>
			</button>
		</div>
	</div>
</div>
