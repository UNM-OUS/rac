<?php
$package->cache_noCache();

$decision = $package->noun();
$prop = $decision->prop();
//check viewability to avoid leaking information in notifications
if (!$prop->isViewable()) {
    return;
}

if (!$prop->isViewable()) {
    //deny access for those with no access
    $package->error(403);
}

$package['response.ttl'] = 30;

/*
Display notification of whether proposal was funded
*/
if (!$decision->funded()) {
    //proposal was not funded, no other notifications
    $cms->helper('notifications')->printError(
        'This proposal has not been selected for funding.'
    );
} else {
    //proposal was funded, there will be more steps
    $cms->helper('notifications')->printConfirmation(
        'Congratulations! This proposal has been selected to receive '.$decision->fundedHR().'.'.
        '<br><a href="'.$prop->window()->url('version-jumper', ['page'=>'prop/recipient-guide']).'">RAC grant recipient guide</a>'
    );
    //final report notifications
    if ($report = $prop->report()) {
        $cms->helper('notifications')->confirmation("Final report submitted.<br><a href='".$report->url()."'>View final report</a>.");
    } elseif ($due = $prop['report_due']) {
        $s = $cms->helper('strings');
        if ($due > time()) {
            $cms->helper('notifications')->notice("Final report is due ".$s->datetime($due).".<br><a href='".$prop->url('submit-final-report')."'>Submit final report</a>.");
        } else {
            $cms->helper('notifications')->error("Final report was due ".$s->datetime($due).".<br><a href='".$prop->url('submit-final-report')."'>Submit final report</a>.");
        }
    }
}
if ($prop['digraph.body.text']) {
    echo "<h2>Notes from the committee</h2>";
}
//body content comes from main display handler
