<?php
$call = $package->noun();
$package['fields.page_name'] = $package['fields.page_title'] .= ': Funding statistics';
$package['response.ttl'] = 3600*8;

$charts = [
    'submitter.rank',
    'submitter.yearsvoting',
    'submission.discipline',
    'submitter.college',
    'submission.requested'
];

$v = $cms->helper('templates')->variables();
echo "<div style='display:inline-block;' class='notification notification-confirmation'>Funded</div>";
echo "<div style='display:inline-block;' class='notification notification-notice'>No decision</div>";
echo "<div style='display:inline-block;' class='notification notification-error'>Denied</div>";

echo "<div class='rac-chart-container'>";
foreach ($charts as $by) {
    echo "<embed src='".$this->url(
        $call['dso.id'],
        'stats-graph-by',
        ['by'=>$by]
    )."' type='image/svg+xml'>";
}
echo "</div>";
