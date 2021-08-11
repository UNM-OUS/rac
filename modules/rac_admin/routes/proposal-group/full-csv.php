<?php
$package['response.ttl'] = 3600 * 8;
$s = $cms->helper('strings');

$data = [[
    'year',
    'semester',
    'discipline',
    'college',
    'submitter name',
    'submitter email',
    'submitter netid',
    'submitter rank',
    'submitter years voting faculty',
    'amount requested',
    'amount funded',
    'final report due',
    'final report url',
    'submission url',
]];

foreach ($package->noun()->calls() as $call) {
    if (!$call->ended()) {
        continue;
    }

    $calldata = array_map(
        function ($prop) use ($call, $s) {
            $out = [
                $call['semester.year'], // year
                $call['semester.semester'], // semester
                $prop['submission.discipline'], // discipline
                $prop['submitter.college'], // college
                $prop['submitter.firstname'] . ' ' . $prop['submitter.lastname'], // submitter name
                $prop['submitter.email'], // submitter email
                preg_replace('/@netid$/', '', $prop['owner']), // submitter netid
                $prop['submitter.rank'], // submitter rank
                $prop['submitter.yearsvoting'], // submitter years voting faculty
                $prop['submission.requested'], // amount requested
            ];
            // amount funded
            if ($prop->finalDecision()) {
                $out[] = $prop->decision() ? ($prop->decision()->funded() ? $prop->decision()->funded() : 0) : '[no decision]';
            } else {
                $out[] = '[no decision]';
            }
            // final report due
            if ($prop['report_due']) {
                $out[] = $s->datetime($prop['report_due']);
            } else {
                $out[] = '';
            }
            // final report url
            if ($prop->report()) {
                $out[] = $prop->report()->url();
            } else {
                $out[] = '';
            }
            // submission url
            $out[] = $prop->url();
            // return row
            return $out;
        },
        $call->completeSubmissions()
    );

    $data = array_merge($data, $calldata);
}

$data = array_map(
    function ($row) {
        foreach ($row as $i => $d) {
            $d = transliterate($d);
            $d = str_replace('"', '""', $d);
            $row[$i] = '"' . $d . '"';
        }
        return implode(',', $row);
    },
    $data
);

$package->makeMediaFile(
    'Full RAC Proposal Data downloaded ' . date('Y-m-d', time()) . '.csv'
);
$package->binaryContent(implode("\r\n", $data));

function transliterate($string)
{
    $string = strtr(
        utf8_decode($string),
        utf8_decode(
            'ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'
        ),
        'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy'
    );
    return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
}
