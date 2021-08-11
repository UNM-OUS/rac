<?php
namespace Digraph\Modules\rac_proposals\Fields;

use Digraph\CMS;
use Formward\FieldInterface;

class FilePDFMulti extends \Digraph\Forms\Fields\FileStoreFieldMulti
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
    public function __construct(string $label, string $name = null, FieldInterface $parent = null, CMS $cms = null, string $path = null, array $exts = null, int $maxSize = null)
    {
        parent::__construct($label, $name, $parent, $cms, $path, $exts, $maxSize);
        //upload new file
        $this['upload'] = new \Formward\Fields\PDF\FilePDFMulti('');
        $this->addTip('PDF files only');
    }

    public function maxPages($set = null)
    {
        return $this['upload']->maxPages($set);
    }

    public function maxPagesPer($set = null)
    {
        return $this['upload']->maxPagesPer($set);
    }
}
