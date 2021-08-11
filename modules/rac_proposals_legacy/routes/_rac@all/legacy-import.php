<?php
$package->cache_noStore();

$form = $cms->helper('forms')->form('');
$form->oneTimeTokens(false);
$form['file'] = new \Formward\Fields\File('JSON file');
$form['file']->required('true');

//echo/handle form
echo $form;
if (!$form->handle()) {
    return;
}

//form is handled, do import
ini_set('max_execution_time', 300);
ini_set('memory_limit', '1024M');

$LOG = [];
$DATA = file_get_contents($form->value()['file']['file']);
$EDGES = $cms->helper('edges');
$FACTORY = $cms->factory();
$FS = $cms->helper('filestore');

if (!$DATA = json_decode($DATA, true)) {
    $cms->helper('notifications')->error('Error parsing JSON');
    return;
}

$PGROUP = $cms->read('prop');
$LOG[] = 'Decoded JSON ('.$DATA['window']['year'].' '.$DATA['window']['semester'].': '.count($DATA['proposals']).' proposals)';

/*
PROPOSAL WINDOW
*/
//insert proposal window
$id = 'lc'.hash('crc32', serialize($DATA['window']));
if (!($WINDOW = $cms->read($id))) {
    $WINDOW = $FACTORY->create([
        'dso.type' => 'proposal-call',
        'dso.id' => $id,
        'semester.semester' => $DATA['window']['semester'],
        'semester.year' => $DATA['window']['year'],
        'requestamount.maximum' => 10000,
        'requestamount.minimum' => 1
    ]);
    if ($WINDOW->insert()) {
        $WINDOW->update();
        $LOG[] = 'Window created';
        if ($EDGES->create($PGROUP['dso.id'], $WINDOW['dso.id'], 'proposal-call')) {
            $LOG[] = 'Edge created';
        } else {
            echo 'Edge failed to be created';
            return;
        }
    } else {
        var_dump($WINDOW->get());
        echo 'Window failed to be created';
        return;
    }
} else {
    $LOG[] = 'Window already exists';
}
//guess start/end date
if (!$WINDOW['window.start']) {
    $LOG[] = 'guessing start date';
    if ($WINDOW['semester.semester'] == 'Fall') {
        $WINDOW['window.start'] = strtotime('October 1, '.$WINDOW['semester.year'].', 8:00 am');
    } elseif ($WINDOW['semester.semester'] == 'Spring') {
        $WINDOW['window.start'] = strtotime('February 1, '.$WINDOW['semester.year'].', 8:00 am');
    }
}
if (!$WINDOW['window.end']) {
    $LOG[] = 'guessing end date';
    if ($WINDOW['semester.semester'] == 'Fall') {
        $WINDOW['window.end'] = strtotime('October 15, '.$WINDOW['semester.year'].', 11:00 pm');
    } elseif ($WINDOW['semester.semester'] == 'Spring') {
        $WINDOW['window.end'] = strtotime('February 15, '.$WINDOW['semester.year'].', 11:00 pm');
    }
}
$WINDOW->update();

/*
PROPOSALS
*/
foreach ($DATA['proposals'] as $PDATA) {
    //create proposal
    $id = 'lp'.hash('crc32', $WINDOW['dso.id'].$PDATA['netid'].serialize($PDATA['submission']));
    $LOG[] = 'Proposal '.$id;
    if ($PROP = $FACTORY->read($id)) {
        $LOG[] = 'Already exists';
    } else {
        $PROP = $FACTORY->create([
            'dso.type' => 'proposal',
            'dso.id' => $id,
            'owner' => $PDATA['netid'].'@netid',
            'submitter' => $PDATA['submitter'],
            'submission' => $PDATA['submission'],
            'submitterfieldclass' => 'Digraph\\Modules\\rac_proposals\\Fields\\SubmitterField2019',
            'submissionfieldclass' => 'Digraph\\Modules\\rac_proposals\\Fields\\SubmissionField2019',
            'partsclass' => 'Digraph\\Modules\\rac_proposals_legacy\\LegacyProposalParts',
        ]);
        $PROP['submitter.college'] = fixCollegeName($PROP['submitter.college']);
        if ($PROP->insert()) {
            $PROP->update();
            $LOG[] = 'Proposal created';
            if ($EDGES->create($WINDOW['dso.id'], $PROP['dso.id'], 'submission')) {
                $LOG[] = 'Edge created';
            } else {
                echo 'Edge failed to be created';
                return;
            }
        } else {
            var_dump($PROP->get());
            echo 'Proposal failed to be created';
            return;
        }
    }
    //add proposal file
    $pf_name = $PDATA['file']['name'];
    $pf_content = gzuncompress(base64_decode($PDATA['file']['content']));
    if ($FS->get($PROP, $pf_name)) {
        $LOG[] = 'file already exists';
    } else {
        $LOG[] = 'adding '.$pf_name;
        $tfile = tempnam(sys_get_temp_dir(), 'rac-legacy-import');
        file_put_contents($tfile, $pf_content);
        $FS->import(
            $PROP,
            [
                'file' => $tfile,
                'name' => $pf_name
            ],
            'prop_proposal'
        );
    }
    //add final report if it exists
    if ($PDATA['final_report']) {
        if (!$PROP->report()) {
            $pf_name = $PDATA['final_report']['name'];
            $pf_content = gzuncompress(base64_decode($PDATA['final_report']['content']));
            $report = $FACTORY->create([
                'dso.type' => 'proposal-report-legacy'
            ]);
            $report->insert();
            $EDGES->create($PROP['dso.id'], $report['dso.id'], 'proposal-report');
            $LOG[] = 'added '.$report->name();
            $tfile = tempnam(sys_get_temp_dir(), 'rac-legacy-import');
            file_put_contents($tfile, $pf_content);
            $FS->import(
                $report,
                [
                    'file' => $tfile,
                    'name' => $pf_name
                ],
                'report'
            );
        }else {
            $LOG[] = 'final report already exists';
        }
    }
    //set due date if decision was positive
    if (intval($PDATA['decision']['amount'])) {
        $due = $PDATA['final_report_due'].' 11:00 pm';
        $due = strtotime($due);
        $LOG[] = 'setting report due date to '.date('F j, Y, g:i a', $due);
        $PROP['report_due'] = $due;
    }
    //save proposal
    $PROP->update();
    //add ratings
    foreach ($PDATA['ratings'] as $RDATA) {
        $rating = $cms->helper('racratings')->rating($PROP, $RDATA['netid']);
        if (!$rating->data()) {
            $RDATA['comments'] = [
                'text' => $RDATA['comments'],
                'filter' => 'text-safe'
            ];
            $LOG[] = 'Adding rating from '.$RDATA['netid'];
            $rating->data($RDATA);
        } else {
            $LOG[] = 'Rating from '.$RDATA['netid'].' already exists';
        }
    }
    //add decision
    if (!$PROP->decision()) {
        $DECISION = $FACTORY->create([
            'dso.type' => 'proposal-decision',
            'funded' => intval($PDATA['decision']['amount']),
            'digraph.body.text' => $PDATA['decision']['comments']
        ]);
        if ($DECISION->insert()) {
            $DECISION->update();
            $LOG[] = 'Decision created';
            if ($EDGES->create($PROP['dso.id'], $DECISION['dso.id'], 'proposal-decision')) {
                $LOG[] = 'Edge created';
            } else {
                echo 'Edge failed to be created';
                return;
            }
        } else {
            var_dump($DECISION->get());
            echo 'Decision failed to be created';
            return;
        }
        $PROP->finalizeDecision();
    }
}

echo "<pre>".implode(PHP_EOL, $LOG)."</pre>";

function fixCollegeName($name)
{
    return $name;
}
