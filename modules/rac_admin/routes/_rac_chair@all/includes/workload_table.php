<?php
$assignments = $cms->helper('rac')->windowDatastore($call)->get('assignments');
$workload = [];

foreach ($assignments as $types) {
    foreach ($types as $type => $netids) {
        foreach ($netids as $netid) {
            if (!isset($workload[$netid])) {
                $workload[$netid] = ['regular'=>0,'lead'=>0,'total'=>0];
            }
            $workload[$netid][$type]++;
            $workload[$netid]['total']++;
        }
    }
}

echo "<table>";
echo "<tr><th>Member</th><th>Lead</th><th>Regular</th><th>Total</th></tr>";
foreach ($workload as $netid => $load) {
    echo "<tr>";
    if ($member = $cms->helper('rac')->member($netid)) {
        echo "<td>".$member->name()."</td>";
    }else {
        echo "<td>$netid</td>";
    }
    echo "<td>".$load['lead']."</td>";
    echo "<td>".$load['regular']."</td>";
    echo "<td>".$load['total']."</td>";
    echo "</tr>";
}
echo "</table>";