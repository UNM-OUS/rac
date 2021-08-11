<?php
$package->cache_noStore();
$prop = $package->noun();

if (!$prop->isViewable()) {
    $package->error(403);
    return;
}
if ($prop->report()) {
    $cms->helper('notifications')->notice("A report has already been submitted, this form will replace it.");
    if ($prop['report_due']) {
        $cms->helper('notifications')->notice("Report can be freely edited until ".$cms->helper('strings')->datetime($prop['report_due']).".");
        if (time() > $prop['report_due']) {
            $package->redirect($prop->report()->url());
            return;
        }
    }
}
if (!$prop->finalDecision()) {
    $package->error(404, 'Prop doesn\'t have a decision');
    return;
}
if (!$prop->finalDecision()->funded()) {
    $package->error(404, 'Prop wasn\'t funded');
    return;
}

$parts = $prop->parts();
$chunks = $parts->chunks();

echo "<h2>Proposal information</h2>";
$cms->helper('notifications')->printConfirmation('Awarded '.$prop->decision()->fundedHR().'<br>'.$prop->window()->name());
echo $chunks['submitter']->body();
echo $chunks['submission']->body();

//set up basic form
$forms = $this->helper('forms');
if ($report = $prop->report()) {
    $form = $forms->editNoun($report);
    $insert = false;
} else {
    $form = $forms->addNoun($prop->finalReportClass(), $prop);
    $insert = true;
}

//echo form
echo "<h2>Final report</h2>";
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
        $package->redirect(
            $form->object->hook_postEditUrl()
        );
    } else {
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
        $package->redirect(
            $form->object->hook_postUpdateUrl()
        );
    }
}
