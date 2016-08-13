<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Thread;
use AppBundle\Form\ThreadType;
use AppBundle\Entity\Post;
use AppBundle\Form\PostType;
use ReCaptcha\ReCaptcha; // Include the recaptcha lib

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
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $dql   = "SELECT t FROM AppBundle:Thread t ORDER BY t.createdAt DESC";
        $query = $em->createQuery($dql);

        $paginator  = $this->get('knp_paginator');
        $threads = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            20/*limit per page*/
        );


        //$threads = $em->getRepository('AppBundle:Thread')->findAll(); //list all threads
        
        $thread = new Thread(); //create new thread form
        $form = $this->createForm('AppBundle\Form\ThreadType', $thread);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->checkCaptcha($request);

            $em = $this->getDoctrine()->getManager();
            $file = $thread->getImage();
            $fileName = md5(uniqid()).'.'.$file->guessExtension();
            $file->move(
                $this->getParameter('images_directory'),
                $fileName
            );

            $thread->setImage($fileName);
            $thread->setCreatedAt(new \DateTime());
            $thread->setUpdatedAt(new \DateTime());
            
            
            $em->persist($thread);
            $em->flush();

            return $this->redirectToRoute('thread_show', array('id' => $thread->getId()));
        }

        return $this->render('thread/index.html.twig', array(
            'threads' => $threads,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Thread entity.
     *
     * @Route("/{id}", name="thread_show")
     * @Method({"GET", "POST"})
     */
    public function showAction(Request $request, Thread $thread)
    {
        $deleteForm = $this->createDeleteForm($thread);

        $post = new Post();
        $form = $this->createForm('AppBundle\Form\PostType', $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->checkCaptcha($request);
            
            $em = $this->getDoctrine()->getManager();
            $file = $post->getImage();
            if($file)
            {
                $fileName = md5(uniqid()).'.'.$file->guessExtension();

                $file->move(
                    $this->getParameter('images_directory'),
                    $fileName
                );

                $post->setImage($fileName);
            }
            $post->setThread($em->getRepository('AppBundle:Thread')->findOneById($thread->getId()));
            $post->setCreatedAt(new \DateTime());
            $post->setUpdatedAt(new \DateTime());

            $em->persist($post);
            $em->flush();
        }

        return $this->render('thread/show.html.twig', array(
            'form' => $form->createView(),
            'thread' => $thread,
            'posts' => $thread->getPosts(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    protected function checkCaptcha($request)
    {
        $recaptcha = new ReCaptcha('6LfIiicTAAAAAPXqFIFZeDyK61LDrcJQa6SEm0Y6');
        $resp = $recaptcha->verify($request->request->get('g-recaptcha-response'), $request->getClientIp());

        if (!$resp->isSuccess()) {
            // Do something if the submit wasn't valid ! Use the message to show something
            $message = "The reCAPTCHA wasn't entered correctly. Go back and try it again." . "(reCAPTCHA said: " . $resp->error . ")";
            die(); //improve me pls
        }
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
                $this->getParameter('images_directory'),
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
