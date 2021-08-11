<?php
$package->cache_noStore();
$session = $cms->helper('session');
$data = $session->getPersistedData(true);
$n = $cms->helper('notifications');

if (!$data) {
    $n->printError('This action has expired or already been completed, please go back and try again.');
    return;
}

$recipients = array_filter(array_map(
    function ($e) use ($cms) {
        return $cms->read($e);
    },
    $data['recipients']
));
$template = $data['template'];

// confirmation form
$form = $cms->helper('forms')->form('Confirm sending these emails');

if (!$form->handle()) {
    // display list of emails and form for confirmation
    $n->printNotice('Confirm sending <code>' . $template . '</code> to the following submissions.');
    echo "<table style='font-size:0.8em;'>";
    foreach ($recipients as $prop) {
        echo "<tr>";
        //submission info
        echo "<td valign='top'>";
        echo $prop->infoCell();
        echo "</td>";
        //call
        echo "<td valign='top'>";
        echo $prop->window()->link();
        echo "</td>";
        //decision
        echo "<td valign='top'>";
        if ($decision = $prop->decision()) {
            echo $decision->info();
        } else {
            echo "<em>none</em>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo $form;
} else {
    // queue emails
    $rac = $cms->helper('rac');
    foreach ($recipients as $prop) {
        $rac->sendMailTemplate($prop, $template);
    }
    $n->flashConfirmation('Queued ' . count($recipients) . ' emails. They will be sent in the next few minutes.');
    $package->redirect($data['after']);
    // clear data to avoid accidental multiple submissions
    $session->getPersistedData();
}
