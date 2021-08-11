<?php
namespace Digraph\Modules\rac_proposals\Parts\Chunks;

use Formward\Form;

class TextArea extends \Digraph\Modules\Submissions\Parts\Chunks\AbstractChunk
{
    protected $maxWords = null;

    public function body_form(): Form
    {
        $form = $this->form();
        $form['content'] = $this->submission()->cms()->helper('forms')->field('digraph_content', '');
        $form['content']->extra(false); //disable extra BBCode filters
        $form['content']->selectable(false); //hide mode selection
        $form['content']->required(true); //mark required
        $form['content']->default($this->submission()[$this->name]);
        if ($this->maxWords()) {
            $form['content']->addTip("Maximum " . $this->maxWords() . " words", 'maxwords');
            $form['content']->addValidatorFunction(
                'maxwords',
                function ($field) {
                    if ($value = $field->value()['text']) {
                        if ($this->str_word_count_utf8($value) > $this->maxWords()) {
                            return "Maximum " . $this->maxWords() . " words (" . $this->str_word_count_utf8($value) . " entered)";
                        }
                    }
                    return true;
                }
            );
        }
        return $form;
    }

    public function str_word_count_utf8($str)
    {
        return count(preg_split('~[^\p{L}\p{N}\']+~u', $str));
    }

    public function form_handle(Form $form)
    {
        $submission = $this->submission();
        $submission[$this->name] = $form['content']->value();
        $submission->update();
    }

    public function body_complete()
    {
        echo "<div>";
        echo $this->submission()->cms()->helper('filters')
            ->filterContentField($this->submission()[$this->name], $this->submission()['dso.id']);
        echo "</div>";
    }

    public function maxWords($set = null)
    {
        if ($set !== null) {
            $this->maxWords = $set;
        }
        return $this->maxWords;
    }
}
