<?php
$package['fields.page_name'] = $package['fields.page_title'] = 'Funding summary by semester';
$package['response.ttl'] = 3600*8;

$data = [];

foreach ($package->noun()->calls() as $call) {
    //don't list windows not ended yet
    if ($call->end() && $call->end() > time()) {
        continue;
    }
    //don't list windows not started yet
    if ($call->start() && $call->start() > time()) {
        continue;
    }
    //add statistics
    $stats = [
        'year' => $call['semester.year'],
        'semester' => $call['semester.semester'],
        'proposed' => 0,
        'denied' => 0,
        'awarded' => 0,
        'awardrate' => 0,
        'amtproposed' => 0,
        'amtawarded' => 0
    ];
    //pull stats from props
    foreach ($call->completeSubmissions() as $prop) {
        $stats['proposed']++;
        if ($decision = $prop->finalDecision()) {
            if ($decision->funded()) {
                $stats['awarded']++;
                $stats['amtawarded'] += $decision->funded();
            } else {
                $stats['denied']++;
            }
        }
        $stats['amtproposed'] += $prop['submission.requested'];
    }
    if ($stats['proposed']) {
        $stats['awardrate'] = round(100*$stats['awarded']/$stats['proposed']).'%';
    }
    //alert if awarded+denied != proposed, this means data is incomplete
    if ($stats['awarded']+$stats['denied'] != $stats['proposed']) {
        $cms->helper('notifications')->warning('Data for '.$call['semester.semester'].' '.$call['semester.year'].' is incomplete. Please check back later once all funding decisions have been finalized.');
    }
    //save into data
    $data[] = $stats;
}

$data = array_reverse($data);

echo "<table>";
echo "<tr>";
echo "<th colspan=2>Semester</th>";
echo "<th>Submitted</th>";
echo "<th>Awarded</th>";
echo "<th>Award rate</th>";
echo "<th>Total proposed</th>";
echo "<th>Total awarded</th>";
echo "</tr>";
$totals = [
    'proposed' => 0,
    'awarded' => 0,
    'awardrate' => 0,
    'amtproposed' => 0,
    'amtawarded' => 0
];
foreach ($data as $row) {
    //update totals
    $totals['proposed'] += $row['proposed'];
    $totals['awarded'] += $row['awarded'];
    $totals['amtproposed'] += $row['amtproposed'];
    $totals['amtawarded'] += $row['amtawarded'];
    //format/output
    unset($row['denied']);
    $row['amtproposed'] = '$'.number_format($row['amtproposed']);
    $row['amtawarded'] = '$'.number_format($row['amtawarded']);
    echo "<tr>";
    foreach ($row as $cell) {
        echo "<td>$cell</td>";
    }
    echo "</tr>";
}
//output totals
$totals['amtproposed'] = '$'.number_format($totals['amtproposed']);
$totals['amtawarded'] = '$'.number_format($totals['amtawarded']);
if ($totals['proposed']) {
    $totals['awardrate'] = round(100*$totals['awarded']/$totals['proposed']).'%';
}
echo "<tr style='font-weight:bold;' class='highlighted'>";
echo "<td colspan=2>Total</td>";
foreach ($totals as $cell) {
    echo "<td>$cell</td>";
}
echo "</tr>";
echo "</table>";
