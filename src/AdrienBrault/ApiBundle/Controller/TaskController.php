<?php

namespace AdrienBrault\ApiBundle\Controller;

use FOS\Rest\Util\Codes;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

use AdrienBrault\ApiBundle\Entity\Task;
use AdrienBrault\ApiBundle\Form\Model\Pagination;

/**
 * @Route("/tasks")
 */
class TaskController extends Controller
{
    /**
     * @Method("GET")
     * @Route("/{id}", name = "api_task_get")
     */
    public function getAction(Task $task)
    {
        return $this->view($task);
    }

    /**
     * @Method("GET")
     * @Route("", name = "api_task_list")
     */
    public function getTasksAction(Request $request)
    {
        $paginationForm = $this->createPaginationForm($pagination = new Pagination());

        if (!$paginationForm->bind($request)->isValid()) {
            return $this->view($paginationForm);
        }

        $taskRepository = $this->getDoctrine()->getManager()->getRepository('AdrienBraultApiBundle:Task');
        $pager = $this->createORMPager($pagination, $taskRepository->createQueryBuilder('t'));

        $this->addBasicRelations($pager); // will add self + navigation links
        $this->addRelation($pager, 'pagination', array('route' => 'api_task_form_pagination'), array(
            'provider' => array('fsc_hateoas.factory.form_view', 'create'),
            'providerArguments' => array($paginationForm, 'GET', 'api_task_list'),
        ));

        return $this->view($pager);
    }

    /**
     * @Method("POST")
     * @Route("", name = "api_task_create")
     */
    public function createAction(Request $request)
    {
        $form = $this->createTaskForm($task = new Task(), true);

        if (!$form->bind($request)->isValid()) {
            return $this->view($form);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($task);
        $em->flush();

        return $this->redirectView($this->generateSelfUrl($task), Codes::HTTP_CREATED);
    }

    /**
     * @Method("PUT")
     * @Route("/{id}", name = "api_task_edit")
     */
    public function editAction(Task $task, Request $request)
    {
        $form = $this->createTaskForm($task);

        if (!$form->bind($request)->isValid()) {
            return $this->view($form);
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->redirectView($this->generateSelfUrl($task), Codes::HTTP_ACCEPTED);
    }

    /**
     * @Method("GET")
     * @Route("/forms/pagination", name = "api_task_form_pagination")
     */
    public function paginationFormAction()
    {
        $form = $this->createPaginationForm($pagination = new Pagination());
        $formView = $this->createFormView($form, 'GET', 'api_task_list'); // will add method/action attributes

        $this->addBasicRelations($formView); // will add self link

        return $this->view($formView);
    }

    /**
     * @Method("GET")
     * @Route("/forms/create", name = "api_task_form_create")
     */
    public function createFormAction()
    {
        $form = $this->createTaskForm($task = new Task(), true);
        $formView = $this->createFormView($form, 'POST', 'api_task_create'); // will add method/action attributes

        $this->addBasicRelations($formView); // will add self link

        return $this->view($formView);
    }

    /**
     * @Method("GET")
     * @Route("/{id}/forms/edit", name = "api_task_form_edit")
     */
    public function editFormAction(Task $task)
    {
        $form = $this->createTaskForm($task);
        $formView = $this->createFormView($form, 'PUT', 'api_task_edit', array('id' => $task->getId())); // will add method/action attributes

        $this->addBasicRelations($formView); // will add self link

        return $this->view($formView);
    }

    protected function createTaskForm(Task $task, $create = false)
    {
        $options = $create ? array('is_create' => true) : array();

        return $this->createFormNamed('task', 'adrienbrault_task', $task, $options);
    }

    protected function createPaginationForm(Pagination $pagination)
    {
        return $this->createFormNamed('pagination', 'adrienbrault_pagination', $pagination);
    }
}
