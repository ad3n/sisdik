<?php

namespace Acme\TaskBundle\Controller;
use Assetic\Asset\StringAsset;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Acme\TaskBundle\Entity\Task;
use Acme\TaskBundle\Form\Type\TaskType;

/**
 * 
 * @Route("/task")
 */
class DefaultController extends Controller
{
    /**
     * @Route("/new", name="task_new")
     */
    public function newAction(Request $request) {
        // create a task and give it some dummy data for this example
        $task = new Task();
        $form = $this->createForm(new TaskType(), $task);

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            if ($form->isValid()) {
                // perform some action, such as saving the task to the database
                $this->get('session')
                        ->setFlash('notice',
                                'data inserted: task "' . $task->getTask() . '", due date "'
                                        . $task->getDueDate()->format('d/m/Y') . '", category "' . $task->getCategory() . '"');
                return $this->redirect($this->generateUrl('task_success'));
            }
        } else {
            $task->setTask('Write a blog post');
            $task->setDueDate(new \DateTime('2012-08-19'));
            $form = $this->createForm(new TaskType(), $task);
        }
        return $this
                ->render('AcmeTaskBundle:Default:new.html.twig',
                        array(
                            'form' => $form->createView(),
                        ));

    }

    /**
     * @Route("/success", name="task_success")
     * @Template()
     */
    public function successAction() {
        //         $task = new Task();
        //         $form = $this->createForm(new TaskType(), $task);
        //         $form->getData();
        return array(
            "message" => "data has been successfully inserted"
        );
    }
}
