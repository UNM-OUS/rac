<?php
$package->cache_noStore();

$prop = $cms->read($package['url.args.prop']);
if (!$prop) {
    $package->error(404);
    return;
}

echo "<p>This form will set the due date <strong>and immediately send email notification</strong> of the new date for the proposal ".$prop->link().".</p>";
$f = $cms->helper('forms');
$dform = $f->form();
$dform['date'] = $f->field('datetime', 'Due date/time');
$dform['date']->required(true);
$dform['date']->default($prop['report_due']?$prop['report_due']:strtotime('18 months, 11:00 pm'));
$dform['date']->addTip('Due dates set to 11:00 pm are generally preferred. This provides a memorable time late in the day, but avoids confusion regarding the transition from one date to the next at midnight.');
echo $dform;

if ($dform->handle()) {
    //set value
    $prop['report_due'] = $dform['date']->value();
    if ($prop->update()) {
        //send email
        $cms->helper('rac')->sendMailTemplate($prop, 'prop_report_due');
        //flash confirmation
        $cms->helper('notifications')->flashConfirmation('Due date set');
    }else {
        $cms->helper('notifications')->flashError('An error occurred');
    }
    //redirect
    $package->redirect($this->url('_rac_chair', 'reports_duedates'));
}
