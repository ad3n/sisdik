<?php

namespace Acme\TaskBundle\Form\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
class TaskType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options) {
        $builder
                ->add('task', null,
                        array(
                            'label' => 'Task'
                        ));
        $builder
                ->add('dueDate', 'date',
                        array(
                                'widget' => 'single_text', 'label' => 'Due Date',
                                'format' => 'dd-MM-yyyy',
                                'invalid_message' => 'format tanggal dd-MM-yyyy'
                        ));
        $builder->add('category', new CategoryType());
    }

    public function getDefaultOptions(array $options) {
        return array(
            'data_class' => 'Acme\TaskBundle\Entity\Task',
        );
    }

    public function getName() {
        return 'task';
    }
}
