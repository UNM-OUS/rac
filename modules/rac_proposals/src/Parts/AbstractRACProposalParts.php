<?php
namespace Digraph\Modules\rac_proposals\Parts;

abstract class AbstractRACProposalParts extends \Digraph\Modules\Submissions\Parts\AbstractPartsClass
{
    const TEMPLATE_PREFIX = '';

    protected function instructions($name, $noPrefix = false)
    {
        $prefix = $noPrefix ? '' : static::TEMPLATE_PREFIX;
        $t = $this->submission()->cms()->helper('templates');
        $f = 'rac/instructions/' . $prefix . $name . '.twig';
        if ($t->exists($f)) {
            return $t->render(
                $f,
                [
                    'submission' => $this->submission(),
                    'parts' => $this,
                ]
            );
        } elseif (!$noPrefix) {
            return $this->instructions($name, true);
        }
        {
            return '';
        }
    }

    public function construct()
    {
        //basic parts
        $this->chunks['submitter'] = new Chunks\SubmitterMeta2019($this, 'submitter', 'Proposer information');
        $this->chunks['submission'] = new Chunks\SubmissionMeta2019($this, 'submission', 'Proposal information');
    }

    /**
     * Add this proposal's parts to a Zip archive file
     *
     * @param \ZipArchive $zip
     * @return void
     */
    abstract public function zip(\ZipArchive $zip);

    protected function zipAddTextChunk($zip, $chunk, $name, $extraPrefix = null)
    {
        //skip if opted out
        if ($this->submission()['submission_optouts.' . $chunk]) {
            return;
        }
        //otherwise add text file
        if ($text = $this->submission()[$chunk . '.text']) {
            $zip->addFromString("$name.txt", $text);
        }
        //add files
        if (!$extraPrefix) {
            $extraPrefix = $name . '/';
        }
        $fs = $this->submission()->cms()->helper('filestore');
        foreach ($fs->list($this->submission(), $chunk . '_extras') as $file) {
            $zip->addFile($file->path(), $extraPrefix . $file->name());
        }
    }

    /**
     * Add the contents of a file-based chunk to a zip file
     *
     * @param \ZipArchive $zip
     * @param string $chunk
     * @param string $prefix
     * @param string $extra
     * @return void
     */
    protected function zipAddFilesChunk($zip, $chunk, $prefix, $extraPrefix = 'extra')
    {
        //skip if opted out
        if ($this->submission()['submission_optouts.' . $chunk]) {
            return;
        }
        //add files
        $fs = $this->submission()->cms()->helper('filestore');
        foreach ($fs->list($this->submission(), $chunk) as $file) {
            $zip->addFile($file->path(), $prefix . $file->name());
        }
        foreach ($fs->list($this->submission(), $chunk . '_extras') as $file) {
            $zip->addFile($file->path(), $extraPrefix . $file->name());
        }
    }
}
