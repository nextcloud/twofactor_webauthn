<?php

declare(strict_types=1);

/**
 * Nextcloud - Webauthn 2FA
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2018
 */

namespace OCA\TwoFactorWebauthn\Listener;

use Symfony\Component\EventDispatcher\Event;

interface IListener {

	public function handle(Event $event);

}