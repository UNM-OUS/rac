<?php
$call = $package->noun();
if (!$call->ended()) {
    echo "<p>No statistics are available until the submission window ends. Please check back later.</p>";
    return;
}

$package['response.ttl'] = 3600*8;

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
        'Funding decisions have not been completely finalized. More proposals may still be added to this list as the committee posts decisions. Please check back later for the complete list.'
    );
}
