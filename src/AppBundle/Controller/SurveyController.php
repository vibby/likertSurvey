<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Respondent;
use AppBundle\Form\IsManagerType;
use AppBundle\Form\SubscribeType;
use AppBundle\Tools\Shuffle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormFactory;
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

        $isManagerForm = $this->createForm(IsManagerType::class);
        $isManagerForm->handleRequest($request);
        if ($isManagerForm->isSubmitted() && $isManagerForm->isValid()) {
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
     * @Route("/questionnaire/{idPage}", name="survey")
     */
    public function questionnaireAction(Request $request, $idPage = 1)
    {
        if (!($respondent = $this->getRespondentFromSessionOrRedirect()) instanceof Respondent) {
            return $respondent;
        }

        $likertScales = $this->container->getParameter('likert_scales');
        $likertQuestions = $this->container->getParameter('likert_questions');

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
            if (!array_key_exists('page'. $idPage, $likertQuestions))
                $found = true;
            else foreach ($likertQuestions['page'. $idPage] as $qKey => $likertQuestion) {
                $responseKey = 'page'. $idPage.'_item'.$qKey;
                if (!array_key_exists($responseKey, $responseData) || ($responseData[$responseKey] === null && $likertQuestion['type'] !== 'separator')) {
                    $found = true;
                }
            }
        } while (array_key_exists('page'. $idPage, $likertQuestions) && !$found);

        $formBuilder = $this->createFormBuilder($responseData);
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
                    $keysOrdered[] = $matches[1];
                }
            } elseif ($this->get('session')->has('keys_ordered')) {
                $keysOrdered = $this->get('session')->get('keys_ordered');
            }

            if ($keysOrdered) {
                $orderedQuestions = [];
                foreach ($keysOrdered as $key) {
                    $orderedQuestions[$key] = $questions[$key];
                }
                $questions = $orderedQuestions;
            } else {
                $questions = Shuffle::shuffleQuestions($questions);
                $this->get('session')->set('keys_ordered', array_keys($questions));
            }

            foreach($questions as $qKey => $likertQuestion) {
                $choices = $this->getChoicesFromScale($likertQuestion);
                $formBuilder->add( 'page'.$idPage.'_item'.$qKey , Type\ChoiceType::class, array(
                    'choices' => $choices ? $choices : [],
                    'expanded' => true,
                    'multiple' => false,
                    'constraints' => $choices ? new Assert\Choice(array_values($choices)) : null,
                    'attr' => array(
                        'class' => $likertQuestion['type'],
                    ),
                    'label' => $likertQuestion['label'],
                ));
            }
        } else {
            /*
            $sectors = array(
                'Public',
                'Privé',
                'Parapublic',
            );
            $jobs = array(
                "Agriculteurs exploitants",
                "Artisans",
                "Commerçants et assimilés",
                "Chefs d'entreprise de plus de 10 salariés ou plus",
                "Professions libérales et assimilés",
                "Cadres de la fonction publique, professions intellectuelles et artistiques",
                "Cadres d'entreprise",
                "Professions intermédiaires de l'enseignement, de la santé, de la fonction publique et assimilés",
                "Professions intermédiaires administratives et commerciales des entreprises",
                "Techniciens",
                "Contremaîtres, agents de maîtrise",
                "Employés de la fonction publique",
                "Employés administratifs d'entreprise",
                "Employés de commerce",
                "Personnels de services directs aux particuliers",
                "Ouvriers qualifiés",
                "Ouvriers non qualifiés",
                "Ouvriers agricoles"
            );
            $domains = array(
                "Agriculture",
                "Industrie",
                "Électricité, gaz et eau",
                "Construction",
                "Commerce",
                "Hôtels et restaurants",
                "Transport",
                "Communication",
                "Finances, banques et assurances",
                "Immobilier",
                "Administration publique",
                "Education - Enseignement",
                "Social - Aide aux personnes",
                "Santé",
                "Informatique et nouvelles technologies",
                "Autre, préciser ci-dessous",
            );
            */

            $isLastPage = true;
            $formBuilder
                ->add( 'age', Type\IntegerType::class, array(
                    'label' => "Votre age",
                    'required' => true,
                ))
                ->add( 'Sexe', Type\ChoiceType::class, array(
                    'placeholder' => '-sélectionner-',
                    'choices' => array_flip(array('Homme','Femme')) ,
                    'expanded' => true,
                    'multiple' => false,
                    'constraints' => new Assert\Choice(array(0,1)),
                    'label' => "Sexe :",
                    'required' => true,
                ))
                /*
                ->add( 'Situation_famille', Type\ChoiceType::class, array(
                    'placeholder' => '-sélectionner-',
                    'choices' => array_flip(array('Seul','En couple')) ,
                    'expanded' => true,
                    'multiple' => false,
                    'constraints' => new Assert\Choice(array(0,1)),
                    'label' => "Situation familiale :",
                    'required' => true,
                ))
                ->add( 'Nombre_enfants_a_charge', Type\ChoiceType::class, array(
                    'constraints' => new Assert\Type('Integer', 'Cette valeur doit être un nombre entier'),
                    'label' => "Nombre d'enfants ou de personnes à votre charge :",
                    'placeholder' => '-sélectionner-',
                    'choices' => range(0, 10)
                ))
                ->add( 'Profession', Type\ChoiceType::class, array(
                    'placeholder' => '-sélectionner-',
                    'choices' => array_flip($jobs) ,
                    'expanded' => false,
                    'multiple' => false,
                    'constraints' => new Assert\Choice(array_keys($jobs)),
                    'label' => "Quelle est votre profession ?",
                    'required' => true,
                ))
                ->add( 'Secteur', Type\ChoiceType::class, array(
                    'placeholder' => '-sélectionner-',
                    'choices' => array_flip($sectors) ,
                    'expanded' => false,
                    'multiple' => false,
                    'constraints' => new Assert\Choice(array_keys($sectors)),
                    'label' => "Quel est le type de secteur de votre entreprise ?",
                    'required' => true,
                ))
                ->add( 'Intitule_poste', Type\TextType::class, array(
                    'label' => "Quel est l'intitulé exact de votre poste actuel ?",
                    'required' => true,
                ))
                ->add( 'Heures_travail_semaine', Type\IntegerType::class, array(
                    'label' => "Combien d'heures par semaine travaillez-vous ?",
                    'required' => true,
                ))
                ->add( 'Heures_travail_mois', Type\IntegerType::class, array(
                    'label' => "Combien d'heures supplémentaires effectuez-vous par mois, environ ?",
                    'required' => true,
                ))
                ->add( 'travaillez_vous', Type\ChoiceType::class, array(
                    'placeholder' => '-sélectionner-',
                    'label' => 'Travaillez-vous',
                    'required' => true,
                    'choices' => array_flip(array(
                        'de jour',
                        'de nuit',
                        'en 2/8',
                        'en 3/8',
                        'autre (précisez)',
                    ))
                ))
                ->add('travaillez_vous_other', Type\TextType::class, array(
                    'label' => 'Si autre, précisez',
                    'required' => false,
                ))
                ->add( 'Satisfaction_salaire', Type\ChoiceType::class, array(
                    'label' => "Êtes-vous satisfait(e) de votre salaire net mensuel ?",
                    'required' => true,
                    'expanded' => true,
                    'choices' => array_flip(array(
                        'oui',
                        'non',
                    ))
                ))
                */
            ;

            /** @var FormFactory $formFactory */
            /*
            $formFactory = $this->get('form.factory');
            $formBuilder2 = $formFactory
                ->createNamedBuilder('Duree_poste', Type\FormType::class, array(
                    'label' => "Depuis quand travaillez-vous à votre poste actuel (années et mois) ?",
                    'required' => true,
                    ))
                ->add('Duree_poste_ans', Type\ChoiceType::class, array(
                    'label' => ' ',
                    'required' => true,
                    'placeholder' => '-années-',
                    'choices' => range(0, 45)
                    ))
                ->add('Duree_poste_mois', Type\ChoiceType::class, array(
                    'label' => ' ',
                    'required' => true,
                    'placeholder' => '-mois-',
                    'choices' => range(0, 11)
                    ))
            ;
            $formBuilder3 = $this->get('form.factory')
                ->createNamedBuilder('Duree_societe', Type\FormType::class, array(
                    'label' => "Depuis quand travaillez-vous dans votre entreprise actuel (années et mois) ?",
                    'required' => true,
                    ))
                ->add('Duree_societe_ans', Type\ChoiceType::class, array(
                    'label' => ' ',
                    'required' => true,
                    'placeholder' => '-années-',
                    'choices' => range(0, 45)
                    ))
                ->add('Duree_societe_mois', Type\ChoiceType::class, array(
                    'label' => ' ',
                    'required' => true,
                    'placeholder' => '-mois-',
                    'choices' => range(0, 11)
                    ))
            ;

            $formBuilder
                ->add($formBuilder2, null, array(
                    'required' => true,
                    ))
                ->add($formBuilder3, null, array(
                    'required' => true,
                    ))
                ->add( 'Societe', Type\TextType::class, array(
                    'label' => "Nom de votre entreprise (facultatif) :",
                    'required' => false ,
                    ))
                ->add( 'Domain', Type\ChoiceType::class, array(
                    'choices' => array_flip($domains),
                    'expanded' => false,
                    'multiple' => false,
                    'constraints' => new Assert\Choice(array_keys($domains)),
                    'label' => "À quelle branche appartient votre entreprise ?",
                    'placeholder' => '-sélectionner-',
                    'required' => true,
                    ))
                ->add( 'Domain_other', Type\TextType::class, array(
                    'label' => "Si autre, précisez",
                    'required' => false ,
                    ))
                ->add( 'Nombre_salaries_etablissement', Type\IntegerType::class, array(
                    'label' => "Nombre de salariés dans votre  établissement (facultatif) :",
                    'required' => false ,
                    ))
                ->add( 'Nombre_salaries_entreprise', Type\IntegerType::class, array(
                    'label' => "Nombre total de salariés dans votre entreprise (facultatif) :",
                    'required' => false ,
                    ))
            ;
            */
        }
        $form = $formBuilder->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $data = array_merge($formData, $responseData);
            $respondent->setResponse($data);
            $em = $this->getDoctrine()->getManagerForClass(Respondent::class);
            $em->persist($respondent);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response('ok');
            }

            if ($idPage > count($likertQuestions)) {

                $time = time();
                $respondent->setFinishDate(new \DateTime());

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

                $path = $this->container->getParameter('kernel.root_dir'). '/../app/Responses/';
                $handle = fopen($path. '_toutes.csv', 'a');
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
        if (!isset($likertQuestion['scale']) || !$likertQuestion['scale']) {
            return [];
        }

        $likertScales = $this->container->getParameter('likert_scales');
        $scale = $likertScales[$likertQuestion['scale']];
        switch ($likertQuestion['type']) {
            case 'likert':
            case 'osgood':
                return array_flip($scale);
            default:
                throw new \Exception('Cannot understand scale type');
        }
    }

    private function getRespondentFromSessionOrRedirect()
    {
        $currentRespondentKey = $this->get('session')->get('currentRespondentKey');
        if (!$currentRespondentKey) {
            $this->addFlash('Error', 'La session de votre clé d’activation n’est plus valide. Veuillez l’entrer à nouveau');

            return $this->redirectToRoute('homepage');
        }
        $respondent = $this->getDoctrine()->getRepository(Respondent::class)->findOneBy(['key' => $currentRespondentKey]);
        if (!$respondent) {
            $this->addFlash('Error', 'La session de votre clé d’activation n’est plus valide. Veuillez l’entrer à nouveau');

            return $this->redirectToRoute('homepage');
        }

        return $respondent;
    }
}
