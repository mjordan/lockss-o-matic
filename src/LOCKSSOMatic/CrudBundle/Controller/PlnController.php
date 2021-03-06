<?php

namespace LOCKSSOMatic\CrudBundle\Controller;

use LOCKSSOMatic\CrudBundle\Entity\Keystore;
use LOCKSSOMatic\CrudBundle\Entity\Pln;
use LOCKSSOMatic\CrudBundle\Form\PlnType;
use LOCKSSOMatic\UserBundle\Security\Acl\Permission\PlnAccessLevels;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * Pln controller.
 *
 * @Route("/pln")
 */
class PlnController extends Controller
{
    /**
     * Lists all Pln entities. Does pagination, even though there's
     * probably never going to be more than 25 PLNs in a LOCKSSOMatic
     * instance.
     *
     * @param Request $request
     *
     * @Route("/", name="pln")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $dql = 'SELECT e FROM LOCKSSOMaticCrudBundle:Pln e';
        $query = $em->createQuery($dql);
        $paginator = $this->get('knp_paginator');
        $entities = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        return array(
            'entities' => $entities,
        );
    }

    /**
     * Creates a new Pln entity.
     *
     * @Route("/", name="pln_create")
     * @Method("POST")
     * @Template("LOCKSSOMaticCrudBundle:Pln:new.html.twig")
     *
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    public function createAction(Request $request) {
        $entity = new Pln();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl(
                'pln_show',
                array('id' => $entity->getId())
            ));
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Pln entity.
     *
     * @param Pln $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Pln $entity) {
        $form = $this->createForm(
            new PlnType(),
            $entity,
            array(
            'action' => $this->generateUrl('pln_create'),
            'method' => 'POST',
                )
        );

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Pln entity.
     *
     * @Route("/new", name="pln_new")
     * @Method("GET")
     * @Template()
     *
     * @return array
     */
    public function newAction() {
        $entity = new Pln();
        $form = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Pln entity.
     *
     * @param int $id
     *
     * @Route("/{id}", name="pln_show")
     * @Method("GET")
     * @Template()
     *
     * @return array
     */
    public function showAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LOCKSSOMaticCrudBundle:Pln')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Pln entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Pln entity.
     *
     * @Route("/{id}/edit", name="pln_edit")
     * @Method("GET")
     * @Template()
     *
     * @param int $id
     *
     * @return array
     */
    public function editAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LOCKSSOMaticCrudBundle:Pln')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Pln entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Creates a form to edit a Pln entity.
     *
     * @param Pln $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Pln $entity) {
        $form = $this->createForm(
            new PlnType($entity),
            $entity,
            array(
            'action' => $this->generateUrl(
                'pln_update',
                array('id' => $entity->getId())
            ),
            'method' => 'PUT',
                )
        );

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * Edits an existing Pln entity.
     *
     * @Route("/{id}", name="pln_update")
     * @Method("PUT")
     * @Template("LOCKSSOMaticCrudBundle:Pln:edit.html.twig")
     *
     * @param Request $request
     * @param int $id
     *
     * @return array|RedirectResponse
     */
    public function updateAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LOCKSSOMaticCrudBundle:Pln')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Pln entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->addFlash('success', 'The PLN has been updated.');
            return $this->redirect($this->generateUrl(
                'pln_show',
                array('id' => $id)
            ));
        }

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a Pln entity.
     *
     * @Route("/{id}/delete", name="pln_delete")
     *
     * @param int $id
     *
     * @return array|RedirectResponse
     */
    public function deleteAction($id) {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('LOCKSSOMaticCrudBundle:Pln')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Pln entity.');
        }

        $em->remove($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('pln'));
    }

    /**
     * Creates a form to delete a Pln entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder()->setAction($this->generateUrl('pln_delete', array('id' => $id)))->setMethod('DELETE')->add('submit', 'submit', array('label' => 'Delete'))->getForm();
    }

    /**
     * Show all of the users and how they can access the PLN.
     *
     * @Route("/{id}/access", name="pln_access")
     * @Template("LOCKSSOMaticCrudBundle:Pln:access.html.twig")
     *
     * @param type $id
     *
     * @return array
     */
    public function showAccessAction($id) {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('LOCKSSOMaticCrudBundle:Pln')->find($id);
        if(! $entity) {
            throw $this->createNotFoundException('The PLN could not be found.');
        }
        $this->get('lom.access')->checkAccess('PLNADMIN', $entity);
        $users = $em->getRepository('LOCKSSOMaticUserBundle:User')->findAll();

        return array(
            'users' => $users,
            'pln' => $entity,
            'levels' => PlnAccessLevels::levels(),
        );
    }

    /**
     * Create the form to edit the user access for the PLN.
     *
     * @param Pln $pln
     * @return Form the form
     */
    private function createEditAccessForm(Pln $pln) {
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository('LOCKSSOMaticUserBundle:User')->findAll();
        $accessManager = $this->get('lom.access');
        $defaultData = array('message' => 'Edit user access');
        $levels = PlnAccessLevels::levels();
        $options = array(
            'method' => 'POST',
            'action' => $this->generateUrl(
                'pln_access_update',
                array('id' => $pln->getId())
            ),
        );
        $builder = $this->createFormBuilder($defaultData, $options);
        foreach ($users as $user) {
            if ($user->hasRole('ROLE_ADMIN')) {
                continue; // skip admins - they can do anything.
            }
            $builder->add(
                'user_'.$user->getId(),
                'choice',
                array(
                'label' => $user->getFullname(),
                'choices' => $levels,
                'empty_value' => 'No access',
                'data' => $accessManager->findAccessLevel($user, $pln),
                'multiple' => false,
                'expanded' => false,
                'mapped' => false,
                'required' => false,
                    )
            );
        }
        $builder->add('submit', 'submit', array('label' => 'Update'));

        return $builder->getForm();
    }

    /**
     * Display a form to edit the user access to a PLN.
     *
     * @Route("/{id}/access/edit", name="pln_access_edit")
     * @Method("GET")
     * @Template("LOCKSSOMaticCrudBundle:Pln:accessEdit.html.twig")
     *
     * @param int $id
     *
     * @return array
     */
    public function editAccessAction($id) {
        $em = $this->getDoctrine()->getManager();
        $pln = $em->getRepository('LOCKSSOMaticCrudBundle:Pln')->find($id);
        if(! $pln) {
            throw $this->createNotFoundException('The PLN was not found.');
        }
        $this->get('lom.access')->checkAccess('PLNADMIN', $pln);
        $form = $this->createEditAccessForm($pln);

        return array(
            'access_edit_form' => $form->createView(),
            'pln' => $pln,
        );
    }

    /**
     * Update user access to the PLN.
     *
     * @param Request $request
     * @param Pln $pln
     */
    private function updateAccess(Request $request, Pln $pln) {
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository('LOCKSSOMaticUserBundle:User')->findAll();
        $accessManager = $this->get('lom.access');
        $formData = $request->request->all();
        $data = $formData['form'];
        foreach ($users as $user) {
            $key = 'user_'.$user->getId();
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $accessManager->setAccess($data[$key], $pln, $user);
        }
    }

    /**
     * Process the access update request and return a
     * redirect.
     *
     * @Route("/{id}/access/edit", name="pln_access_update")
     * @Method("POST")
     *
     * @param Request $request
     * @param int $id
     *
     * @return array|RedirectResponse
     */
    public function updateAccessAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();
        $pln = $em->getRepository('LOCKSSOMaticCrudBundle:Pln')->find($id);
        $this->get('lom.access')->checkAccess('PLNADMIN', $pln);
        $form = $this->createEditAccessForm($pln);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $this->updateAccess($request, $pln);
            $this->addFlash('success', 'The form was saved.');

            return $this->redirect($this->generateUrl(
                'pln_access',
                array('id' => $id)
            ));
        } else {
            $this->addFlash('error', 'The form was not saved.');

            return $this->redirect($this->generateUrl(
                'pln_access_edit',
                array('id' => $id)
            ));
        }
    }

    /**
     * Show a list of the plugins associated with a PLN.
     *
     * @Route("/{id}/plugins", name="pln_plugins")
     * @Template("LOCKSSOMaticCrudBundle:Pln:plugins.html.twig")
     *
     * @param int $id
     *
     * @return array
     */
    public function showPluginsAction($id) {
        $em = $this->getDoctrine()->getManager();
        $pln = $em->getRepository('LOCKSSOMaticCrudBundle:Pln')->find($id);
        if(! $pln) {
            throw $this->createNotFoundException('The PLN could not be found.');
        }
        return array(
            'pln' => $pln,
        );
    }

    /**
     * Create a form to edit the plugins associated with a
     * PLN.
     *
     * @param Pln $pln
     * @return Form
     */
    private function createEditPluginsForm(Pln $pln) {
        $options = array(
            'method' => 'POST',
            'action' => $this->generateUrl('pln_plugins_update', array(
                'id' => $pln->getId(),
            )),
        );
        $builder = $this->createFormBuilder($pln, $options);
        $builder->add('plugins', 'entity', array(
            'class' => 'LOCKSSOMaticCrudBundle:Plugin',
            'multiple' => true,
            'group_by' => 'identifier',
            'required' => false,
        ));

        $builder->add('submit', 'submit', array('label' => 'Update'));

        return $builder->getForm();
    }

    /**
     * Display a form to edit the plugins associated with a PLN.
     *
     * @Route("/{id}/plugins/edit", name="pln_plugins_edit")
     * @Method("GET")
     * @Template("LOCKSSOMaticCrudBundle:Pln:pluginsEdit.html.twig");
     *
     * @param int $id
     *
     * @return array
     */
    public function editPluginsAction($id) {
        $em = $this->getDoctrine()->getManager();
        $pln = $em->getRepository('LOCKSSOMaticCrudBundle:Pln')->find($id);
        $form = $this->createEditPluginsForm($pln);

        return array(
            'pln' => $pln,
            'plugins_edit_form' => $form->createView(),
        );
    }

    /**
     * Update the plugins associated with a PLN.
     *
     * @Route("/{id}/plugins/update", name="pln_plugins_update")
     * @Method("POST")
     *
     * @todo Rejig the edit form. One select per plugin should be the way to go.
     *
     * @param Request $request
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function updatePluginsAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();
        $pln = $em->getRepository('LOCKSSOMaticCrudBundle:Pln')->find($id);
        $form = $this->createEditPluginsForm($pln);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $this->addFlash('success', 'The plugins for this PLN have been updated.');
            $em->flush();

            return $this->redirect($this->generateUrl('pln_plugins', array('id' => $id)));
        }
        $this->addFlash('error', 'The form was not saved.');

        return $this->redirect($this->generateUrl('pln_access_edit', array('id' => $id)));
    }

    /**
     * Display information about the LOCKSS keystore, and
     * show a widget to upload a new keystore. Also handles
     * processing the form for post requests.
     *
     * @todo this method does too much. Split it up to smaller methods.
     *
     * @Route("/{id}/keystore", name="pln_keystore")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     * @param int     $id
     *
     * @return array|RedirectResponse
     */
    public function keystoreAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();
        $pln = $em->getRepository('LOCKSSOMaticCrudBundle:Pln')->find($id);
        if(! $pln) {
            throw $this->createNotFoundException('The PLN was not found.');
        }
        $keystore = $pln->getKeystore();
        if (!$keystore) {
            $keystore = new Keystore();
            $keystore->setPln($pln);
        }

        $form = $this->createFormBuilder()->add('filename', 'file', array(
                    'mapped' => false,
                    'required' => true,
        ))->add('submit', 'submit', array(
                    'label' => 'Save',
        ))->getForm();

        $form->handleRequest($request);
        if ($form->isValid()) {
            $pluginDir = $this->container->get('lom.filepaths')->getPluginsExportDir($pln);
            if ($pluginDir) {
                $file = $form['filename']->getData();
                $file->move($pluginDir, "pln_{$pln->getId()}.keystore");
                $keystore->setFilename($file->getClientOriginalName());
                $keystore->setPath("{$pluginDir}/pln_{$pln->getId()}.keystore");
                $pln->setKeystore($keystore);
                $em->persist($keystore);
                $em->flush();
                $this->addFlash('success', 'The keystore has been added to the PLN.');

                return $this->redirect($this->generateUrl('pln_show', array(
                    'id' => $pln->getId(),
                )));
            }
        }

        return array(
            'pln' => $pln,
            'keystore' => $keystore,
            'form' => $form->createView(),
        );
    }
}
