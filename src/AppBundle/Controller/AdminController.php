<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Respondent;
use AppBundle\Form\RespondentType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class AdminController extends Controller
{
    /**
     * @Route("/admin", name="admin_homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->render('index.html.twig', [
            'dataFound' => $this->get('session')->get('data') ? true : false,
        ]);
    }

    /**
     * @Route("/admin/create", name="admin_create")
     *
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $respondent = new Respondent();
        $form = $this->createForm(RespondentType::class, $respondent);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($respondent);
            $em->flush();

            return $this->redirectToRoute('admin_list');
        }

        return $this->render(
            'admin/create.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @Route("/admin/list", name="admin_list")
     */
    public function listAction(Request $request)
    {
        $em    = $this->get('doctrine.orm.entity_manager');
        $dql   = "SELECT r FROM AppBundle:Respondent r";
        $query = $em->createQuery($dql);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        // parameters to template
        return $this->render('admin/list.html.twig', array('pagination' => $pagination));
    }

    /**
     * @Route("/admin/results", name="results")
     */
    public function resultAction()
    {
        $responses = array();
        $path = $this->container->getParameter('kernel.root_dir'). '/../app/Responses/';
        if (($handle = fopen($path . '/_toutes.csv', "r")) !== FALSE) {
            while (($line = fgetcsv($handle, 1000, ";")) !== FALSE) {
                $responses[] = $line;
            }
            fclose($handle);
        }

        $likertQuestions = $this->container->getParameter('likert_questions');
        $questionsFlat = array();
        foreach ($likertQuestions as $page => $questions) {
            foreach ($questions as $question) {
                // $question['scale'] = $likertScales[$question['scale']];
                $questionsFlat[] = $question;
            }
        }
        $questionsFlat = array_merge(
            array(
                array('label'=>'date_debut'),
                array('label'=>'date_fin'),
            ),
            $questionsFlat,
            array(
                array('label'=>'age'),
                array('label'=>'Sexe'),
                array('label'=>'Situation_famille'),
                array('label'=>'Nombre_enfants_a_charge'),
                array('label'=>'Profession'),
                array('label'=>'Secteur'),
                array('label'=>'Intitule_poste'),
                array('label'=>'Heures_travail_semaine'),
                array('label'=>'Heures_travail_mois'),
                array('label'=>'Satisfaction_salaire'),
                array('label'=>'Duree_poste'),
                array('label'=>'Duree_entreprise'),
                array('label'=>'Societe'),
                array('label'=>'Domain'),
                array('label'=>'Domain_other'),
                array('label'=>'Nombre_salaries_etablissement'),
                array('label'=>'Nombre_salaries_entreprise'),
            )
        );

        $inversedResponses = array();
        foreach ($responses as $x => $line) {
            foreach ($line as $y => $item) {
                $inversedResponses[$y][$x] = $item;
            }
        }

        $iResponse = 0;
        $data = array();
        foreach ($questionsFlat as &$question) {
            if (array_key_exists($iResponse, $inversedResponses)) {
                $data[] = array_merge(array($question['label']),$inversedResponses[$iResponse]);
                $iResponse++;
            } else {
                $data[] = array($question['label']);
            }
        }

        return $this->render('data.html.twig', [
            'responses' => $data,
        ]);
    }
}
