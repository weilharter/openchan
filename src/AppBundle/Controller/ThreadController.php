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
 * @Route("/{board_name}")
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
        $board = $em->getRepository('AppBundle:Board')->findOneByName($request->get('board_name')); //list all threads1

        $paginator  = $this->get('knp_paginator');
        $threads = $paginator->paginate(
            $em->getRepository('AppBundle:Thread')->findAllOrderedByCreatedAt($board), /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            8/*limit per page*/
        );
        
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
            $thread->setBoard($board);
            $thread->setCreatedAt(new \DateTime());
            $thread->setUpdatedAt(new \DateTime());
            
            
            $em->persist($thread);
            $em->flush();

            return $this->redirectToRoute('thread_show', array('id' => $thread->getId(), 'board_name' => $board->getName()));
        }

        return $this->render('thread/index.html.twig', array(
            'board' => $board,
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
        $em = $this->getDoctrine()->getManager();
        $board = $em->getRepository('AppBundle:Board')->findOneByName($request->get('board_name')); //list all threads1
        
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
            'board' => $board,
            'thread' => $thread,
            'posts' => $thread->getPosts(),
            'form' => $form->createView()
        ));
    }

    protected function checkCaptcha($request)
    {
//        $recaptcha = new ReCaptcha('6LfIiicTAAAAAPXqFIFZeDyK61LDrcJQa6SEm0Y6');
//        $resp = $recaptcha->verify($request->request->get('g-recaptcha-response'), $request->getClientIp());
//
//        if (!$resp->isSuccess()) {
//            // Do something if the submit wasn't valid ! Use the message to show something
//            $message = "The reCAPTCHA wasn't entered correctly. Go back and try it again." . "(reCAPTCHA said: " . $resp->error . ")";
//            die(); //improve me pls
//        }
    }
}
