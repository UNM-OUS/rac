<?php
$package->cache_noStore();

echo "<div class='noprint'>"; //don't print all the admin stuff

$form = $cms->helper('rac')->propWindowForm();
echo $form;

$call = $form['call']->value();
$call = $cms->read($call);

if (!$call) {
    $cms->helper('notifications')->error('Call not found');
    return;
}

echo "<p>This page is used to automatically make as good a guess as possible at a good distribution of proposals across reviewers.</p>";
echo "<p>It attempts to avoid department-matching conflicts, but cannot do so 100% reliably. Its output should be double-checked for reviewers and proposals from the same department.</p>";
echo "<p>It also attempts to balance reviewer workloads as equitably as it can. However, it can only do so much if there are a high number of proposals from a discipline without many reviewers, or a reviewer appears to share a department with many proposals.</p>";

if ($call->open()) {
    $cms->helper('notifications')->error($call->name() . ' is still open. Assignments cannot be made until it closes.');
    return;
}

global $SIMILARITIES;
$SIMILARITIES = [
    'Physical Sciences' => [
        'Life Sciences' => 1,
        'Social Sciences' => 0.75,
        'Engineering' => 0.75,
        'Education' => 0.25,
        'Humanities' => 0,
        'Fine Arts' => 0,
    ],
    'Life Sciences' => [
        'Physical Sciences' => 1,
        'Social Sciences' => 0.75,
        'Engineering' => 0.25,
        'Education' => 0.25,
        'Humanities' => 0,
        'Fine Arts' => 0,
    ],
    'Social Sciences' => [
        'Physical Sciences' => 0.5,
        'Life Sciences' => 0.5,
        'Engineering' => 0.25,
        'Education' => 0.5,
        'Humanities' => 0.25,
        'Fine Arts' => 0.2,
    ],
    'Engineering' => [
        'Physical Sciences' => 1,
        'Life Sciences' => 0.75,
        'Social Sciences' => 0.5,
        'Education' => 0.2,
        'Humanities' => 0,
        'Fine Arts' => 0,
    ],
    'Education' => [
        'Physical Sciences' => 0.25,
        'Life Sciences' => 0.25,
        'Social Sciences' => 0.75,
        'Engineering' => 0,
        'Humanities' => 0.75,
        'Fine Arts' => 0.5,
    ],
    'Humanities' => [
        'Physical Sciences' => 0,
        'Life Sciences' => 0.2,
        'Social Sciences' => 0.25,
        'Engineering' => 0,
        'Education' => 0.75,
        'Fine Arts' => 1,
    ],
    'Fine Arts' => [
        'Physical Sciences' => 0,
        'Life Sciences' => 0.2,
        'Social Sciences' => 0.25,
        'Engineering' => 0,
        'Education' => 0.5,
        'Humanities' => 1,
    ],
];

//get helpers and info we'll need
$rac = $cms->helper('rac');
$datastore = $rac->windowDatastore($call);

//locking interface
echo "<hr>";
$token = $cms->helper('session')->getToken('rac.assignments.locking');
if ($package['url.args.token'] == $token) {
    if ($package['url.args.lock']) {
        $cms->helper('notifications')->flashConfirmation('Assignments locked.');
        $rac->lockAssignments($call);
        $package->redirect(
            $this->url('_rac_chair', 'prop_assignments')
        );
        return;
    }
    if ($package['url.args.unlock']) {
        $cms->helper('notifications')->flashConfirmation('Assignments unlocked.');
        $rac->unlockAssignments($call);
        $package->redirect(
            $this->url('_rac_chair', 'prop_assign')
        );
        return;
    }
}
if ($rac->lockedAssignments($call)) {
    $url = $cms->helper('urls')->parse('_rac_chair/prop_assignments');
    echo "<p>Assignments are locked. To avoid making unexpected large changes to multiple assignments, changes can now only be made one at a time through " . $url->html() . ".</p>";
    $url = $package->url();
    $url['args.unlock'] = true;
    $url['args.token'] = $token;
    echo "<p><a href='$url'>Click here to unlock assignments</a><br>(this will immediately re-run the bulk assignments algorithm, and if you've changed anything anywere else it may result in significant changes to existing assignments)</p>";
    return;
} else {
    echo "<p>Once the assignments shown here have been double-checked for conflicts of interest and general sanity, you should lock them to prevent unexpected changes. Once assignments are locked, this page's bulk assignment tools will be unavailable, and assignments will only be able to be changed manually one at a time.</p>";
    $url = $package->url();
    $url['args.lock'] = true;
    $url['args.token'] = $token;
    echo "<p><a href='$url'>Click here to lock assignments</a></p>";
}

//global stuff
global $MEMBERS;
$MEMBERS = $cms->helper('rac')->members();
global $POSSIBLEASSIGNMENTS;
$POSSIBLEASSIGNMENTS = [];
global $ASSIGNMENTS;
$ASSIGNMENTS = [];
global $PROPS;
$PROPS = $call->completeSubmissions();
global $WORKLOAD;
$WORKLOAD = [];

//pick settings
echo "<hr>";
$session = $cms->helper('session');
$numReviewers = $session->get('rac_assignment_target') ? $session->get('rac_assignment_target') : 3;
$nrForm = $cms->helper('forms')->form('');
$nrForm->addClass('compact-form');
$nrForm['nr'] = new \Formward\Fields\Number('Number of regular reviewers to assign per proposal');
$nrForm['nr']->required(true);
$nrForm['nr']->default($numReviewers);
$numReviewers = $nrForm['nr']->value();
$session->set('rac_assignment_target', $numReviewers);
if ($nrForm->handle()) {
    $package->redirect($package->url());
    return;
}
echo $nrForm;

//get guidelines and build form for them
//this creates the global $GUIDELINES
echo "<hr>";
include 'includes/guidelines.php';

//build a list of all possible assignments
foreach ($PROPS as $prop) {
    allPossibleAssignments($POSSIBLEASSIGNMENTS, $prop, 'lead');
    allPossibleAssignments($POSSIBLEASSIGNMENTS, $prop, 'regular');
}

//make lead assignments first
foreach ($PROPS as $prop) {
    if (!pickPerson($prop, 'lead')) {
        $cms->helper('notifications')->error('Couldn\'t automatically assign a lead reviewer for "' . $prop->title() . '". Is there a reviewer for the discipline "' . $prop['submission.discipline'] . '"?');
    }
}
//then make regular assignments
foreach ($PROPS as $prop) {
    $notFound = 0;
    for ($i = 1; $i <= $numReviewers; $i++) {
        if (!pickPerson($prop, 'regular')) {
            $notFound++;
        }
    }
    if ($notFound) {
        $cms->helper('notifications')->error('Couldn\'t automatically assign ' . $notFound . ' reviewer slot' . ($notFound == 1 ? '' : 's') . ' for "' . $prop->title() . '". You probably need to assign an "prefer" rule to pick a reviewer for this rule.');
    }
}

echo "</div>"; //end .noprint div

//save assignments
$datastore->set('assignments', $ASSIGNMENTS);

//display tables of output
echo "<hr>";
include 'includes/prop_assign_display.php';

/*
pick a lead reviewer
 */
function pickPerson($prop, $type)
{
    global $POSSIBLEASSIGNMENTS;
    if ($candidates = @$POSSIBLEASSIGNMENTS[$prop['dso.id']][$type]) {
        sortCandidates($candidates, $prop, $type);
        $candidate = array_shift($candidates);
        assignReviewer($candidate, $prop, $type);
        return true;
    } else {
        return false;
    }
}

/*
return a workload level of a person
 */
function workload($person, $type = 'total')
{
    global $WORKLOAD;
    return @$WORKLOAD[$person->netid()][$type];
}

/*
assign a person, which also removes them from other possibilities
 */
function assignReviewer($person, $prop, $type)
{
    global $POSSIBLEASSIGNMENTS;
    global $ASSIGNMENTS;
    global $WORKLOAD;
    //assign person
    $ASSIGNMENTS[$prop['dso.id']][$type][] = $person->netid();
    //record workload
    if (!@$WORKLOAD[$person->netid()]) {
        $WORKLOAD[$person->netid()] = ['total' => 0, 'lead' => 0, 'regular' => 0];
    }
    $WORKLOAD[$person->netid()]['total']++;
    $WORKLOAD[$person->netid()][$type]++;
    //remove from applicable possible assignments
    foreach ($POSSIBLEASSIGNMENTS as $a => $pos) {
        if ($a != $prop['dso.id']) {
            continue;
        }
        foreach ($pos as $b => $ms) {
            foreach ($ms as $c => $m) {
                if ($m->netid() == $person->netid()) {
                    unset($POSSIBLEASSIGNMENTS[$a][$b][$c]);
                }
            }
        }
    }
}

/*
sort a list of candidates by how well they suit a given proposal
 */
function sortCandidates(&$candidates, $prop, $type)
{
    //sort list by text similarity, to try and place people from
    //the same department further down the candidates list
    usort(
        $candidates,
        function ($a, $b) use ($prop, $type) {
            $sa = reviewerScore($a, $prop, $type);
            $sb = reviewerScore($b, $prop, $type);
            if ($sa > $sb) {
                return -1;
            } elseif ($sa < $sb) {
                return 1;
            } else {
                return 0;
            }
        }
    );
}

/*
scores how much a reviewer should be favored for a particular proposal
should return higher numbers for better candidates
 */
function reviewerScore($member, $prop, $type)
{
    global $GUIDELINES, $SIMILARITIES;
    //get levenshtein distance with zero cost for deletion
    //zero cost for deletion means we're effectively ignoring non-matching text surrounding the submitter's department
    $score = levenshtein($member->info(), $prop['submitter.department'], 1, 1, 0);
    //member doesn't appear to be the same department, so we may be able to increase their score
    if ($score > 5) {
        if (in_array($prop['submission.discipline'], $member->disciplines())) {
            //multiply score if discipline is the same, because we should favor reviewers
            //from the same discipline who don't appear to be the same department
            $score *= 10;
        } else {
            //multiply score less if discipline is "similar" using a predefined list of
            //which disciplines are similar enough to be preferred as reviewers
            $similarity = 0;
            foreach ($member->disciplines() as $d) {
                if ($SIMILARITIES[$d][$prop['submission.discipline']] > $similarity) {
                    $similarity = $SIMILARITIES[$d][$prop['submission.discipline']];
                }
            }
            if ($similarity) {
                $score *= 10 * $similarity;
            }
        }
    }
    //reduce score by workload score
    if ($w = workload($member, ($type == 'lead' ? 'lead' : 'total'))) {
        $score /= pow(10, $w);
    }
    //check for manually-entered prefer/avoid guidelines
    if (($g = @$GUIDELINES[$member->netid()][$prop['dso.id']]) || ($g = @$GUIDELINES[$member->netid()]['all'])) {
        if ($g[0] == 'prefer') {
            if ($g[1] == 'any' || $g[1] == $type) {
                $score *= 100;
            }
        }
        if ($g[0] == 'avoid') {
            if ($g[1] == 'any' || $g[1] == $type) {
                $score /= 1000;
            }
        }
    }
    //return final score
    return $score;
}

/* builds an array of all valid assignments in place */
function allPossibleAssignments(&$ASSIGNMENTS, $prop, $type)
{
    global $MEMBERS;
    //build a list of possible people to take this slot
    $candidates = [];
    foreach ($MEMBERS as $member) {
        if (isValidReviewer($prop, $member, $type)) {
            $candidates[] = $member;
        }
    }
    //filter down to just netid and place in array
    $ASSIGNMENTS[$prop['dso.id']][$type] = $candidates;
}

/*
return whether or not a reviewer is a valid candidate for a particular proposal
 */
function isValidReviewer($prop, $member, $type)
{
    global $GUIDELINES;
    //check for manually-entered prefer guidelines ("prefer" guidelines for all proposals don't make reviewers valid leads for all proposals)
    if ($g = @$GUIDELINES[$member->netid()][$prop['dso.id']]) {
        //prefer rules allow members to be considered even if they don't normally
        if ($g[0] == 'prefer') {
            if ($g[1] == 'any' || $g[1] == $type) {
                return true;
            }
        }
    }
    //check for manually-entered avoid guidelines (blocking works for all proposal guidelines)
    if (($g = @$GUIDELINES[$member->netid()][$prop['dso.id']]) || ($g = @$GUIDELINES[$member->netid()]['all'])) {
        if ($g[0] == 'block') {
            if ($g[1] == 'any' || $g[1] == $type) {
                return false;
            }
        }
    }
    // nobody can review a proposal from the same department as them
    // Note: disabled, because it has too many false positives, mostly thanks to "art"
    // if (stripos($member->info(), $prop['submitter.department']) !== false) {
    //     return false;
    // }
    // lead reviewers must be from the same discipline
    if ($type == 'lead') {
        return in_array($prop['submission.discipline'], $member->disciplines());
    } else {
        return true;
    }
}
