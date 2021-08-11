<?php
namespace Digraph\Modules\rac_proposals\Parts\Chunks;

use Digraph\Modules\rac_proposals\Fields\FilePDFMulti;
use Formward\Form;

class UploadPDFMulti extends \Digraph\Modules\Submissions\Parts\Chunks\AbstractChunk
{
    protected $maxPages = null;
    protected $maxPagesPer = null;
    protected $uploadFieldLabel = 'Upload PDFs';

    public function uploadFieldLabel($set = null)
    {
        if ($set !== null) {
            $this->uploadFieldLabel = $set;
        }
        return $this->uploadFieldLabel;
    }

    public function body_form(): Form
    {
        $form = $this->form();
        $form->attr('enctype', 'multipart/form-data');
        //set up upload field
        $form['upload'] = new FilePDFMulti(
            $this->uploadFieldLabel, //label
            'upload', //name
            null, //parent
            $this->submission()->cms(), //cms
            $this->name//filestore path
        );
        //make required, and specify max pages
        $form['upload']->required(true);
        $form['upload']->maxPages($this->maxPages);
        $form['upload']->maxPagesPer($this->maxPagesPer);
        //tell field which noun it's working on
        $form['upload']->dsoNoun($this->submission());
        //return form
        return $form;
    }

    public function complete()
    {
        return parent::complete() || $this->submission()['filestore.' . $this->name];
    }

    public function body_complete()
    {
        echo "<div class='uploaded-files primary'>";
        $fs = $this->submission()->cms()->helper('filestore');
        foreach ($fs->list($this->submission(), $this->name) as $f) {
            echo $f->metaCard();
        }
        echo "</div>";
    }

    public function maxPages($set = null)
    {
        if ($set !== null) {
            $this->maxPages = $set;
        }
        return $this->maxPages;
    }

    public function maxPagesPer($set = null)
    {
        if ($set !== null) {
            $this->maxPagesPer = $set;
        }
        return $this->maxPagesPer;
    }

    public function form_handle(Form $form)
    {
        $form['upload']->hook_formWrite($this->submission(), []);
    }
}
