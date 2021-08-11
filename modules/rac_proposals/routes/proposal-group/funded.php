<?php
$package['fields.page_name'] = $package['fields.page_title'] = 'Funded proposals since Fall 2012';
$package['response.ttl'] = 3600*8;

foreach ($package->noun()->pastCalls() as $call) {
    $incomplete = false;
    $funded = [];
    $amount = 0;
    foreach ($call->completeSubmissions() as $prop) {
        if (!$prop->finalDecision()) {
            $incomplete = true;
            continue;
        }
        if ($decision = $prop->decision()) {
            if ($decision->funded()) {
                $funded[] = $prop;
                $amount += $decision->funded();
            }
        }
    }
    echo "<h2>".$call->name()."</h2>";
    echo "<p><strong>Total awarded:</strong> $".number_format($amount)." awarded to ".count($funded)." proposals</p>";
    echo "<table>";
    echo "<tr><th>Name</th><th>Rank</th><th>College</th><th>Department</th><th>Title</th><th>Funding</th></tr>";
    foreach ($funded as $prop) {
        echo "<tr>";
        echo "<td>".$prop['submitter.firstname']." ".$prop['submitter.lastname']."</td>";
        echo "<td>".$prop['submitter.rank']."</td>";
        echo "<td>".$prop['submitter.college']."</td>";
        echo "<td>".$prop['submitter.department']."</td>";
        echo "<td>".$prop['submission.title']."</td>";
        echo "<td>".$prop->decision()->fundedHR()."</td>";
        echo "</tr>";
    }
    echo "</table>";

    if ($incomplete) {
        $cms->helper('notifications')->warning(
            'Decisions for '.$call->name().' have not been completely finalized. More proposals may still be added to this list as the committee posts decisions. Please check back later for the complete list.'
        );
    }
}
