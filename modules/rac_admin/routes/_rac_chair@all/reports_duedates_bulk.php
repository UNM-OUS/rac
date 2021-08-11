<?php

use Formward\Fields\CheckboxList;

$package->cache_noStore();

global $filters;
$filters = $cms->helper('filters');
global $rac;
$rac = $cms->helper('rac');
$s = $cms->helper('strings');
$form = $rac->propWindowForm();
echo $form;
echo "<hr>";

$call = $form['call']->value();
$call = $cms->read($call);

if (!$call) {
    $cms->helper('notifications')->error('Call not found.');
    return;
}

echo "<p>Note that changing due dates via this form will email the proposal authors immediately with the new due date for their final report.</p>";

$f = $cms->helper('forms');
$form = $f->form();
$form['date'] = $f->field('datetime', 'New due date/time');
$form['date']->required(true);
$form['date']->addTip('Due dates set to 11:00 pm are generally preferred. This provides a memorable time late in the day, but avoids confusion regarding the transition from one date to the next at midnight.');
$form['date']->required(true);
$form['props'] = new CheckboxList('Proposals to set');
$options = [];
$submissions = $call->completeSubmissions();
usort($submissions, function ($a, $b) {
    $out = $a['report_due'] - $b['report_due'];
    if ($out) {
        return $out;
    } else {
        return strcasecmp($a['submitter.lastname'], $b['submitter.lastname']);
    }
});
foreach ($submissions as $prop) {
    if ($prop->finalDecision() && $prop->finalDecision()->funded() && !$prop->report()) {
        $label = '<strong>' . $prop->name() . '</strong>';
        if ($prop['report_due']) {
            $label .= '<br>Current due date: ' . $s->datetime($prop['report_due']);
        }
        $id = $prop['dso.id'];
        $options[$id] = $label;
    }
}
$form['props']->options($options);
$form['props']->addTip('Proposals with a final report already submitted are not shown.');
$form['props']->addTip('Proposals without a finalized funding decision are not shown.');
echo $form;

if ($form->handle()) {
    $n = $cms->helper('notifications');
    $newDate = $form['date']->value();
    foreach ($form['props']->value() as $id) {
        if ($prop = $cms->read($id)) {
            if ($prop['report_due'] != $newDate) {
                //set value
                $prop['report_due'] = $newDate;
                if ($prop->update()) {
                    //send email
                    $rac->sendMailTemplate($prop, 'prop_report_due');
                    //flash confirmation
                    $n->confirmation('Updated due date for ' . $prop->link());
                } else {
                    $n->error('An error occurred when updating ' . $prop->link());
                }
            } else {
                $n->notice($prop->link() . ' already had this due date, nothing was changed.');
            }
        }
    }
}
