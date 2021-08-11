<?php
namespace Digraph\Modules\rac_proposals\Parts\Chunks;

use Digraph\Forms\Fields\FileStoreFieldMulti;
use Formward\Form;

class UploadPDFPlus extends UploadPDF
{
    protected $extrasLabel = 'Additional/supporting files';

    public function extrasLabel($set = null)
    {
        if ($set !== null) {
            $this->extrasLabel = $set;
        }
        return $this->extrasLabel;
    }

    public function body_form(): Form
    {
        $form = parent::body_form();
        //set up additional upload field
        $form['extras'] = new FileStoreFieldMulti(
            $this->extrasLabel, //label
            'extras', //name
            null, //parent
            $this->submission()->cms(), //cms
            $this->name . '_extras' //filestore path
        );
        //tell field which noun it's working on
        $form['extras']->dsoNoun($this->submission());
        //return form
        return $form;
    }

    public function form_handle(Form $form)
    {
        parent::form_handle($form);
        $form['extras']->hook_formWrite($this->submission(), []);
    }

    public function body_complete()
    {
        parent::body_complete();
        $fs = $this->submission()->cms()->helper('filestore');
        if ($files = $fs->list($this->submission(), $this->name . '_extras')) {
            echo "<p><strong>" . $this->extrasLabel() . "</strong></p>";
            echo "<div class='uploaded-files secondary'>";
            foreach ($files as $f) {
                echo $f->metaCard();
            }
            echo "</div>";
        }
    }
}
