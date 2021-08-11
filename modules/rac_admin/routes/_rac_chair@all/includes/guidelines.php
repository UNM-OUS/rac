<?php
global $GUIDELINES;
$GUIDELINES = $datastore->get('guidelines');
$GUIDELINES = $GUIDELINES?$GUIDELINES:[];

echo "<h2>Assignment guidelines</h2>";

//handle guideline deletions
$token = $cms->helper('session')->getToken('rac.guidelines.delete');
if ($package['url.args.token'] == $token) {
    list($netid,$pid) = json_decode($package['url.args.delete']);
    unset($GUIDELINES[$netid][$pid]);
    if (!$GUIDELINES[$netid]) {
        unset($GUIDELINES[$netid]);
    }
    $datastore->set('guidelines', $GUIDELINES);
    $cms->helper('notifications')->flashConfirmation('Removed guideline');
    $package->redirect($this->url('_rac_chair','prop_assign'));
}

//list all guidelines in a table
echo "<table>";
foreach ($GUIDELINES as $netid => $ps) {
    $member = $netid;
    foreach ($MEMBERS as $m) {
        if ($m->netid() == $netid) {
            $member = $m->name();
        }
    }
    foreach ($ps as $propid => $rule) {
        $prop = $cms->read($propid);
        echo "<tr>";
        echo "<td>$member</td>";
        echo "<td>".implode(' ',$rule)."</td>";
        if ($prop) {
            echo "<td>".$prop->name()."</td>";
        }else {
            echo "<td><em>[".$propid."]</em></td>";
        }
        $deletelink = $this->url('_rac_chair','prop_assign',
        [
            'delete' => json_encode([$netid,$propid]),
            'token' => $token
        ]);
        echo "<td><a href='$deletelink' class='row-button row-delete'>delete</a></td>";
        echo "</tr>";
    }
}
echo "</table>";

$gform = new \Formward\Form('','prop_guidelines');

$gform['member'] = new \Formward\Fields\Select('');
$gform['member']->required(true);
$options = [];
foreach ($MEMBERS as $member) {
    $options[$member->netid()] = $member->name();
}
asort($options);
$gform['member']->options($options);

$gform['rule'] = new \Formward\Fields\Select('');
$gform['rule']->required(true);
$gform['rule']->options([
    'prefer' => 'Preferred if possible',
    'avoid' => 'Avoided if possible',
    'block' => 'Not allowed'
]);

$gform['role'] = new \Formward\Fields\Select('');
$gform['role']->required(true);
$gform['role']->options([
    'any' => 'In any position',
    'regular' => 'As a regular reviewer',
    'lead' => 'As a lead reviewer'
]);

$gform['prop'] = new \Formward\Fields\Select('');
$gform['prop']->required(true);
$options = [
    'all' => 'All proposals'
];
foreach ($PROPS as $prop) {
    $options[$prop['dso.id']] = $prop->name();
}
asort($options);
$gform['prop']->options($options);

$gform->submitButton()->label('Save assignment guideline');
$gform->addClass('compact-form');
$gform->addTip('Use this form to steer the output of the reviewer assignment algorithm.');

echo $gform;

if ($gform->handle()) {
    $GUIDELINES[$gform['member']->value()][$gform['prop']->value()] = [
        $gform['rule']->value(),
        $gform['role']->value()
    ];
    $datastore->set('guidelines', $GUIDELINES);
    $cms->helper('notifications')->flashConfirmation('Added guideline');
    $package->redirect($this->url('_rac_chair','prop_assign'));
    return;
}

if (!$GUIDELINES) {
    return;
}
