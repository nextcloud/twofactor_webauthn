<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

return [
	'routes' => [
		[
			'name' => 'settings#startRegister',
			'url' => '/settings/startregister',
			'verb' => 'POST'
		],
		[
			'name' => 'settings#finishRegister',
			'url' => '/settings/finishregister',
			'verb' => 'POST'
		],
		[
			'name' => 'settings#remove',
			'url' => '/settings/remove',
			'verb' => 'POST'
		],
		[
			'name' => 'settings#changeActivationState',
			'url' => '/settings/active',
			'verb' => 'POST'
		],
	]
];
