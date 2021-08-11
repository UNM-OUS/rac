<?php
$package->cache_noStore();
$rac = $cms->helper('rac');

if (!($prop = $cms->read($package['url.args.prop']))) {
    $package->error(404, 'Prop not found');
    return;
}
$call = $prop->window();
$netid = $package['url.args.netid'];

if ($prop->finalDecision()) {
    echo "<p>Once a decision is finalized ratings can no longer be added/updated through this interface.</p>";
    return;
}

$rating = $cms->helper('racratings')->rating($prop, $netid);

$form = $rating->form();

echo $form;

if ($form->handle()) {
    $package->redirect($this->url('_rac_chair', 'prop_decisions'));
}
