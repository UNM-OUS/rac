<?php
$package->cache_noStore();
$rac = $cms->helper('rac');

if (!($prop = $cms->read($package['url.args.prop']))) {
    $package->error(404, 'Prop not found');
    return;
}
$call = $prop->window();
$netid = $cms->helper('users')->user()->identifier();

if (!$cms->helper('permissions')->check('admin','rac')) {
    if (!$rac->lockedAssignments($call)) {
        echo "<p>Assignments for ".$call->link()." must be generated and locked by the chair for committee members to see their individual assignments here.</p>";
        return;
    }
    if ($prop->finalDecision()) {
        echo "<p>Once a final decision is posted ratings can no longer be added/updated through this interface.</p>";
        return;
    }
}

$rating = $cms->helper('racratings')->rating($prop, $netid);

$form = $rating->form();

echo $form;

if ($form->handle()) {
    $package->redirect($this->url('_rac', 'prop_myassignments'));
}