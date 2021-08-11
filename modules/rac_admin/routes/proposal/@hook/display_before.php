<?php

//display message center link
$prop = $package->noun();
if ($cms->helper('mail')->tagCount($prop['dso.id'])) {
    $cms->helper('notifications')->notice('Missing an email? <a href="' . $prop->url('messages') . '">See all messages sent regarding this proposal in the message center</a>.');
}
