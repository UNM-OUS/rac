<?php
$package->cache_noStore();

global $filters;
$filters = $cms->helper('filters');
global $rac;
$rac = $cms->helper('rac');
$ratings = $cms->helper('racratings');
$form = $rac->propWindowForm();
echo $form;
echo "<hr>";

$call = $form['call']->value();
$call = $cms->read($call);

if (!$call) {
    $cms->helper('notifications')->error('Call not found.');
    return;
}

if ($call->open()) {
    $cms->helper('notifications')->error($call->name().' is still open.');
    return;
}

if ($call['legacy-notifications-sent']) {
    echo "<p>Legacy notifications have already been sent for this call.</p>";
}

$cf = $cms->helper('forms')->form();
$cf->submitButton()->label('Send messages');
echo $cf;

if ($cf->handle()) {
    echo "<p>Sending messages</p>";
    $subject = 'RAC proposal migration notice';
    $body = file_get_contents(__DIR__.'/message.md');
    $body = $cms->helper('filters')->filterPreset($body);
    echo $body;
}
