<?php

script('twofactor_webauthn', 'challenge');

?>

<img class="two-factor-icon" src="<?php print_unescaped(image_path('twofactor_webauthn', 'app.svg')); ?>" alt="">

<input id="twofactor-webauthn-publicKey" type="hidden" value="<?php p(json_encode($_['publicKey'])); ?>">
<div id="twofactor-webauthn-challenge"></div>
