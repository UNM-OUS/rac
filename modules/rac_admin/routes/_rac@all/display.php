<?php
$package->cache_noCache();

$links = [
    'Proposal review' => [
        [
            '_rac/prop_myassignments',
            'View and review proposals currently assigned to you.',
        ],
        [
            '_rac/prop_ratings',
            'View all currently-submitted reviewer ratings.',
        ],
    ],
    'Submitted proposals' => [
        [
            '_rac/prop_list',
            'View submitted proposals.',
        ],
        [
            '_rac/search_netid',
            'Locate proposals by submitter/owner NetID.',
        ],
        [
            '_rac/search_title',
            'Locate proposals by title keyword search.',
        ],
    ],
    'Reviewer assignments' => [
        [
            '_rac_chair/reviewer_profiles',
            'Set up committee member profiles so the bulk assignment tool can use them to make better assignments.',
        ],
        [
            '_rac_chair/prop_assign',
            'Algorithmically build a set of draft reviewer assignments.',
        ],
        [
            '_rac_chair/prop_assignments',
            'View/manage reviewer assignments once they are constructed and locked in the bulk assignment tool.',
        ],
        [
            '_rac_chair/reviewer_status',
            'View completion status of reviewer assignments.',
        ],
    ],
    'Funding decisions' => [
        [
            '_rac_chair/prop_decisions',
            'Enter/edit draft funding decisions. Nothing entered here is visible outside the committee.',
        ],
        [
            '_rac_chair/finalize',
            'Finalize draft funding decisions and notify submitters.',
        ],
    ],
    'Final reports' => [
        [
            '_rac/reports_submitted',
            'View submitted final reports.',
        ],
        [
            '_rac_chair/reports_duedates',
            'View/manage final report due dates.',
        ],
        [
            '_rac_chair/reports_duedates_bulk',
            'View/manage final report due dates in bulk.',
        ],
        [
            '_rac_chair/reports_overdue',
            'View overdue final reports.',
        ],
    ],
    'Emails' => [
        [
            '_rac_chair/mail_templates',
            'Manage email templates.',
        ],
        [
            '_rac_chair/mail_send_bulk',
            'Contact submitters by sending mass emails.',
        ],
    ],
    'Data' => [
        [
            '_rac_chair/submitter_names',
            'Get an alphabetical list of all submitters. Used to send to send a list of submitters to COI people at the Office of Research &amp; Compliance.',
        ],
        [
            'calls/full-csv',
            'Get a full list of all past proposals, including their funding decisions and final report status.',
        ],
    ],
];

foreach ($links as $section => $sl) {
    $shtml = '';
    foreach ($sl as $l) {
        $shtml .= "<dl>";
        if ($url = $cms->helper('urls')->parse($l[0])) {
            if ($cms->helper('permissions')->checkUrl($url)) {
                $shtml .= "<dt>" . $url->html() . "</dt>";
                $shtml .= "<dd>" . $l[1] . "</dd>";
            }
        }
        $shtml .= "</dl>";
    }
    if ($shtml != '<dl></dl>') {
        echo "<h2>$section</h2>" . $shtml;
    }
}
