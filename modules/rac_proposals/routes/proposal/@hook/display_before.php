<?php
$package->cache_noCache();

$prop = $package->noun();
//check viewability to avoid leaking information in notifications
if (!$prop->isViewable()) {
    return;
}

//display decision confirmation or denial
if ($decision = $prop->finalDecision()) {
    if ($decision->funded()) {
        $cms->helper('notifications')->confirmation("Congratulations! This proposal was awarded " . $decision->fundedHR() . ".<br><a href='" . $decision->url() . "'>Visit the decision page for more information</a>.");
    } else {
        $cms->helper('notifications')->error("This proposal has not been selected for funding. <a href='" . $decision->url() . "'>Visit the decision page for more information</a>.");
    }
}
//display final report link
if ($report = $prop->report()) {
    $cms->helper('notifications')->confirmation("Final report submitted.<br><a href='" . $report->url() . "'>View final report</a>.");
} elseif ($due = $prop['report_due']) {
    $s = $cms->helper('strings');
    if ($due > time()) {
        $cms->helper('notifications')->notice("Final report is due " . $s->datetime($due) . ".<br><a href='" . $prop->url('submit-final-report') . "'>Submit final report</a>.");
    } else {
        $cms->helper('notifications')->error("Final report was due " . $s->datetime($due) . ".<br><a href='" . $prop->url('submit-final-report') . "'>Submit final report</a>.");
    }
}

echo "<noscript>";
echo $cms->helper('notifications')->printError("This form does not function correctly without Javascript enabled. Please enable Javascript in your browser.");
echo "</noscript>";
