<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Respondent;
use AppBundle\Entity\User;
use AppBundle\Form\RespondentType;
use AppBundle\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

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
     * @Route("/admin/register", name="admin_registration")
     *
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function registerAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        // 1) build the form
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // 3) Encode the password (you could also do this via Doctrine listener)
            $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);

            // 4) save the User!
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            // ... do any other work - like sending them an email, etc
            // maybe set a "flash" success message for the user

            return $this->redirectToRoute('replace_with_some_route');
        }

        return $this->render(
            'admin/register.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @Route("/admin/create", name="admin_create")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
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
     * @Route(
     *     "/admin/list/{filter}{page}.{_format}",
     *     name="admin_list",
     *     defaults={
     *         "_format": "html",
     *         "filter": "all",
     *         "page": 1
     *     },
     *     requirements={
     *         "_format": "html|csv",
     *         "page": "\d+",
     *         "filter": "[a-zA-Z_]+"
     *     }
     * )
     */
    public function listAction(Request $request, $_format, $filter)
    {
        $em    = $this->get('doctrine.orm.entity_manager');
        switch ($filter) {
            case 'manager_under_five_collabs':
                $dql = "SELECT r FROM AppBundle:Respondent r";
                break;
            case 'all':
                $dql = "SELECT r FROM AppBundle:Respondent r";
                break;
            default:
                throw new \Exception(sprintf(
                    'Cannot understand filter «%s»',
                    $filter
                ));
        }
        $query = $em->createQuery($dql);

        if ($_format == 'html') {
            $paginator  = $this->get('knp_paginator');
            $pagination = $paginator->paginate(
                $query,
                $request->query->getInt('page', 1),
                20
            );

            // parameters to template
            return $this->render('admin/list.html.twig', array('pagination' => $pagination));
        } else {
            $response = new Response($this->get('twig')->render(
                'admin/list.csv.twig',
                array('respondents' => $query->getResult())
            ));

            // Create the disposition of the file
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                sprintf('%s.%s', $filter, $_format)
            );

            // Set the content disposition
            $response->headers->set('Content-Disposition', $disposition);

            return $response;
        }
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
