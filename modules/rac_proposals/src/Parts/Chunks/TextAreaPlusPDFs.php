<?php
namespace Digraph\Modules\rac_proposals\Parts\Chunks;

use Digraph\Modules\rac_proposals\Fields\FilePDFMulti;
use Formward\Form;

class TextAreaPlusPDFs extends TextAreaPlus
{
    protected $extrasLabel = 'Additional PDFs';
    protected $extrasMaxPages = null;
    protected $extrasMaxPagesPer = null;

    public function extrasMaxPages($set = null)
    {
        if ($set !== null) {
            $this->extrasMaxPages = $set;
        }
        return $this->extrasMaxPages;
    }

    public function extrasMaxPagesPer($set = null)
    {
        if ($set !== null) {
            $this->extrasMaxPagesPer = $set;
        }
        return $this->extrasMaxPagesPer;
    }

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
        $form['extras'] = new FilePDFMulti(
            $this->extrasLabel, //label
            'extras', //name
            null, //parent
            $this->submission()->cms(), //cms
            $this->name . '_extras' //filestore path
        );
        $form['extras']->maxPages($this->extrasMaxPages);
        $form['extras']->maxPagesPer($this->extrasMaxPagesPer);
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
}
