<?php
$package->cache_noStore();

$rac = $cms->helper('rac');
echo $rac->semesterForm();
$semester = $rac->workingSemesterName();
$calls = $rac->workingCalls();
$netid = $cms->helper('users')->user()->identifier();
echo "<hr>";

$notifyDenial = [];
$notifyApproval = [];
$alreadyNotified = [];
$noDecision = 0;

foreach ($calls as $call) {
    foreach ($call->completeSubmissions() as $prop) {
        if ($prop->finalDecision()) {
            $alreadyNotified[] = $prop;
        } elseif ($decision = $prop->decision()) {
            if ($decision->funded()) {
                $notifyApproval[] = $prop;
            } else {
                $notifyDenial[] = $prop;
            }
        } else {
            $noDecision++;
        }
    }
}

if ($noDecision) {
    $cms->helper('notifications')->warning(
        'There are currently proposals without decisions. Those submitters cannot be sent any approval/denial emails until decisions are added to their proposals.'
    );
}

if ($alreadyNotified) {
    echo "<p>";
    echo "<strong>Proposals already finalized: </strong>";
    echo count($alreadyNotified);
    echo "</p>";
}

if ($notifyApproval || $notifyDenial) {
    echo "<p>";
    echo "<strong>Not notified yet: </strong>";
    echo "<br>Approved: ".count($notifyApproval);
    echo "<br>Denied: ".count($notifyDenial);
    echo "</p>";

    echo "<p>Please carefully review the following decision list before finalizing. Once proposals are finalized the shown proposals will all be immediately sent confirmation emails and their decision forms will be locked.</p>";
    
    echo '<table width="100%">';
    echo '<tr><th>Submitter</th><th>Proposal</th><th>Funded</th></tr>';
    foreach ($notifyApproval as $prop) {
        echo "<tr class='highlighted highlighted-confirmation'>";
        echo "<td>".$prop['submitter.lastname']."</td>";
        echo "<td>".$prop->name()."</td>";
        echo "<td>".$prop->decision()->fundedHR()."</td>";
        echo "</tr>";
    }
    foreach ($notifyDenial as $prop) {
        echo "<tr class='highlighted highlighted-warning'>";
        echo "<td>".$prop['submitter.lastname']."</td>";
        echo "<td>".$prop->name()."</td>";
        echo "<td>".$prop->decision()->fundedHR()."</td>";
        echo "</tr>";
    }
    echo "</table>";

    $url = $this->url('_rac_chair', 'finalize', ['ftoken'=>$cms->helper('session')->getToken('rac.assignments.finalize')]);
    echo "<p><a href='$url' class='cta-button'>Finalize ".(count($notifyApproval)+count($notifyDenial))." decisions</a></p>";
}

if ($package['url.args.ftoken'] && $cms->helper('session')->checkToken('rac.assignments.finalize', $package['url.args.ftoken'])) {
    foreach ($notifyApproval as $prop) {
        $prop['decision_finalized'] = true;
        $prop->update(true);
        $rac->sendMailTemplate($prop, 'prop_awarded');
    }
    foreach ($notifyDenial as $prop) {
        $prop['decision_finalized'] = true;
        $prop->update(true);
        $rac->sendMailTemplate($prop, 'prop_denied');
    }
    $cms->helper('notifications')->flashConfirmation('Decisions finalized');
    $package->redirect($this->url('_rac_chair', 'finalize'));
}
