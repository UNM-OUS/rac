<?php
namespace Digraph\Modules\rac_admin;

class RACMember
{
    protected $data;
    protected $cms;

    public function __construct($data, $cms)
    {
        $this->data = $data;
        $this->cms = $cms;
        $ns = $this->cms->helper('datastore')->namespace('rac_member_data');
        $ns->set($this->netid(), $data);
    }

    public function email()
    {
        $found = $this->netid() . '@unm.edu';
        preg_replace_callback(
            '/[-0-9a-z.+_]+@[-0-9a-z.+_]+[a-z]/i',
            function ($matches) use ($found) {
                if ($found) {
                    return;
                }
                $email = $matches[0];
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $found = $email;
                }
            },
            $this->info()
        );
        return strtolower($found);
    }

    public function disciplines(array $set = null)
    {
        $ns = $this->cms->helper('datastore')->namespace('rac_member_disciplines');
        if ($set !== null) {
            $ns->set($this->netid(), $set);
        }
        if (!$disc = $ns->get($this->netid())) {
            $ns->set($this->netid(), []);
            $disc = [];
        }
        return $disc;
    }

    public function positions()
    {
        return $this->data['positions'];
    }

    public function netid()
    {
        return $this->data['netid'];
    }

    public function name()
    {
        return $this->data['name'];
    }

    public function info()
    {
        return $this->data['info'];
    }
}
