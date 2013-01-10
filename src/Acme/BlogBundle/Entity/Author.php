<?php

namespace Acme\BlogBundle\Entity;
use Symfony\Component\Validator\Constraints as Assert;

class Author
{
//     /**
//      * @Assert\NotBlank(message = "nama tidak boleh kosong")
//      * @Assert\MinLength(limit = 3, message = "nama harus lebih dari 3 karakter")
//      */
    public $name;
    
    public function getName() {
        return $this->name;
    }
    
    public function setName($name) {
        $this->name = $name;
    }
    
}
