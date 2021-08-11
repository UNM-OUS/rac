<?php
global $DATASTORE;
$DATASTORE = $cms->helper('rac')->windowDatastore($call);
global $ASSIGNMENTS;
$ASSIGNMENTS = $DATASTORE->get('assignments');
global $TOKEN;
$TOKEN = $cms->helper('session')->getToken('rac.assignment_table.delete');
global $CMS;
$CMS = $cms;
global $PACKAGE;
$PACKAGE = $package;

echo "<table style='width:100%;'>";
echo "<tr><th>Proposal</th><th colspan=2>Reviewers</th></tr>";
foreach ($call->completeSubmissions() as $prop) {
    echo "<tr>";
    echo "<td valign='top' width='40%'>";
    echo $prop->infoCell();
    echo "</td>";
    echo "<td valign='top'>";
    reviewerList($prop, 'lead');
    echo "</td>";
    echo "<td valign='top'>";
    reviewerList($prop, 'regular');
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

//handle deletions
if ($package['url.args.token'] == $TOKEN) {
    list($pid, $netid) = json_decode($package['url.args.delete'], true);
    $guidelines = $DATASTORE->get('guidelines');
    foreach ($ASSIGNMENTS as $a => $types) {
        if ($a !== $pid) {
            continue;
        }
        foreach ($types as $b => $ns) {
            foreach ($ns as $c => $n) {
                if ($n == $netid) {
                    $cms->helper('notifications')->flashConfirmation('Removed reviewer assignment');
                    unset($ASSIGNMENTS[$a][$b][$c]);
                    $DATASTORE->set('assignments', $ASSIGNMENTS);
                    if ($b == 'lead') {
                        $guidelines[$netid][$pid] = ['block', 'lead'];
                    } else {
                        $guidelines[$netid][$pid] = ['block', 'any'];
                    }
                    $DATASTORE->set('guidelines', $guidelines);
                }
            }
        }
    }
    $url = $package->url();
    unset($url['args']);
    $package->redirect($url);
}

function reviewerList($prop, $type)
{
    global $ASSIGNMENTS, $TOKEN, $CMS, $PACKAGE, $DATASTORE;
    echo "<p><strong>$type</strong></p>";
    if (@$ASSIGNMENTS[$prop['dso.id']][$type]) {
        foreach ($ASSIGNMENTS[$prop['dso.id']][$type] as $netid) {
            if ($member = $CMS->helper('rac')->member($netid)) {
                $memberName = $member->name();
            } else {
                $memberName = $netid;
            }
            if (canAdmin()) {
                $removeLink = $PACKAGE->url();
                $removeLink['args'] = [
                    'token' => $TOKEN,
                    'delete' => json_encode([$prop['dso.id'], $netid]),
                ];
                $removeLink = " <a class='noprint' href='$removeLink' title='Remove this reviewer'>[x]</a>";
            } else {
                $removeLink = '';
            }
            $memberInfo = '';
            if ($member) {
                $memberInfo = $member->info();
                $memberInfo = trim(preg_replace('/[-0-9a-z.+_]+@[-0-9a-z.+_]+[a-z]/i', '', $memberInfo));
                $memberInfo = preg_replace('/[\r\n]+/', '<br>', $memberInfo);
                $memberInfo = '<br>' . $memberInfo;
            }
            echo "<p>$memberName$removeLink$memberInfo</p>";
        }
    } else {
        echo "<div><em>NONE</em></div>";
    }
    if (canAdmin() && $DATASTORE->get('assignments_locked')) {
        $form = new \Formward\Form('', $prop['dso.id'] . $type);
        $form->addClass('compact-form');
        $form->addClass('autosubmit');
        $form->addClass('noprint');
        $form['member'] = new \Formward\Fields\Select('');
        $form['member']->nullText = '-- add reviewer --';
        $options = [];
        foreach ($CMS->helper('rac')->members() as $member) {
            $options[$member->netid()] = $member->name();
        }
        asort($options);
        $form['member']->options($options);
        echo $form;
        if ($form->handle()) {
            //save assignments
            $ASSIGNMENTS[$prop['dso.id']][$type][] = $form['member']->value();
            $ASSIGNMENTS[$prop['dso.id']][$type] = array_unique($ASSIGNMENTS[$prop['dso.id']][$type]);
            $DATASTORE->set('assignments', $ASSIGNMENTS);
            //save prefer rule for this assignment
            $guidelines = $DATASTORE->get('guidelines');
            $guidelines[$form['member']->value()][$prop['dso.id']] = ['prefer', $type];
            $DATASTORE->set('guidelines', $guidelines);
            //redirect and notify
            $url = $PACKAGE->url();
            unset($url['args']);
            $PACKAGE->redirect($url);
            $CMS->helper('notifications')->flashConfirmation('Added reviewer assignment');
        }
    }
}

function canAdmin()
{
    global $CMS;
    return $CMS->helper('permissions')->check('assignments/admin', 'rac');
}
