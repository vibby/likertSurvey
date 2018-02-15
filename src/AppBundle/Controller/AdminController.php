<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Respondent;
use AppBundle\Form\ManyRespondentType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class AdminController extends Controller
{
    const FILTER_ALL = 'all';
    CONST FILTER_MANAGER_UNDER_COLLABORATORS_COUNT = 'manager_with_few_collabs';
    CONST FILTER_PREVIOUSLY_INSERTED_KEYS = 'previously_inserted_keys';
    CONST FILTER_UNCONNECTED_SINCE_TEN_DAYS = 'unconnected_since_ten_days';

    /**
     * @Route("/admin/createMany", name="admin_create_many")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createManyAction(Request $request)
    {
        $form = $this->createForm(ManyRespondentType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $respondents = [];
            foreach ($form->getData()['emails'] as $email) {
                $respondent = new Respondent();
                $respondent->setEmail($email);
                $respondent->setSource('admin');
                $respondent->setDomain($request->getHost());
                $em->persist($respondent);
                $respondents[] = $respondent;
            }
            $em->flush();

            $respondents = array_map(function (Respondent $respondent) {
                return $respondent->getKey();
            }, $respondents);
            $this->get('session')->set('previously_inserted_keys', $respondents);

            return $this->redirectToRoute('admin_list', ['filter' => 'previously_inserted_keys']);
        }

        return $this->render(
            'admin/create.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @Route(
     *     "/admin/{filter}{page}.{_format}",
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
    public function listAction($_format, $filter, $page)
    {
        $repo = $this->get('doctrine.orm.entity_manager')->getRepository(Respondent::class);
        switch ($filter) {
            case self::FILTER_ALL:
                $query = $repo->getQueryAll();
                break;
            case self::FILTER_MANAGER_UNDER_COLLABORATORS_COUNT:
                $query = $repo->getQueryManagerUnderCollaboratorsCount(5);
                break;
            case self::FILTER_PREVIOUSLY_INSERTED_KEYS:
                $query = $repo->getQueryKeyList($this->get('session')->get('previously_inserted_keys'));
                break;
            case self::FILTER_UNCONNECTED_SINCE_TEN_DAYS:
                $query = $repo->getQueryUnconnectedSinceXDays(10);
                break;
            default:
                throw new \Exception(sprintf(
                    'Cannot understand filter «%s»',
                    $filter
                ));
        }

        if ($_format == 'html') {
            $paginator  = $this->get('knp_paginator');
            $pagination = $paginator->paginate(
                $query,
                $page,
                20,
                ['wrap-queries' => true]
            );

            // parameters to template
            return $this->render(
                'admin/list.html.twig',
                [
                    'pagination' => $pagination,
                    'filter' => $filter,
                ]
            );
        } else {
            $response = new Response($this->get('twig')->render(
                'admin/list.csv.twig',
                array('respondents' => $query->getResult())
            ));

            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                sprintf('%s.%s', $filter, $_format)
            );
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
