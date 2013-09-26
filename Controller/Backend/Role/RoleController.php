<?php

namespace Egzakt\SystemBundle\Controller\Backend\Role;

use Egzakt\SystemBundle\Lib\DeletableResult;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Egzakt\SystemBundle\Lib\Backend\BaseController;
use Egzakt\SystemBundle\Entity\Role;
use Egzakt\SystemBundle\Form\Backend\RoleType;

/**
 * Role Controller.
 */
class RoleController extends BaseController
{

    /**
     * @var bool
     */
    protected $isAdmin;

    /**
     * @var bool
     */
    protected $isDeveloper;

    /**
     * @var array
     */
    protected $rolesAdmin;

    /**
     * Init
     */
    public function init()
    {
        parent::init();

        // Check if the current User has the privileges
        if (!$this->get('security.context')->isGranted('ROLE_BACKEND_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        $this->createAndPushNavigationElement('Roles', 'egzakt_system_backend_role');

        // Add/remove some behaviors if Admin
        $this->isAdmin = $this->get('security.context')->isGranted('ROLE_BACKEND_ADMIN');
        $this->isDeveloper = $this->get('security.context')->isGranted('ROLE_DEVELOPER');
        $this->rolesAdmin = array('ROLE_BACKEND_ADMIN', 'ROLE_DEVELOPER');
    }

    /**
     * Lists all Role entities.
     *
     * @return Response
     */
    public function indexAction()
    {
        if (!$this->isDeveloper) {
            $roles = $this->getEm()->getRepository('EgzaktSystemBundle:Role')->findAllExcept(array('ROLE_DEVELOPER', 'ROLE_BACKEND_ACCESS'));
        } else {
            $roles = $this->getEm()->getRepository('EgzaktSystemBundle:Role')->findAllExcept('ROLE_BACKEND_ACCESS');
        }

        return $this->render('EgzaktSystemBundle:Backend/Role/Role:list.html.twig', array('roles' => $roles));
    }

    /**
     * Displays a form to edit an existing Role entity.
     *
     * @param $id
     * @param Request $request
     *
     * @return RedirectResponse|Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function editAction($id, Request $request)
    {
        $entity = $this->getEm()->getRepository('EgzaktSystemBundle:Role')->find($id);

        if (!$entity) {
            $entity = $this->initEntity(new Role());
        }

        // Not editable
        if ($entity->getRole() == 'ROLE_DEVELOPER' && !$this->isDeveloper) {
            throw new NotFoundHttpException();
        }

        $this->pushNavigationElement($entity);

        $form = $this->createForm(new RoleType(), $entity, array('admin' => in_array($entity->getRole(), $this->rolesAdmin)));

        if ($request->getMethod() == 'POST') {

            $form->submit($request);

            if ($form->isValid()) {

                // Set a Rolename for this Role
                if (!in_array($entity->getRole(), $this->rolesAdmin)) {
                    $roleName = 'ROLE_BACKEND_' . strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $entity->getName()));
                    $entity->setRole($roleName);
                }

                $this->getEm()->persist($entity);
                $this->getEm()->flush();

                $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans(
                    '%entity% has been updated.',
                    array('%entity%' => $entity))
                );

                if ($request->request->has('save')) {
                    return $this->redirect($this->generateUrl('egzakt_system_backend_role'));
                }

                return $this->redirect($this->generateUrl('egzakt_system_backend_role_edit', array(
                    'id' => $entity->getId() ? : 0
                )));
            } else {
                $this->get('session')->getFlashBag()->add('error', 'Some fields are invalid.');
            }
        }

        return $this->render(
            'EgzaktSystemBundle:Backend/Role/Role:edit.html.twig',
            array(
                'entity' => $entity,
                'form' => $form->createView()
            )
        );
    }

    /**
     * Check if we can delete a Role.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws NotFoundHttpException
     */
    public function checkDeleteAction(Request $request, $id)
    {
        $entity = $this->getEm()->getRepository('EgzaktSystemBundle:Role')->find($id);

        if (null === $entity) {
            throw new NotFoundHttpException();
        }

        $result = $this->checkDeletable($entity);
        $output = $result->toArray();
        $output['template'] = $this->renderView('EgzaktSystemBundle:Backend/Core:delete_message.html.twig',
            array(
                'entity' => $entity,
                'result' => $result
            )
        );

        return new JsonResponse($output);

    }

    /**
     * Deletes a Role entity
     *
     * @param $id
     *
     * @return RedirectResponse|Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @throws \Exception
     */
    public function deleteAction($id)
    {
        $role = $this->getEm()->getRepository('EgzaktSystemBundle:Role')->find($id);

        if (!$role) {
            throw $this->createNotFoundException('Unable to find Role entity.');
        }

        // Don't delete some roles
        if ($this->checkDeletable($role)->isFail()) {
            throw new \Exception('You can\'t delete this role.');
        }

        // Call the translator before we flush the entity so we can have the real __toString()
        $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans(
            '%entity% has been deleted.',
            array('%entity%' => $role))
        );

        $this->getEm()->remove($role);
        $this->getEm()->flush();

        return $this->redirect($this->generateUrl('egzakt_system_backend_role'));
    }
}
