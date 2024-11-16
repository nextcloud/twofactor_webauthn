<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

script('twofactor_webauthn', 'twofactor_webauthn-challenge');
style('twofactor_webauthn', 'auth');

?>

<img class="two-factor-icon two-factor-webauthn-icon" src="<?php print_unescaped(image_path('twofactor_webauthn', 'app-dark.svg')); ?>" alt="">

<div id="twofactor-webauthn-challenge"></div>
