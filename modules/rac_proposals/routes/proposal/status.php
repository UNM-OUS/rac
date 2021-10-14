<?php
$package->cache_private();
$package['response.ttl'] = $cms->config['submissions.status_ttl'];
$package->makeMediaFile('status.json');
$status = [
    'type' => 'none',
    'message' => 'No status',
];

$submission = $package->noun();
if (!$submission->isViewable()) {
    //deny access for those with no access
    $package->error(403);
}

if (!$submission->complete()) {
    if ($submission->isMine()) {
        if ($submission->isEditable()) {
            $icWarning = 'Your proposal has not been completed yet. Please finish any incomplete sections.';
            if ($window = $submission->window()) {
                if ($window->end() && !$window->ended()) {
                    $icWarning .= '<br>Proposal can be edited until ' . $window->endHR();
                    $icWarning .= '<br>Proposal will be automatically submitted once all fields are completed. If this banner does not update after you complete your submission, try <a href="' . $submission->url() . '">refreshing the page</a>.';
                }
            }
            $status['type'] = 'notice';
            $status['message'] = $icWarning;
        } else {
            $status['type'] = 'error';
            $status['message'] = 'Your proposal was not completed by the submission deadline of ' . $submission->window()->endHR();
        }
    } else {
        if ($submission->isEditable()) {
            $status['type'] = 'notice';
            $status['message'] = 'This proposal is currently incomplete.';
        } else {
            $status['type'] = 'error';
            $status['message'] = 'This proposal was not completed by the deadline.';
        }
    }
} elseif ($submission->isMine()) {
    $cMessage = 'Your proposal is complete and will be submitted automatically when the submission window closes.';
    if ($submission->isEditable() && $window = $submission->window()) {
        if ($window->end() && !$window->ended()) {
            $cMessage .= '<br>Proposal can be edited until ' . $window->endHR();
        }
        $reloadTime = 30;
    }
    $status['type'] = 'confirmation';
    $status['message'] = $cMessage;
} else {
    $status['type'] = 'confirmation';
    $status['message'] = 'This proposal is complete and submitted.';
}

echo json_encode($status);
