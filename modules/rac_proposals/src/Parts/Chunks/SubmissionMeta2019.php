<?php
namespace Digraph\Modules\rac_proposals\Parts\Chunks;

use Formward\Form;

class SubmissionMeta2019 extends \Digraph\Modules\Submissions\Parts\Chunks\SubmissionMeta
{

    public function body_form() : Form
    {
        $form = parent::body_form();
        $min = 1;
        $max = 10000;
        if ($window = $this->submission()->window()) {
            $min = intval($window['requestamount.minimum']);
            $max = intval($window['requestamount.maximum']);
        }
        $form['submission']->setMinimum($min);
        $form['submission']->setMaximum($max);
        return $form;
    }

    public function body_complete()
    {
        $submission = $this->submission()['submission'];
        echo "<dl>";
        echo "<dt>Title</dt>";
        echo "<dd>".$submission['title']."</dd>";
        echo "<dt>Discipline</dt>";
        echo "<dd>".$submission['discipline']."</dd>";
        echo "<dt>Amount requested</dt>";
        echo "<dd>".$this->submission()->requestedHR()."</dd>";
        echo "</dl>";
    }
}
