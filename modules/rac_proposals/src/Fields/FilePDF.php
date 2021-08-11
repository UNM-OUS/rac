<?php
namespace Digraph\Modules\rac_proposals\Fields;

use Digraph\CMS;
use Digraph\DSO\Noun;
use Formward\AbstractField;
use Formward\FieldInterface;

class FilePDF extends \Digraph\Forms\Fields\FileStoreFieldSingle
{
    protected $cms;
    protected $noun;
    protected $path;

    /**
     * Extra args:
     * string $path the filestore path to use
     * array $exts an array of allowed file extensions
     * int $maxSize the maximum file size (per file) in bytes
     */
    public function __construct(string $label, string $name=null, FieldInterface $parent=null, CMS $cms=null, string $path=null, array $exts=null, int $maxSize=null)
    {
        parent::__construct($label, $name, $parent, $cms, $path, $exts, $maxSize);
        //upload new file
        $this['upload'] = new \Formward\Fields\PDF\FilePDF('');
        $this->addTip('PDF files only');
    }

    public function maxPages($set=null)
    {
        return $this['upload']->maxPages($set);
    }
}
