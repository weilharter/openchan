<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Thread;
use AppBundle\Form\ThreadType;

/**
 * Thread controller.
 *
 * @Route("/thread")
 */
class ThreadController extends Controller
{
    /**
     * Lists all Thread entities.
     *
     * @Route("/", name="thread_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $threads = $em->getRepository('AppBundle:Thread')->findAll();

        return $this->render('thread/index.html.twig', array(
            'threads' => $threads,
        ));
    }

    /**
     * Creates a new Thread entity.
     *
     * @Route("/new", name="thread_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $thread = new Thread();
        $form = $this->createForm('AppBundle\Form\ThreadType', $thread);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            
            // $file stores the uploaded PDF file
            /** @var Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $thread->getImage();

            // Generate a unique name for the file before saving it
            $fileName = md5(uniqid()).'.'.$file->guessExtension();

            // Move the file to the directory where brochures are stored
            $file->move(
                '%kernel.root_dir%/../web/uploads/images',
                $fileName
            );

            // Update the 'brochure' property to store the PDF file name
            // instead of its contents
            $thread->setImage($fileName);
            $thread->setCreatedAt(new \DateTime());
            $thread->setUpdatedAt(new \DateTime());
            
            
            $em->persist($thread);
            $em->flush();

            return $this->redirectToRoute('thread_show', array('id' => $thread->getId()));
        }

        return $this->render('thread/new.html.twig', array(
            'thread' => $thread,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Thread entity.
     *
     * @Route("/{id}", name="thread_show")
     * @Method("GET")
     */
    public function showAction(Thread $thread)
    {
        $deleteForm = $this->createDeleteForm($thread);

        return $this->render('thread/show.html.twig', array(
            'thread' => $thread,
            'posts' => $thread->getPosts(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Thread entity.
     *
     * @Route("/{id}/edit", name="thread_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Thread $thread)
    {
        $deleteForm = $this->createDeleteForm($thread);
        $editForm = $this->createForm('AppBundle\Form\ThreadType', $thread);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($thread);
            $em->flush();

            return $this->redirectToRoute('thread_edit', array('id' => $thread->getId()));
        }

        return $this->render('thread/edit.html.twig', array(
            'thread' => $thread,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Thread entity.
     *
     * @Route("/{id}", name="thread_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Thread $thread)
    {
        $form = $this->createDeleteForm($thread);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($thread);
            $em->flush();
        }

        return $this->redirectToRoute('thread_index');
    }

    /**
     * Creates a form to delete a Thread entity.
     *
     * @param Thread $thread The Thread entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Thread $thread)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('thread_delete', array('id' => $thread->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
