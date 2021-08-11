<?php
$call = $package->noun();
if (!$call->ended()) {
    echo "<p>No statistics are available until the submission window ends. Please check back later.</p>";
    return;
}

$package['response.ttl'] = 3600*8;

$data = array_map(
    function ($prop) use ($call) {
        $out = [
            'prop-'.hash('crc32', hash('md2', $prop['dso.id'].$prop->name())),
            $call['semester.year'],
            $call['semester.semester'],
            $prop['submission.discipline'],
            $prop['submitter.college'],
            $prop['submitter.rank'],
            $prop['submitter.yearsvoting'],
            $prop['submission.requested']
        ];
        if ($prop->finalDecision()) {
            $out[] = $prop->decision()?($prop->decision()->funded()?$prop->decision()->funded():0):'[no decision]';
        } else {
            $out[] = '[no decision]';
        }
        return $out;
    },
    $call->completeSubmissions()
);

ksort($data);

array_unshift($data, [
    'identifier',
    'year',
    'semester',
    'discipline',
    'college',
    'submitter rank',
    'submitter years voting faculty',
    'amount requested',
    'amount funded'
]);

$data = array_map(
    function ($row) {
        foreach ($row as $i => $d) {
            $d = transliterate($d);
            $d = str_replace('"', '""', $d);
            $row[$i] = '"'.$d.'"';
        }
        return implode(',', $row);
    },
    $data
);

$package->makeMediaFile(
    $call->name().' '.$call['dso.id'].'.csv'
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
