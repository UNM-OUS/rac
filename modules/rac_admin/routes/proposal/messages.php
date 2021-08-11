<?php
$package->cache_noCache();

$prop = $package->noun();
//check viewability to avoid leaking information in notifications
if (!$prop->isViewable()) {
    $package->error(403);
    return;
}

$package['fields.page_title'] = $package['fields.page_name'] = "Message center";
$package['response.ttl'] = 60;

$tag = $prop['dso.id'];

$mail = $cms->helper('mail');
$paginator = $cms->helper('paginator');
echo $paginator->paginate(
    $mail->tagCount($tag),
    $package,
    'page',
    20,
    function ($start, $end) use ($mail, $cms, $tag) {
        ob_start();
        $s = $cms->helper('strings');
        echo "<table>";
        echo "<tr><th>Message</th><th>Sent</th></tr>";
        foreach ($mail->tagged($tag, $start - 1, $end - $start + 1) as $qm) {
            echo "<tr>";
            echo "<td valign='top'>" . $qm->summaryText(['hidetags' => true, 'hidebcc' => true, 'hidereplyto' => true, 'hidefrom' => true, 'hidecc' => true]) . "</td>";
            echo "<td valign='top'>";
            if ($qm->sendAfter > time()) {
                echo "Queued: " . $s->dateHTML($qm->sendAfter);
            } elseif ($qm->sent) {
                echo $s->dateHTML($qm->sent);
                if ($qm->error) {
                    echo "<br><strong>Error sending:</strong><br>" . $qm->error;
                }
            } else {
                echo "Queued";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        return ob_get_clean();
    }
);
