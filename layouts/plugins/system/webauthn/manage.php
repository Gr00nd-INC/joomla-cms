<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.webauthn
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Joomla\Plugin\System\Webauthn\Helper\CredentialsCreation;
use Joomla\Plugin\System\Webauthn\Helper\Joomla;

/**
 * Passwordless Login management interface
 *
 *
 * Generic data
 *
 * @var   FileLayout $this        The Joomla layout renderer
 * @var   array      $displayData The data in array format. DO NOT USE.
 *
 * Layout specific data
 *
 * @var   User       $user        The Joomla user whose passwordless login we are managing
 * @var   bool       $allow_add   Are we allowed to add passwordless login methods
 * @var   array      $credentials The already stored credentials for the user
 * @var   string     $error       Any error messages
 */

// Extract the data. Do not remove until the unset() line.
try
{
	$app          = Factory::getApplication();
	$loggedInUser = $app->getIdentity();
}
catch (Exception $e)
{
	$loggedInUser = new User;
}

$defaultDisplayData = [
	'user'        => $loggedInUser,
	'allow_add'   => false,
	'credentials' => [],
	'error'       => '',
];
extract(array_merge($defaultDisplayData, $displayData));

HTMLHelper::_('stylesheet', 'plg_system_webauthn/backend.css', ['relative' => true]);

/**
 * Why not push these configuration variables directly to JavaScript?
 *
 * We need to reload them every time we return from an attempt to authorize an authenticator. Whenever that
 * happens we push raw HTML to the page. However, any SCRIPT tags in that HTML do not get parsed, i.e. they
 * do not replace existing values. This causes any retries to fail. By using a data storage object we circumvent
 * that problem.
 */
$randomId    = 'plg_system_webauthn_' . UserHelper::genRandomPassword(32);
// phpcs:ignore
$publicKey   = $allow_add ? base64_encode(CredentialsCreation::createPublicKey($user)) : '{}';
$postbackURL = base64_encode(rtrim(Uri::base(), '/') . '/index.php?' . Joomla::getToken() . '=1');
?>
<div class="plg_system_webauthn" id="plg_system_webauthn-management-interface">
	<span id="<?php echo $randomId ?>"
		  data-public_key="<?php echo $publicKey ?>"
		  data-postback_url="<?php echo $postbackURL ?>"
	></span>

	<?php // phpcs:ignore
	if (is_string($error) && !empty($error)): ?>
		<div class="alert alert-danger">
			<?php echo htmlentities($error) ?>
		</div>
	<?php endif; ?>

	<table class="table table-striped">
		<thead class="thead-dark">
		<tr>
			<th><?php echo Text::_('PLG_SYSTEM_WEBAUTHN_MANAGE_FIELD_KEYLABEL_LABEL') ?></th>
			<th><?php echo Text::_('PLG_SYSTEM_WEBAUTHN_MANAGE_HEADER_ACTIONS_LABEL') ?></th>
		</tr>
		</thead>
		<tbody>
		<?php // phpcs:ignore
		foreach ($credentials as $method): ?>
			<tr data-credential_id="<?php echo $method['id'] ?>">
				<td><?php echo htmlentities($method['label']) ?></td>
				<td>
					<button onclick="return plgSystemWebauthnEditLabel(this, '<?php echo $randomId ?>');"
					   class="btn btn-secondary">
						<span class="icon-edit icon-white" aria-hidden="true"></span>
						<?php echo Text::_('PLG_SYSTEM_WEBAUTHN_MANAGE_BTN_EDIT_LABEL') ?>
					</button>
					<button onclick="return plgSystemWebauthnDelete(this, '<?php echo $randomId ?>');"
					   class="btn btn-danger">
						<span class="icon-minus-sign icon-white" aria-hidden="true"></span>
						<?php echo Text::_('PLG_SYSTEM_WEBAUTHN_MANAGE_BTN_DELETE_LABEL') ?>
					</button>
				</td>
			</tr>
		<?php endforeach; ?>
		<?php // phpcs:ignore
		if (empty($credentials)): ?>
			<tr>
				<td colspan="2">
					<?php echo Text::_('PLG_SYSTEM_WEBAUTHN_MANAGE_HEADER_NOMETHODS_LABEL') ?>
				</td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>

	<?php // phpcs:ignore
	if ($allow_add): ?>
		<p class="plg_system_webauthn-manage-add-container">
			<button
				type="button"
				onclick="plgSystemWebauthnCreateCredentials('<?php echo $randomId ?>', '#plg_system_webauthn-management-interface'); return false;"
				class="btn btn-success btn-block">
				<span class="icon-plus icon-white" aria-hidden="true"></span>
				<?php echo Text::_('PLG_SYSTEM_WEBAUTHN_MANAGE_BTN_ADD_LABEL') ?>
			</button>
		</p>
	<?php endif; ?>
</div>
