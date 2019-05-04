<?php
script('twofactor_webauthn', 'settings');
style('twofactor_webauthn', 'style');
?>

<input type="hidden" id="twofactor-webauthn-initial-state" value="<?php p($_['state']); ?>">

<div id="twofactor-webauthn-settings"></div>
