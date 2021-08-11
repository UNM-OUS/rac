<?php
$package->cache_noStore();

$rac =$cms->helper('rac');
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
    $cms->helper('notifications')->error($call->name().' is still open. Assignments cannot be made until it closes.');
    return;
}

echo "<p>This page is used to review and manually edit reviewer assignments. This is where you can add and remove reviewers from proposals after they have been locked in using the bulk assignment tool.</p>";

//locking interface
if (!$rac->lockedAssignments($call)) {
    echo "<p>Assignments must be locked to use this page.</p>";
    echo "<p><a href='".$this->url('_rac_chair','prop_assign')."'>Lock assignments in the bulk assignment tool</a></p>";
    return;
}

echo "<h2>Workload summary</h2>";
include 'includes/workload_table.php';

echo "<h2>All assignments</h2>";
include 'includes/assignment_table.php';