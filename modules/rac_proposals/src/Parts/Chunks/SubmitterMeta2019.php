<?php
namespace Digraph\Modules\rac_proposals\Parts\Chunks;

class SubmitterMeta2019 extends \Digraph\Modules\Submissions\Parts\Chunks\SubmitterMeta
{
    public function body_complete()
    {
        $submitter = $this->submission()['submitter'];
        echo "<dl>";
        echo "<dt>Name</dt>";
        echo "<dd>".$submitter['firstname']." ".$submitter['lastname']."</dd>";
        echo "<dt>Rank</dt>";
        echo "<dd>".$submitter['rank']."</dd>";
        echo "<dt>Campus Address</dt>";
        echo "<dd>".$this->submission()->addressHR()."</dd>";
        echo "<dt>Email</dt>";
        echo "<dd>".$submitter['email']."</dd>";
        echo "<dt>Phone</dt>";
        echo "<dd>".$submitter['phone']."</dd>";
        echo "<dt>College</dt>";
        echo "<dd>".$submitter['college']."</dd>";
        echo "<dt>Department</dt>";
        echo "<dd>".$submitter['department']."</dd>";
        echo "<dt>Years as voting faculty</dt>";
        echo "<dd>".$submitter['yearsvoting']."</dd>";
        echo "</dl>";
    }
}
