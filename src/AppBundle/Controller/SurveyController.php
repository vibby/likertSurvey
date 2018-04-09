<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Respondent;
use AppBundle\Form\IsManagerType;
use AppBundle\Form\YearsMonthsType;
use AppBundle\Tools\Shuffle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

class SurveyController extends Controller
{
    /**
     * @Route(
     *     "/commencer",
     *     name="is_manager"
     * )
     */
    public function isManagerAction(Request $request)
    {
        if (!($respondent = $this->getRespondentFromSessionOrRedirect()) instanceof Respondent) {
            return $respondent;
        }

        $isManagerForm = $this->createForm(IsManagerType::class, $respondent);
        $isManagerForm->handleRequest($request);
        if ($isManagerForm->isSubmitted() && $isManagerForm->isValid()) {
//            $respondent = $isManagerForm->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($respondent);
            $em->flush();

            return $this->redirectToRoute('survey');
        }

        return $this->render(
            'is_manager.html.twig',
            [
                'isManagerForm' => $isManagerForm->createView(),
            ]
        );
    }

    /**
     * @Route("/questionnaire", name="survey")
     */
    public function questionnaireAction(Request $request)
    {
        if (!($respondent = $this->getRespondentFromSessionOrRedirect()) instanceof Respondent) {
            return $respondent;
        }

        if ($respondent->getIsManager() === null) {
            return $this->redirectToRoute('is_manager');
        }

        $likertScales = $this->container->getParameter('likert_scales');
        $likertQuestions = array_merge(
            $respondent->getIsManager()
                ? $this->container->getParameter('likert_questions_manager')
                : $this->container->getParameter('likert_questions_collab'),
            $this->container->getParameter('likert_questions_common')
        );

        if (!$respondent->getStartDate()) {
            $respondent->setStartDate(new \DateTime());
        }

        $responseData = $respondent->getResponse();
        if (!is_array($responseData)) {
            $responseData = [];
        }

        $idPage = 0;
        $found = false;
        do {
            $idPage++;
            if (!array_key_exists('page' . $idPage, $likertQuestions)) {
                $found = true;
            } else {
                foreach ($likertQuestions['page'. $idPage] as $qKey => $likertQuestion) {
                    $responseKey = 'page' . $idPage . '_item' . $qKey;
                    if (!array_key_exists($responseKey, $responseData) || ($responseData[$responseKey] === null && $likertQuestion['type'] !== 'separator')) {
                        $found = true;
                    }
                }
            }
        } while (array_key_exists('page'. $idPage, $likertQuestions) && !$found);

        $formBuilder = $this->createFormBuilder($responseData, ['allow_extra_fields' => true]);
        $isLastPage = false;

        if ($idPage <= count($likertQuestions)) {
            // Find order of questions
            $questions = $likertQuestions['page'. $idPage];
            $keysOrdered = null;
            if (array_key_exists(
                sprintf(
                    'page%s_item%s',
                    $idPage,
                    array_keys($questions)[0]
                ),
                $responseData
            )) {
                $keysOrdered = [];
                foreach ($responseData as $key => $response) {
                    preg_match(
                        sprintf('#page%s_item(.*)$#', $idPage),
                        $key,
                        $matches
                    );
                    if (isset($matches[1])) {
                        $keysOrdered[] = $matches[1];
                    }
                }
            } elseif ($this->get('session')->has('keys_ordered')) {
                $keysOrdered = $this->get('session')->get('keys_ordered');
            }

            if ($keysOrdered) {
                $orderedQuestions = [];
                foreach ($keysOrdered as $key) {
                    if (isset($questions[$key])) {
                        $orderedQuestions[$key] = $questions[$key];
                    }
                }
                $questions = $orderedQuestions;
            } else {
                $questions = Shuffle::shuffleQuestions($questions);
                $this->get('session')->set('keys_ordered', array_keys($questions));
            }

            foreach($questions as $qKey => $likertQuestion) {
                $choices = $this->getChoicesFromScale($likertQuestion);
                $labels = $this->getLabelsFromScale($likertQuestion);
                $formBuilder->add( 'page'.$idPage.'_item'.$qKey , Type\ChoiceType::class, array(
                    'choices' => $choices ? $choices : [],
                    'expanded' => true,
                    'multiple' => false,
                    'constraints' => $choices ? new Assert\Choice(array_values($choices)) : null,
                    'attr' => array(
                        'class' => $likertQuestion['type'],
                        'labelFrom' => $labels[0],
                        'labelTo' => $labels[1],
                    ),
                    'label' => $likertQuestion['label'],
                ));
            }
        } else {
            $salaries = array(
                1 => '< 19 999€',
                2 => '20 000€ - 29 999€',
                3 => '30 000€ - 39 999€',
                4 => '40 000€ - 49 999€',
                5 => '50 000€ - 59 999€',
                6 => '60 000€ - 69 999€',
                7 => '70 000€ - 79 999€',
                8 => '80 000€ - 89 999€',
                9 => '90 000€ - 99 999€',
                10 => '≥ 100 000€',
            );
            $studyLevels = array(
                6 => 'Collège (classes de 6ème à 3ème)',
                5 => 'CAP, BEP, Diplôme National de Brevet ou équivalents',
                4 => 'Bac (général, technique ou professionnel), Brevet de Technicien, Brevet Professionnel',
                3 => 'Bac + 2 : Licence 2, BTS, DUT',
                2 => 'Bac + 3 et Bac + 4 : Licence 3, Licence professionnelle, Maîtrise / Master 1',
                1 => 'Bac + 5 ou plus : Master 2, Doctorat, diplômes d’école…',
            );
            $jobs = array(
                1 => 'Artisans',
                2 => 'Commerçants et assimilés',
                3 => 'Chefs d’entreprise',
                4 => 'Professions libérales et assimilés',
                5 => 'Cadres de la fonction publique, professions intellectuelles et artistiques',
                6 => 'Cadres d’entreprise',
                7 => 'Professions intermédiaires de l’enseignement, de la santé, de la fonction publique et assimilés',
                8 => 'Professions intermédiaires administratives et commerciales des entreprises',
                9 => 'Techniciens',
                10 => 'Contremaîtres, agents de maîtrise',
                11 => 'Employés de la fonction publique',
                12 => 'Employés administratifs d’entreprise',
                13 => 'Employés de commerce',
                14 => 'Personnels des services directs aux particuliers',
                15 => 'Ouvriers',
            );
            $sectors = array(
                'A' => 'Agriculture, sylviculture et pêche',
                'B' => 'Industries extractives',
                'C' => 'Industries manufacturières',
                'D' => 'Production et distribution d’électricité, de gaz, de vapeur et d’air conditionné',
                'E' => 'Production et distribution d’eau ; assainissement, gestion des déchets et dépollution',
                'F' => 'Construction',
                'G' => 'Commerce ; réparation d’automobiles et de motocycles',
                'H' => 'Transports et entreposage',
                'I' => 'Hébergement et restauration',
                'J' => 'Information et communication',
                'K' => 'Activités financières et d’assurance',
                'L' => 'Activités immobilières',
                'M' => 'Activités spécialisées, scientifiques et techniques',
                'N' => 'Activités de services administratifs et de soutien',
                'O' => 'Administration publique',
                'P' => 'Enseignement',
                'Q' => 'Santé humaine et action sociale',
                'R' => 'Arts, spectacles et activités récréatives',
                'S' => 'Autres activités de services',
            );
            $sizes = array(
                1 => 'Petite entreprise, 1–9 salariés',
                2 => 'Moyenne Entreprise, 10–49 salariés',
                3 => 'Entreprise de Taille Intermédiaire (ETI), 50–249 salariés',
                4 => 'Grande entreprise, 250 salariés ou plus',
            );
            $telework = array(
                0 => 'Non',
                2 => 'Oui, 1 à 3 jours / mois',
                3 => 'Oui, 1 jour / semaine',
                4 => 'Oui, 2 jours / semaine',
                5 => 'Oui, 3 jours / semaine',
                6 => 'Oui, 4 jours / semaine',
                7 => 'Oui, 5 jours ou plus par semaine',
            );

            $isLastPage = true;
            $formBuilder
                ->add( 'Sexe', Type\ChoiceType::class, array(
                    'placeholder' => '-sélectionner-',
                    'choices' => array_flip(array('Homme','Femme')) ,
                    'expanded' => true,
                    'multiple' => false,
                    'constraints' => new Assert\Choice(array(0,1)),
                    'label' => "Genre",
                    'required' => true,
                ))
                ->add( 'age', Type\IntegerType::class, array(
                    'label' => "Votre age",
                    'required' => true,
                    'attr' => [
                        'min' => 18,
                    ],
                ))
                ->add('Duree_societe', YearsMonthsType::class, array(
                    'label' => "Depuis combien de temps travaillez-vous dans votre entreprise actuelle ?",
                    'required' => true,
                ))
                ->add('Duree_poste', YearsMonthsType::class, array(
                    'label' => "Depuis combien de temps travaillez-vous à votre poste actuel ?",
                    'required' => true,
                ))
                ->add('Duree_management', YearsMonthsType::class, array(
                    'label' => "Dans votre vie professionnelle, combien de temps au total avez-vous exercé des fonctions d’encadrement/de management (dans cette entreprise ou dans d’autres) ?",
                    'required' => true,
                ))
                ->add( 'Salaire', Type\ChoiceType::class, array(
                    'placeholder' => '-sélectionner-',
                    'choices' => array_flip($salaries) ,
                    'expanded' => false,
                    'multiple' => false,
                    'constraints' => new Assert\Choice(array_keys($salaries)),
                    'label' => "Quel est votre salaire annuel imposable ?",
                    'required' => true,
                ))
                ->add( 'Niveau_etude', Type\ChoiceType::class, array(
                    'placeholder' => '-sélectionner-',
                    'choices' => array_flip($studyLevels) ,
                    'expanded' => false,
                    'multiple' => false,
                    'constraints' => new Assert\Choice(array_keys($studyLevels)),
                    'label' => "Quel est votre niveau d’études ?",
                    'required' => true,
                ))
                ->add( 'Profession', Type\ChoiceType::class, array(
                    'placeholder' => '-sélectionner-',
                    'choices' => array_flip($jobs) ,
                    'expanded' => false,
                    'multiple' => false,
                    'constraints' => new Assert\Choice(array_keys($jobs)),
                    'label' => "Choisissez dans le menu déroulant la catégorie socio-professionnelle qui correspond le mieux à votre activité professionnelle",
                    'required' => true,
                ))
                ->add( 'Secteur', Type\ChoiceType::class, array(
                    'placeholder' => '-sélectionner-',
                    'choices' => array_flip($sectors) ,
                    'expanded' => false,
                    'multiple' => false,
                    'constraints' => new Assert\Choice(array_keys($sectors)),
                    'label' => "Indiquez le secteur d’activité de votre entreprise actuelle",
                    'required' => true,
                ))
                ->add( 'Entreprise', Type\TextType::class, array(
                    'label' => "Nom de votre entreprise",
                    'required' => true,
                ))
                ->add( 'Taille', Type\ChoiceType::class, array(
                    'placeholder' => '-sélectionner-',
                    'choices' => array_flip($sizes) ,
                    'expanded' => false,
                    'multiple' => false,
                    'constraints' => new Assert\Choice(array_keys($sizes)),
                    'label' => "Indiquez la taille de votre entreprise actuelle",
                    'required' => true,
                ))
                ->add( 'Heures_travail_semaine', Type\IntegerType::class, array(
                    'label' => "Indiquez votre nombre moyen d’heures travaillées par semaine",
                    'required' => true,
                    'attr' => [
                        'min' => 1,
                    ],
                ))
                ->add( 'Taille_equipe', Type\IntegerType::class, array(
                    'label' => "Indiquez la taille de votre équipe de travail",
                    'required' => true,
                    'attr' => [
                        'min' => 1,
                    ],
                ))
                ->add( 'Teletravail', Type\ChoiceType::class, array(
                    'placeholder' => '-sélectionner-',
                    'choices' => array_flip($telework) ,
                    'expanded' => false,
                    'multiple' => false,
                    'constraints' => new Assert\Choice(array_keys($telework)),
                    'label' => "Travaillez-vous à distance, et si oui, à quelle fréquence moyenne ?",
                    'required' => true,
                ))
                ->add( 'Nb_bureau', Type\IntegerType::class, array(
                    'label' => "Avec combien de personnes partagez-vous votre bureau ?",
                    'required' => true,
                    'attr' => [
                        'min' => 0,
                    ],
                ))
            ;
        }
        $form = $formBuilder->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $data = array_merge($responseData, $formData);
            $respondent->setResponse($data);

            if ($request->isXmlHttpRequest()) {
                return new Response('ok');
            }

            $this->get('session')->set('keys_ordered', null);
            if ($idPage > count($likertQuestions)) {

                $time = time();
                $respondent->setFinishDate(new \DateTime());
                $respondent->setFinished(true);

                $dataList = "";
                foreach ($data as $key => &$value) {
                    if (is_array($value)) {
                        $value = implode('-', $value);
                    }
                    $dataList .= $key .': '. $value. "\n";
                }

//                $message = \Swift_Message::newInstance()
//                    ->setSubject('[EnquêteVieAuTravail] Nouvelle réponse')
//                    ->setFrom(array('noreply@univ-nantes.fr'))
//                    ->setTo(array('vincent.beauvivre@gmail.com', 'kristina.beauvivre@gmail.com'))
//                    ->setBody("Une nouvelle réponse au formulaire :\n\n".$dataList);
//                $this->get('mailer')->send($message);

                $path = $this->container->getParameter('kernel.root_dir'). '/../app/data/responses/';
                $handle = fopen($path. '_all.csv', 'a');
                fputcsv($handle, $data, ';');
                fclose($handle);

                $handle = fopen($path.$time.'.csv', 'w');
                fwrite($handle, $dataList);
                fclose($handle);

                $this->get('session')->set('data',null);
                $this->get('session')->set('respondentId',$respondent->getId());

                $nextRoute = 'thanks';
            } else {
                $nextRoute = 'survey';
            }

            $em = $this->getDoctrine()->getManagerForClass(Respondent::class);
            $em->persist($respondent);
            $em->flush();

            return $this->redirectToRoute($nextRoute);
        }

        // display the form
        return $this->render('form.html.twig', [
            'form' => $form->createView(),
            'shownPage' => $idPage,
            'scales' => array_keys($likertScales),
            'pages' => range(1, count($likertQuestions) + 1),
            'isLastPage' => $isLastPage,
        ]);

    }

    private function getChoicesFromScale(array $likertQuestion)
    {
        if (!$scale = $this->getScale($likertQuestion)) {
            return [];
        }

        if (isset($scale['scale'])) {
            $scale = $scale['scale'];
        }
        switch ($likertQuestion['type']) {
            case 'osgood':
            case 'likert':
                return array_flip($scale);
            default:
                throw new \Exception('Cannot understand scale type');
        }
    }

    private function getLabelsFromScale(array $likertQuestion)
    {
        if (($scale = $this->getScale($likertQuestion)) && isset($scale['labels'])) {
            return $scale['labels'];
        }

        return [null, null];
    }

    private function getScale(array $likertQuestion)
    {
        if (!isset($likertQuestion['scale']) || !$likertQuestion['scale']) {
            return null;
        }

        $likertScales = $this->container->getParameter('likert_scales');

        return $likertScales[$likertQuestion['scale']];
    }

    private function getRespondentFromSessionOrRedirect()
    {
        $currentRespondentKey = $this->get('session')->get('currentRespondentKey');
        if (!$currentRespondentKey) {
            $this->addFlash('error', 'La session de votre clé d’activation n’est plus valide. Veuillez l’entrer à nouveau');

            return $this->redirectToRoute('homepage');
        }
        $respondent = $this->getDoctrine()->getRepository(Respondent::class)->findOneBy(['key' => $currentRespondentKey]);
        if (!$respondent) {
            $this->addFlash('error', 'La session de votre clé d’activation n’est plus valide. Veuillez l’entrer à nouveau');

            return $this->redirectToRoute('homepage');
        }
        if ($respondent->isFinished()) {
            $this->addFlash('success', 'Vous avez déjà complété l’enquête');

            return $this->redirectToRoute('thanks');
        }

        return $respondent;
    }
}
