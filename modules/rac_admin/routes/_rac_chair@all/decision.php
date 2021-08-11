<?php
$package->cache_noStore();
$package['fields.page_title'] = $package['url.text'];
$prop = $cms->read($package['url.args.prop']);
if (!$prop) {
    $package->error(404, 'Prop not found');
    return;
}
$package['fields.page_title'] = $package['fields.page_name'] = $prop->name();

//print rating information
echo "<h2>Rating information to be attached to decision</h2>";
echo "<p>All of the following will be visible to the proposal author, except the names of the raters will be kept anonymous.</p>";
echo "<div class='digraph-card'>";
$ratings = $cms->helper('racratings')->ratings($prop);
foreach ($ratings as $rating) {
    echo $rating->body();
}
echo "</div>";

//set up basic form
$forms = $this->helper('forms');
if ($decision = $prop->decision()) {
    $form = $forms->editNoun($decision);
    $insert = false;
}else {
    $form = $forms->addNoun('proposal-decision', $prop);
    $insert = true;
}

//echo form
echo "<h2>Funding decision</h2>";
$form['funded']->addTip('Proposal is requesting '.$prop->requestedHR());
echo $form;

//handle form to save
if ($form->handle()) {
    if ($insert) {
        if ($form->object->insert()) {
            $object = $cms->read($form->object['dso.id'], false, true);
            $cms->helper('edges')->create($prop['dso.id'], $object['dso.id']);
            $cms->helper('hooks')->noun_trigger($object, 'added');
            $cms->helper('notifications')->flashConfirmation(
                $cms->helper('strings')->string(
                    'notifications.add.confirmation',
                    ['name'=>$object->link()]
                )
            );
        } else {
            $cms->helper('notifications')->flashError(
                $cms->helper('strings')->string(
                    'notifications.add.error'
                )
            );
        }
    }else {
        if ($form->object->update()) {
            $object = $cms->read($form->object['dso.id'], false, true);
            $cms->helper('notifications')->flashConfirmation(
                $cms->helper('strings')->string(
                    'notifications.edit.confirmation',
                    ['name'=>$object->link()]
                )
            );
        } else {
            $cms->helper('notifications')->flashError(
                $cms->helper('strings')->string(
                    'notifications.edit.error'
                )
            );
        }
    }
    $package->redirect(
        $this->url('_rac_chair','prop_decisions')
    );
}
